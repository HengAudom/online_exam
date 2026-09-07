<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentSubmission;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Inspect recent updates to find Chat IDs of users or groups that messaged the bot.
     */
    public function getChatId(Request $request)
    {
        $botInfo = $this->telegram->getMe();
        $updates = $this->telegram->getUpdates();

        $chats = [];
        if (!empty($updates['ok']) && !empty($updates['result'])) {
            foreach ($updates['result'] as $up) {
                $msg = $up['message'] ?? $up['my_chat_member'] ?? null;
                if ($msg && isset($msg['chat'])) {
                    $c = $msg['chat'];
                    $cid = (string) $c['id'];
                    $title = $c['title'] ?? trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                    $username = $c['username'] ?? 'None';
                    $type = $c['type'] ?? 'private';
                    $lastText = $msg['text'] ?? ($msg['caption'] ?? '(Non-text update)');

                    $chats[$cid] = [
                        'chat_id' => $cid,
                        'name' => $title,
                        'username' => $username ? '@' . ltrim($username, '@') : 'N/A',
                        'type' => $type,
                        'last_message' => $lastText,
                        'date' => isset($msg['date']) ? date('Y-m-d H:i:s', $msg['date']) : null,
                    ];
                }
            }
        }

        $currentConfigChatId = config('services.telegram.admin_chat_id', env('TELEGRAM_ADMIN_CHAT_ID'));

        return response()->json([
            'success' => true,
            'bot_status' => $botInfo['ok'] ?? false ? 'CONNECTED' : 'DISCONNECTED',
            'bot' => $botInfo['result'] ?? null,
            'configured_admin_chat_id' => $currentConfigChatId ?: 'NOT_CONFIGURED_YET',
            'discovered_chats' => array_values($chats),
            'instruction' => empty($chats) 
                ? "ដើម្បីដឹង Chat ID របស់អ្នក៖ សូមបើក Telegram ហើយស្វែងរក @onlinexam_bot រួចចុច /start (ឬ Add Bot ចូល Telegram Group ហើយផ្ញើសារមួយ) រួច refresh ទំព័រនេះឡើងវិញ។"
                : "សូមចម្លង 'chat_id' ខាងលើ យកទៅដាក់ក្នុង file .env ត្រង់ TELEGRAM_ADMIN_CHAT_ID=..."
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send a test message to verify the connection.
     */
    public function testSend(Request $request)
    {
        $chatId = $request->query('chat_id') ?: config('services.telegram.admin_chat_id', env('TELEGRAM_ADMIN_CHAT_ID'));

        if (empty($chatId)) {
            return response()->json([
                'success' => false,
                'message' => 'Chat ID មិនទាន់ត្រូវបានកំណត់ទេ។ សូមកំណត់ TELEGRAM_ADMIN_CHAT_ID ក្នុង .env ឬបញ្ជូន ?chat_id=YOUR_CHAT_ID',
            ], 400);
        }

        $hostingUrl = config('app.url');
        if (empty($hostingUrl) || str_contains($hostingUrl, 'localhost')) {
            $currentRoot = $request->root();
            if ($currentRoot && !str_contains($currentRoot, 'localhost')) {
                $hostingUrl = $currentRoot;
            } else {
                $hostingUrl = $currentRoot ?: ($hostingUrl ?: 'http://localhost:8000');
            }
        }

        $dateStr = now()->setTimezone('Asia/Phnom_Penh')->format('d-m-Y H:i:s');

        $text = "🔔 <b>សួស្ដី! នេះជាសារពី OnlinExam System</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "✅ Telegram Bot ដំណើរការបានយ៉ាងត្រឹមត្រូវ ១០០%!\n"
            . "🕒 <b>កាលបរិច្ឆេទ:</b> {$dateStr}\n"
            . "🌐 <b>URL:</b> {$hostingUrl}\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "🎓 ប្រព័ន្ធនឹងផ្ញើសារដំណឹងស្វ័យប្រវត្តិនូវរាល់ពេលសិស្សបញ្ចប់ការប្រឡង!";

        $result = $this->telegram->sendMessage($chatId, $text);

        return response()->json([
            'success' => $result['ok'] ?? false,
            'result' => $result,
        ], !empty($result['ok']) ? 200 : 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Telegram Webhook handler for incoming bot messages & commands.
     */
    public function webhook(Request $request)
    {
        $update = $request->all();
        $res = $this->handleUpdate($update);
        return response()->json($res);
    }

    /**
     * Helper to find a student by various inputs (Code, StudentId, Phone, loose format like rtc-2026-00002).
     */
    public function findStudentByQuery(?string $query): ?Student
    {
        $clean = trim((string)$query);
        if (empty($clean)) {
            return null;
        }

        // 1. Direct match on StudentCode, Phone, StudentId
        $student = Student::where('StudentCode', $clean)
            ->orWhere('StudentCode', strtoupper($clean))
            ->orWhere('Phone', $clean)
            ->orWhere('StudentId', $clean)
            ->first();

        if ($student) {
            return $student;
        }

        // 2. Extract trailing numbers (e.g. rtc-2026-00002 -> 2)
        if (preg_match('/(?:RTC[_-]?)?(?:202\d[_-]?)?0*(\d+)/i', $clean, $m)) {
            $num = (int)$m[1];
            if ($num > 0) {
                $student = Student::where('StudentId', $num)->first();
                if ($student) {
                    return $student;
                }

                $paddedPatterns = [
                    sprintf('RTC-2026-%04d', $num),
                    sprintf('RTC-2026-%05d', $num),
                    '%' . $num,
                ];
                foreach ($paddedPatterns as $pat) {
                    $student = Student::where('StudentCode', 'LIKE', $pat)->first();
                    if ($student) {
                        return $student;
                    }
                }
            }
        }

        return null;
    }

    /**
     * API endpoint to query a student's results (called by Google Apps Script / Telegram Bot).
     */
    public function getStudentResultsApi(Request $request)
    {
        $code = trim((string)$request->input('code', $request->query('code', '')));
        $fromId = trim((string)$request->input('fromId', $request->input('chatId', $request->query('fromId', $request->query('chatId', '')))));

        $student = null;
        if (!empty($code)) {
            $student = $this->findStudentByQuery($code);
        }

        if (!$student && !empty($fromId)) {
            $student = Student::where('TelegramChatId', $fromId)->first();
        }

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
                'found' => false,
            ], 404);
        }

        $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
        $studentCode = $student->StudentCode ?: ('RTC-' . $student->StudentId);

        $submissions = StudentSubmission::with('test')
            ->where('StudentId', $student->StudentId)
            ->whereNotNull('CompletedAt')
            ->orderBy('CompletedAt', 'desc')
            ->take(5)
            ->get();

        $results = [];
        foreach ($submissions as $sub) {
            $score = (float)($sub->Score ?? $sub->TotalScore ?? 0);
            $maxScore = (float)($sub->test->MaxScore ?? 100);
            $passScore = (float)($sub->test->PassingScore ?? ($maxScore / 2));
            $isPassed = $score >= $passScore;

            $results[] = [
                'submissionId' => $sub->SubmissionId,
                'testName' => $sub->test->TestName ?? 'វិញ្ញាសា',
                'score' => number_format($score, 2),
                'maxScore' => $maxScore,
                'isPassed' => $isPassed,
                'status' => $isPassed ? '✅ ជាប់' : '❌ ធ្លាក់',
                'date' => $sub->CompletedAt ? date('d/m/Y H:i', strtotime($sub->CompletedAt)) : 'N/A',
            ];
        }

        return response()->json([
            'success' => true,
            'found' => true,
            'student' => [
                'id' => $student->StudentId,
                'code' => $studentCode,
                'name' => $studentName ?: 'Student ' . $studentCode,
                'telegramChatId' => $student->TelegramChatId,
                'telegramUsername' => $student->TelegramUsername,
            ],
            'submissions' => $results,
        ]);
    }

    /**
     * Core update handler (shared by Webhook and Long Polling).
     */
    public function handleUpdate(array $update): array
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (!$message || !isset($message['chat']['id'])) {
            return ['status' => 'ignored', 'reason' => 'No chat message found in update'];
        }

        $chatId = (string) $message['chat']['id'];
        $fromUser = $message['from'] ?? [];
        $fromId = isset($fromUser['id']) ? (string) $fromUser['id'] : $chatId;
        $chatType = $message['chat']['type'] ?? 'private';
        $isGroup = in_array($chatType, ['group', 'supergroup']) || ((int)$chatId < 0);

        $text = trim($message['text'] ?? ($message['caption'] ?? ''));
        $firstName = $fromUser['first_name'] ?? 'User';
        $senderName = trim(($fromUser['first_name'] ?? '') . ' ' . ($fromUser['last_name'] ?? '')) ?: $firstName;
        $username = $fromUser['username'] ?? null;
        $cleanUsername = $username ? '@' . ltrim($username, '@') : null;
        $mentionName = $cleanUsername ?: $senderName;

        if (empty($text)) {
            return ['status' => 'ignored', 'reason' => 'Empty text'];
        }

        // ── 1. Check for Account Linking: /start link_CODE or /link CODE ──
        $linkTarget = null;
        if (preg_match('/^\/start(?:@\w+)?\s+link_([a-zA-Z0-9_\-]+)/i', $text, $matches)) {
            $linkTarget = $matches[1];
        } elseif (preg_match('/^\/start(?:@\w+)?\s+([a-zA-Z0-9_\-]+)/i', $text, $matches)) {
            $linkTarget = $matches[1];
        } elseif (preg_match('/^\/link(?:@\w+)?(?:\s+(.+))?$/i', $text, $matches)) {
            $linkTarget = !empty($matches[1]) ? trim($matches[1]) : null;
        }

        if ($linkTarget !== null) {
            // If /link was sent without code
            if (empty($linkTarget)) {
                $linkHelp = "ℹ️ <b>របៀបភ្ជាប់គណនី Telegram:</b>\n━━━━━━━━━━━━━━━━━━━━\n"
                    . "👉 សូមវាយពាក្យបញ្ជា <code>/link [លេខកូដសិស្ស]</code>\n"
                    . "<i>(ឧទាហរណ៍៖ <code>/link RTC-2026-0002</code>)</i>\n\n"
                    . "💡 ឬចូលទៅកាន់គេហទំព័រប្រឡង (Student Portal) រួចចុចលើប៊ូតុង <b>Connect Telegram</b>។";
                $this->telegram->sendMessage($chatId, $linkHelp);
                return ['status' => 'ok', 'action' => 'link_help_sent'];
            }

            $student = $this->findStudentByQuery($linkTarget);

            if (!$student) {
                $this->telegram->sendMessage($chatId, "❌ <b>មិនអាចភ្ជាប់គណនីបានទេ!</b>\n\nរកមិនឃើញទិន្នន័យសិស្សដែលមានលេខកូដ <code>{$linkTarget}</code> ក្នុងប្រព័ន្ធឡើយ។ សូមពិនិត្យលេខកូដសិស្សរបស់អ្នកឡើងវិញ ឬទាក់ទងគ្រូ/Admin។");
                return ['status' => 'ok', 'action' => 'link_failed', 'chat_id' => $chatId];
            }

            // CRITICAL: In groups, link the user's private ID ($fromId), NOT the group chat ID ($chatId)!
            $student->TelegramChatId = $fromId;
            if ($cleanUsername) {
                $student->TelegramUsername = $cleanUsername;
            }
            $student->save();

            $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
            $studentCode = $student->StudentCode ?: ('ID #' . $student->StudentId);

            $groupNote = $isGroup ? "\n👥 <i>(បានភ្ជាប់សម្រាប់គណនីផ្ទាល់ខ្លួនរបស់ {$mentionName})</i>" : "";

            $msg = "🎉 <b>ការភ្ជាប់គណនីបានជោគជ័យ! (Telegram Connected)</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "👤 <b>សិស្ស:</b> <b>{$studentName}</b>\n"
                . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                . "📱 <b>Telegram ID:</b> <code>{$fromId}</code>{$groupNote}\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "✅ គណនី Telegram របស់អ្នកត្រូវបានភ្ជាប់ជាមួយប្រព័ន្ធប្រឡង OnlinExam រួចរាល់ហើយ!\n\n"
                . "🎓 ចាប់ពីពេលនេះតទៅ រាល់ពេលអ្នកបញ្ចប់ការប្រឡង (Submit Exam) ប្រព័ន្ធនឹងផ្ញើសារពិន្ទុ និងលទ្ធផលប្រឡងទៅកាន់ Telegram របស់អ្នកដោយស្វ័យប្រវត្តិ។\n\n"
                . "👉 អ្នកអាចវាយ <code>/myresult</code> គ្រប់ពេលវេលាដើម្បីពិនិត្យលទ្ធផលប្រឡងកន្លងមក។";

            $this->telegram->sendMessage($chatId, $msg);
            return ['status' => 'ok', 'action' => 'linked', 'student_id' => $student->StudentId];
        }

        // ── 2. Command: /unlink (Case-insensitive) ──
        if (preg_match('/^\/unlink(?:@\w+)?/i', $text)) {
            $students = Student::where('TelegramChatId', $fromId)
                ->orWhere('TelegramChatId', $chatId)
                ->get();

            if ($students->isEmpty()) {
                $this->telegram->sendMessage($chatId, "ℹ️ {$mentionName} គណនី Telegram របស់អ្នកមិនទាន់បានភ្ជាប់ជាមួយសិស្សណាម្នាក់នៅឡើយទេ។");
                return ['status' => 'ok', 'action' => 'not_linked'];
            }

            foreach ($students as $s) {
                $s->TelegramChatId = null;
                $s->TelegramUsername = null;
                $s->save();
            }

            $this->telegram->sendMessage($chatId, "✂️ <b>គណនី Telegram របស់ {$mentionName} ត្រូវបានផ្តាច់ការភ្ជាប់ (Unlinked) ពីប្រព័ន្ធប្រឡងរួចរាល់ហើយ។</b>\n\nដើម្បីភ្ជាប់ឡើងវិញ សូមវាយ: <code>/link [អត្តលេខសិស្ស]</code>");
            return ['status' => 'ok', 'action' => 'unlinked'];
        }

        // ── 3. Command: /start or /help ──
        if (preg_match('/^\/start(?:@\w+)?$/i', $text) || preg_match('/^\/help(?:@\w+)?$/i', $text)) {
            $welcomeText = "👋 <b>សួស្ដី {$firstName}! សូមស្វាគមន៍មកកាន់ប្រព័ន្ធប្រឡង OnlinExam!</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "🤖 ខ្ញុំជា Bot សម្រាប់ជំនួយការប្រឡង ផ្ញើសារដំណឹង និងលទ្ធផលប្រឡងដោយស្វ័យប្រវត្តិ។\n\n"
                . "📌 <b>មុខងារ និងពាក្យបញ្ជា (Commands):</b>\n"
                . "👉 <code>/myresult</code> - ពិនិត្យលទ្ធផលប្រឡងរបស់អ្នក (ឬ <code>/myresult [លេខកូដសិស្ស]</code>)\n"
                . "👉 <code>/link [លេខកូដសិស្ស]</code> - ភ្ជាប់គណនី Telegram ដើម្បីទទួលពិន្ទុភ្លាមៗ\n"
                . "👉 <code>/unlink</code> - ផ្តាច់ការភ្ជាប់គណនី Telegram\n"
                . "👉 <code>/myid</code> - មើលលេខ Student ID និង Telegram ID\n"
                . "👉 <code>/help</code> - មើលការណែនាំជំនួយ\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "💡 <b>របៀបភ្ជាប់គណនី:</b> វាយពាក្យ <code>/link [លេខកូដសិស្ស]</code> (ឧទាហរណ៍៖ <code>/link RTC-2026-0002</code>)។";

            $appUrl = config('app.url', url('/'));
            $inlineKeyboard = [];
            if (str_starts_with($appUrl, 'https://')) {
                $inlineKeyboard[] = [
                    ['text' => '🌐 ចូលគេហទំព័រប្រឡង (Student Portal)', 'url' => $appUrl . '/student']
                ];
            }
            $adminUser = config('services.telegram.admin_username', env('TELEGRAM_ADMIN_USERNAME', 'DomAi1'));
            $inlineKeyboard[] = [
                ['text' => '👨‍💼 ទំនាក់ទំនង Admin', 'url' => 'https://t.me/' . ltrim($adminUser, '@')]
            ];

            $extra = [
                'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard])
            ];

            $this->telegram->sendMessage($chatId, $welcomeText, $extra);
            return ['status' => 'ok', 'action' => 'start_responded', 'chat_id' => $chatId];
        }

        // ── 4. Command: /myid or /chatid ──
        if (preg_match('/^\/(?:myid|chatid|id)(?:@\w+)?/i', $text)) {
            $student = Student::where('TelegramChatId', $fromId)
                ->orWhere('TelegramChatId', $chatId)
                ->first();

            $groupInfo = $isGroup ? "\n👥 <b>Telegram Group ID:</b> <code>{$chatId}</code>" : "";

            if ($student) {
                $studentCode = $student->StudentCode ?: ('ID #' . $student->StudentId);
                $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
                $this->telegram->sendMessage($chatId, "👤 <b>ព័ត៌មានគណនីរបស់ {$mentionName}:</b>\n━━━━━━━━━━━━━━━━━━━━\n👤 <b>ឈ្មោះ:</b> <b>{$studentName}</b>\n🆔 <b>លេខកូដសិស្ស:</b> <code>{$studentCode}</code>\n📱 <b>User Telegram ID:</b> <code>{$fromId}</code>{$groupInfo}\n━━━━━━━━━━━━━━━━━━━━\n✅ គណនីរបស់អ្នកបានភ្ជាប់ជាមួយប្រព័ន្ធរួចរាល់។");
            } else {
                $this->telegram->sendMessage($chatId, "⚠️ <b>{$mentionName} គណនី Telegram របស់អ្នកមិនទាន់បានភ្ជាប់ជាមួយសិស្សណាម្នាក់ឡើយ!</b>\n\n👉 សូមវាយពាក្យបញ្ជា <code>/link [លេខកូដសិស្ស]</code> (ឧទាហរណ៍៖ <code>/link RTC-2026-0002</code>) ដើម្បីភ្ជាប់គណនីរបស់អ្នក\n📱 <b>User ID របស់អ្នកគឺ:</b> <code>{$fromId}</code>{$groupInfo}");
            }
            return ['status' => 'ok', 'action' => 'myid_sent'];
        }

        // ── 5. Command: /myresult [លេខកូដសិស្ស] ──
        if (preg_match('/^\/myresult(?:@\w+)?(?:\s+(.+))?$/i', $text, $m)) {
            $queryCode = trim($m[1] ?? '');
            $student = null;

            if (!empty($queryCode)) {
                $student = $this->findStudentByQuery($queryCode);
                if (!$student) {
                    $this->telegram->sendMessage($chatId, "❌ <b>រកមិនឃើញទិន្នន័យសិស្សឡើយ!</b>\n\nមិនមានសិស្សដែលមានលេខកូដ <code>{$queryCode}</code> ក្នុងប្រព័ន្ធទេ។ សូមពិនិត្យលេខកូដសិស្សរបស់អ្នកឡើងវិញ ឬវាយ <code>/help</code> សម្រាប់ជំនួយ។");
                    return ['status' => 'ok', 'action' => 'student_not_found'];
                }
            } else {
                // If in group or private: look up by sender's fromId FIRST, then chatId!
                $student = Student::where('TelegramChatId', $fromId)
                    ->orWhere('TelegramChatId', $chatId)
                    ->first();
            }

            if (!$student) {
                $unlinkedMsg = "⚠️ <b>{$mentionName} អ្នកមិនទាន់បានភ្ជាប់គណនីនៅឡើយទេ!</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "ដើម្បីអាចពិនិត្យមើលពិន្ទុ និងលទ្ធផលប្រឡងបាន សូមជ្រើសរើស៖\n\n"
                    . "1️⃣ <b>ភ្ជាប់គណនី (ដើម្បីចុច /myresult មើលពិន្ទុភ្លាមៗ):</b>\n"
                    . "👉 វាយពាក្យ <code>/link [លេខកូដសិស្ស]</code>\n"
                    . "<i>(ឧទាហរណ៍៖ <code>/link RTC-2026-0002</code>)</i>\n\n"
                    . "2️⃣ <b>ឬឆែកមើលពិន្ទុដោយវាយលេខកូដផ្ទាល់:</b>\n"
                    . "👉 វាយ <code>/myresult [លេខកូដសិស្ស]</code>\n"
                    . "<i>(ឧទាហរណ៍៖ <code>/myresult RTC-2026-0002</code>)</i>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "💡 <i>បើអ្នកមិនចាំលេខកូដសិស្ស សូមពិនិត្យមើលក្នុង Student Portal ឬទាក់ទង Admin។</i>";

                $this->telegram->sendMessage($chatId, $unlinkedMsg);
                return ['status' => 'ok', 'action' => 'missing_student_code'];
            }

            $submissions = StudentSubmission::with('test')
                ->where('StudentId', $student->StudentId)
                ->whereNotNull('CompletedAt')
                ->orderBy('CompletedAt', 'desc')
                ->take(5)
                ->get();

            $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
            $studentCode = $student->StudentCode ?: ('ID #' . $student->StudentId);

            if ($submissions->isEmpty()) {
                $this->telegram->sendMessage($chatId, "ℹ️ សិស្ស <b>{$studentName}</b> (<code>{$studentCode}</code>) មិនទាន់មានប្រវត្តិបញ្ចប់ការប្រឡងណាមួយនៅឡើយទេ។\n\n🎓 <i>នៅពេលអ្នកប្រឡងចប់ ពិន្ទុនឹងបង្ហាញនៅទីនេះស្វ័យប្រវត្តិ!</i>");
                return ['status' => 'ok', 'action' => 'no_submissions'];
            }

            $msg = "📊 <b>លទ្ធផលប្រឡងរបស់: {$studentName}</b>\n"
                . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n";

            foreach ($submissions as $idx => $sub) {
                $testName = $sub->test->TestName ?? 'វិញ្ញាសា';
                $score = number_format((float)($sub->Score ?? $sub->TotalScore ?? 0), 2);
                $maxScore = $sub->test->MaxScore ?? 100;
                $passScore = $sub->test->PassingScore ?? ($maxScore / 2);
                $isPassed = (float)$score >= (float)$passScore ? '✅ ជាប់' : '❌ ធ្លាក់';
                $date = $sub->CompletedAt ? date('d/m/Y H:i', strtotime($sub->CompletedAt)) : 'N/A';

                $msg .= ($idx + 1) . ". 📝 <b>{$testName}</b>\n"
                    . "   🎯 <b>ពិន្ទុ:</b> <b>{$score}/{$maxScore}</b> ({$isPassed}) | 📅 {$date}\n\n";
            }

            $msg .= "━━━━━━━━━━━━━━━━━━━━\n🌐 <i>ប្រព័ន្ធប្រឡង OnlinExam</i>";
            $this->telegram->sendMessage($chatId, $msg);
            return ['status' => 'ok', 'action' => 'results_sent'];
        }

        // ── In Group Chats: Ignore normal chat messages to prevent bot spam ──
        if ($isGroup) {
            return ['status' => 'ignored', 'reason' => 'Non-command message in group chat'];
        }

        // ── Private Chat Fallback: Help the user ──
        $this->telegram->sendMessage($chatId, "👋 <b>សួស្ដី {$firstName}!</b> ខ្ញុំជា Bot សម្រាប់ជំនួយការប្រឡង OnlinExam។\n\n👉 វាយ <code>/myresult</code> ដើម្បីមើលលទ្ធផលប្រឡង\n👉 វាយ <code>/link [លេខកូដសិស្ស]</code> ដើម្បីភ្ជាប់គណនី\n👉 វាយ <code>/help</code> សម្រាប់ជំនួយ");
        return ['status' => 'ok', 'action' => 'unknown_command_fallback'];
    }

    /**
     * Poll updates manually or from background worker (useful for local development).
     */
    public function pollUpdates(Request $request)
    {
        $offset = (int) $request->input('offset', 0);
        $updates = $this->telegram->getUpdates($offset, 100, 0);

        if (empty($updates['ok']) || empty($updates['result'])) {
            return response()->json([
                'success' => true,
                'message' => 'No new updates found in Telegram queue.',
                'processed' => 0,
                'last_offset' => $offset,
            ]);
        }

        $processedCount = 0;
        $maxUpdateId = $offset;

        foreach ($updates['result'] as $up) {
            $upId = $up['update_id'] ?? 0;
            if ($upId > $maxUpdateId) {
                $maxUpdateId = $upId;
            }

            $this->handleUpdate($up);
            $processedCount++;
        }

        // Acknowledge updates by fetching with offset = maxUpdateId + 1
        if ($maxUpdateId > 0) {
            $this->telegram->getUpdates($maxUpdateId + 1, 1, 0);
        }

        return response()->json([
            'success' => true,
            'processed' => $processedCount,
            'last_update_id' => $maxUpdateId,
            'message' => "Successfully processed {$processedCount} Telegram update(s).",
        ]);
    }

    /**
     * Set Webhook API helper.
     */
    public function setWebhook(Request $request)
    {
        $url = $request->input('url') ?: url('/api/telegram/webhook');

        if (!str_starts_with($url, 'https://')) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram Webhook requires an HTTPS URL! For localhost, please use long polling or ngrok/cloudflare tunnel.',
                'current_url' => $url,
            ], 422);
        }

        $res = $this->telegram->setWebhook($url);
        return response()->json($res);
    }

    /**
     * Delete Webhook API helper.
     */
    public function deleteWebhook(Request $request)
    {
        $dropPending = $request->boolean('drop_pending', false);
        $res = $this->telegram->deleteWebhook($dropPending);
        return response()->json($res);
    }

    /**
     * Unlink Telegram for authenticated student from website.
     */
    public function unlinkStudent(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = ($user instanceof Student) 
            ? $user 
            : (Student::find($user->StudentId ?? $user->id) ?? Student::where('UserId', $user->id)->first());

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $chatId = $student->TelegramChatId;
        $student->TelegramChatId = null;
        $student->TelegramUsername = null;
        $student->save();

        // Sync unlink to Google Apps Script (24/7 Bot Cloud Bridge)
        $gasUrl = env('TELEGRAM_GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbydj3645-4Rojs9THlBGD8jSAbpMcu5eUdLEaBCIDUXlNRR6gtVKhWrciD44SxLH565qg/exec');
        if (!empty($gasUrl) && !empty($chatId)) {
            try {
                \Illuminate\Support\Facades\Http::timeout(5)->post($gasUrl, [
                    'action' => 'unlink_student',
                    'chatId' => $chatId,
                ]);
            } catch (\Throwable $gasErr) {}
        }

        return response()->json([
            'success' => true,
            'message' => 'បានផ្តាច់ការភ្ជាប់គណនី Telegram ដោយជោគជ័យ (Telegram unlinked successfully).'
        ]);
    }

    /**
     * Manually link Telegram by Chat ID from website.
     */
    public function manualLinkStudent(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = ($user instanceof Student) 
            ? $user 
            : (Student::find($user->StudentId ?? $user->id) ?? Student::where('UserId', $user->id)->first());

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $chatId = trim($request->input('chatId', ''));
        if (empty($chatId)) {
            return response()->json(['message' => 'សូមបញ្ចូល Telegram Chat ID'], 422);
        }

        if (str_starts_with($chatId, '@')) {
            $student->TelegramUsername = $chatId;
        } else {
            $student->TelegramChatId = $chatId;
        }
        $student->save();

        // Send confirmation test to the linked chat if numerical
        $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
        if (is_numeric($student->TelegramChatId)) {
            $this->telegram->sendMessage($student->TelegramChatId, "🎉 <b>ការភ្ជាប់គណនីបានជោគជ័យ!</b>\n━━━━━━━━━━━━━━━━━━━━\n👤 <b>សិស្ស:</b> {$studentName}\n🆔 <b>អត្តលេខ:</b> <code>{$student->StudentCode}</code>\n━━━━━━━━━━━━━━━━━━━━\n✅ គណនី Telegram របស់អ្នកត្រូវបានភ្ជាប់ជាមួយ OnlinExam ដោយជោគជ័យ។ រាល់ពេលប្រឡងចប់ ពិន្ទុរបស់អ្នកនឹងផ្ញើមកទីនេះស្វ័យប្រវត្តិ!");
        }

        // Sync link to Google Apps Script (24/7 Bot Cloud Bridge)
        $gasUrl = env('TELEGRAM_GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbydj3645-4Rojs9THlBGD8jSAbpMcu5eUdLEaBCIDUXlNRR6gtVKhWrciD44SxLH565qg/exec');
        if (!empty($gasUrl) && !empty($student->TelegramChatId)) {
            try {
                \Illuminate\Support\Facades\Http::timeout(5)->post($gasUrl, [
                    'action' => 'link_student',
                    'chatId' => $student->TelegramChatId,
                    'studentCode' => $student->StudentCode,
                    'studentName' => $studentName,
                ]);
            } catch (\Throwable $gasErr) {
                \Illuminate\Support\Facades\Log::warning('Google Apps Script link sync failed: ' . $gasErr->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'បានភ្ជាប់គណនី Telegram ដោយជោគជ័យ!',
            'telegramChatId' => $student->TelegramChatId,
            'telegramUsername' => $student->TelegramUsername
        ]);
    }

    /**
     * Sync all enrolled students to Google Apps Script (Cloud Roster)
     * so that /link [CODE] automatically validates against the real student names.
     */
    public function syncAllStudentsToGas()
    {
        $gasUrl = env('TELEGRAM_GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbydj3645-4Rojs9THlBGD8jSAbpMcu5eUdLEaBCIDUXlNRR6gtVKhWrciD44SxLH565qg/exec');
        if (empty($gasUrl)) {
            return response()->json(['success' => false, 'message' => 'TELEGRAM_GOOGLE_SCRIPT_URL is not set'], 400);
        }

        try {
            $students = Student::all();
            $roster = [];
            foreach ($students as $s) {
                $name = trim(($s->FirstName ?? '') . ' ' . ($s->LastName ?? ''));
                if (empty($name)) {
                    $name = 'Student #' . $s->StudentId;
                }
                $code = strtoupper(trim($s->StudentCode ?: ('RTC-' . $s->StudentId)));
                $roster[] = [
                    'code' => $code,
                    'name' => $name,
                    'chatId' => $s->TelegramChatId ?? '',
                    'phone' => $s->Phone ?? '',
                ];
            }

            $response = \Illuminate\Support\Facades\Http::timeout(15)->post($gasUrl, [
                'action' => 'sync_roster',
                'students' => $roster,
            ]);

            return response()->json([
                'success' => true,
                'message' => "បាន Sync បញ្ជីសិស្សសរុប " . count($roster) . " នាក់ទៅកាន់ Telegram Bot ដោយជោគជ័យ!",
                'count' => count($roster),
                'gas_response' => $response->json() ?: $response->body(),
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('syncAllStudentsToGas error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync បរាជ័យ: ' . $e->getMessage(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Direct incoming link notification from Telegram Bot / Google Apps Script.
     * Updates tblstudent.TelegramChatId in the MySQL database in real-time.
     */
    public function syncLinkDirect(Request $request)
    {
        $code = trim($request->get('studentCode', $request->input('studentCode', $request->input('student_code', ''))));
        $chatId = trim($request->get('chatId', $request->input('chatId', $request->input('chat_id', ''))));
        $username = trim($request->get('username', $request->input('username', '')));

        if (empty($code) || empty($chatId)) {
            return response()->json(['success' => false, 'message' => 'Missing studentCode or chatId'], 422);
        }

        $student = $this->findStudentByQuery($code);

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found in database'], 404);
        }

        $student->TelegramChatId = $chatId;
        if (!empty($username)) {
            $student->TelegramUsername = str_starts_with($username, '@') ? $username : '@' . $username;
        }
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Student telegram chat ID updated successfully in database',
            'student' => [
                'id' => $student->StudentId,
                'code' => $student->StudentCode,
                'name' => trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? '')),
                'chatId' => $student->TelegramChatId,
                'username' => $student->TelegramUsername,
            ]
        ]);
    }

    /**
     * Direct incoming unlink notification from Telegram Bot / Google Apps Script.
     */
    public function syncUnlinkDirect(Request $request)
    {
        $chatId = trim($request->get('chatId', $request->input('chatId', $request->input('chat_id', ''))));
        if (!empty($chatId)) {
            Student::where('TelegramChatId', $chatId)->update([
                'TelegramChatId' => null,
                'TelegramUsername' => null,
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Student unlinked successfully from database']);
    }
}

