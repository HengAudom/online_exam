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
     * Core update handler (shared by Webhook and Long Polling).
     */
    public function handleUpdate(array $update): array
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (!$message || !isset($message['chat']['id'])) {
            return ['status' => 'ignored', 'reason' => 'No chat message found in update'];
        }

        $chatId = (string) $message['chat']['id'];
        $text = trim($message['text'] ?? ($message['caption'] ?? ''));
        $firstName = $message['from']['first_name'] ?? 'User';

        $username = $message['from']['username'] ?? null;
        $cleanUsername = $username ? '@' . ltrim($username, '@') : null;

        if (empty($text)) {
            return ['status' => 'ignored', 'reason' => 'Empty text'];
        }

        // ── 1. Check for Account Linking: /start link_CODE or /link CODE ──
        $linkTarget = null;
        if (preg_match('/^\/start(?:@\w+)?\s+link_([a-zA-Z0-9_\-]+)/i', $text, $matches)) {
            $linkTarget = $matches[1];
        } elseif (preg_match('/^\/start(?:@\w+)?\s+([a-zA-Z0-9_\-]+)/i', $text, $matches)) {
            // Also support direct /start CODE (e.g. /start RTC-XXXX-XXXXX)
            $linkTarget = $matches[1];
        } elseif (preg_match('/^\/link(?:@\w+)?\s+([a-zA-Z0-9_\-]+)/i', $text, $matches)) {
            $linkTarget = $matches[1];
        }

        if ($linkTarget) {
            $student = Student::where('StudentCode', $linkTarget)
                ->orWhere('StudentId', $linkTarget)
                ->orWhere('Phone', $linkTarget)
                ->first();

            if (!$student) {
                $this->telegram->sendMessage($chatId, "❌ <b>មិនអាចភ្ជាប់គណនីបានទេ!</b>\n\nរកមិនឃើញទិន្នន័យសិស្សដែលមានលេខកូដ <code>{$linkTarget}</code> ក្នុងប្រព័ន្ធឡើយ។ សូមពិនិត្យលេខកូដសិស្សរបស់អ្នកឡើងវិញ ឬទាក់ទងគ្រូ/Admin។");
                return ['status' => 'ok', 'action' => 'link_failed', 'chat_id' => $chatId];
            }

            $student->TelegramChatId = $chatId;
            $student->TelegramUsername = $cleanUsername;
            $student->save();

            $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
            $studentCode = $student->StudentCode ?: ('ID #' . $student->StudentId);

            $msg = "🎉 <b>ការភ្ជាប់គណនីបានជោគជ័យ! (Telegram Connected)</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "👤 <b>សិស្ស:</b> <b>{$studentName}</b>\n"
                . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                . "📱 <b>Telegram ID:</b> <code>{$chatId}</code>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "✅ គណនី Telegram របស់អ្នកត្រូវបានភ្ជាប់ជាមួយប្រព័ន្ធប្រឡង OnlinExam រួចរាល់ហើយ!\n\n"
                . "🎓 ចាប់ពីពេលនេះតទៅ រាល់ពេលអ្នកបញ្ចប់ការប្រឡង (Submit Exam) ប្រព័ន្ធនឹងផ្ញើសារពិន្ទុ និងលទ្ធផលប្រឡងមកកាន់ទីនេះដោយស្វ័យប្រវត្តិ។\n\n"
                . "👉 អ្នកអាចវាយ <code>/myresult</code> គ្រប់ពេលវេលាដើម្បីពិនិត្យលទ្ធផលប្រឡងកន្លងមក។";

            $this->telegram->sendMessage($chatId, $msg);
            return ['status' => 'ok', 'action' => 'linked', 'student_id' => $student->StudentId];
        }

        // ── Command: /link without arguments ──
        if (preg_match('/^\/link(?:@\w+)?$/i', $text)) {
            $this->telegram->sendMessage($chatId, "ℹ️ <b>របៀបភ្ជាប់គណនី Telegram:</b>\n━━━━━━━━━━━━━━━━━━━━\n👉 សូមវាយពាក្យបញ្ជា <code>/link [លេខកូដសិស្ស]</code>\n<i>(ឧទាហរណ៍៖ <code>/link RTC-XXXX-XXXXX</code>)</i>\n\n💡 ឬចូលទៅកាន់គេហទំព័រប្រឡង (Student Portal) រួចចុចលើប៊ូតុង <b>Connect Telegram</b>។");
            return ['status' => 'ok', 'action' => 'link_help_sent'];
        }

        // ── 2. Command: /unlink (Case-insensitive) ──
        if (preg_match('/^\/unlink(?:@\w+)?/i', $text)) {
            $students = Student::where('TelegramChatId', $chatId)->get();
            if ($students->isEmpty()) {
                $this->telegram->sendMessage($chatId, "ℹ️ គណនី Telegram របស់អ្នកមិនទាន់បានភ្ជាប់ជាមួយសិស្សណាម្នាក់នៅឡើយទេ។");
                return ['status' => 'ok', 'action' => 'not_linked'];
            }

            foreach ($students as $s) {
                $s->TelegramChatId = null;
                $s->TelegramUsername = null;
                $s->save();
            }

            $this->telegram->sendMessage($chatId, "✂️ <b>គណនី Telegram របស់អ្នកត្រូវបានផ្តាច់ការភ្ជាប់ (Unlinked) ពីប្រព័ន្ធប្រឡងរួចរាល់ហើយ។</b>\n\nដើម្បីភ្ជាប់ឡើងវិញ សូមវាយ: <code>/link អត្តលេខសិស្ស</code>");
            return ['status' => 'ok', 'action' => 'unlinked'];
        }

        // ── 3. Command: /start (Case-insensitive: /start, /Start, /START, /start@bot) ──
        if (preg_match('/^\/start(?:@\w+)?$/i', $text)) {
            $welcomeText = "👋 <b>សួស្ដី {$firstName}! សូមស្វាគមន៍មកកាន់ប្រព័ន្ធប្រឡង OnlinExam!</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "🤖 ខ្ញុំជា Bot សម្រាប់ជំនួយការប្រឡង ផ្ញើសារដំណឹង និងលទ្ធផលប្រឡងដោយស្វ័យប្រវត្តិ។\n\n"
                . "📌 <b>មុខងារ និងពាក្យបញ្ជា (Commands):</b>\n"
                . "👉 <code>/myresult</code> - ពិនិត្យលទ្ធផលប្រឡងរបស់អ្នក (ឬ <code>/myresult [លេខកូដសិស្ស]</code>)\n"
                . "👉 <code>/link [លេខកូដសិស្ស]</code> - ភ្ជាប់គណនី Telegram ដើម្បីទទួលពិន្ទុភ្លាមៗ\n"
                . "👉 <code>/unlink</code> - ផ្តាច់ការភ្ជាប់គណនី Telegram\n"
                . "👉 <code>/myid</code> - មើលលេខ Student ID របស់អ្នក\n"
                . "👉 <code>/help</code> - មើលការណែនាំជំនួយ\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "💡 <b>របៀបភ្ជាប់គណនី:</b> ចូលទៅកាន់គេហទំព័រប្រឡង (Student Portal) រួចចុច <b>Connect Telegram</b> ឬវាយពាក្យ <code>/link [លេខកូដសិស្ស]</code> (ឧទាហរណ៍៖ <code>/link RTC-XXXX-XXXXX</code>) នៅទីនេះ។";

            $appUrl = config('app.url', url('/'));
            $inlineKeyboard = [];

            // Telegram Mini App web_app only accepts HTTPS URLs
            if (str_starts_with($appUrl, 'https://')) {
                $inlineKeyboard[] = [
                    ['text' => '🚀 បើកប្រព័ន្ធប្រឡង (Open Exam)', 'web_app' => ['url' => $appUrl]]
                ];
            } elseif (!str_contains($appUrl, 'localhost')) {
                $inlineKeyboard[] = [
                    ['text' => '🚀 ចូលគេហទំព័រប្រឡង (Visit Website)', 'url' => $appUrl]
                ];
            }

            $adminUser = config('services.telegram.admin_username', env('TELEGRAM_ADMIN_USERNAME', 'DomAi1'));
            $adminUrl = 'https://t.me/' . ltrim($adminUser, '@');

            $inlineKeyboard[] = [
                ['text' => '👨‍💼 ទំនាក់ទំនង Admin', 'url' => $adminUrl]
            ];

            $extra = [
                'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard])
            ];

            $this->telegram->sendMessage($chatId, $welcomeText, $extra);
            return ['status' => 'ok', 'action' => 'start_responded', 'chat_id' => $chatId];
        }

        // ── 4. Command: /myid (Show Student ID) ──
        if (preg_match('/^\/myid(?:@\w+)?/i', $text)) {
            $student = Student::where('TelegramChatId', $chatId)->first();
            if ($student) {
                $studentCode = $student->StudentCode ?: ('ID #' . $student->StudentId);
                $studentName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
                $nameLine = $studentName ? "\n👤 <b>ឈ្មោះ:</b> <b>{$studentName}</b>" : "";
                $this->telegram->sendMessage($chatId, "🆔 <b>Student ID របស់អ្នកគឺ:</b> <code>{$studentCode}</code>{$nameLine}");
            } else {
                $this->telegram->sendMessage($chatId, "⚠️ <b>គណនី Telegram របស់អ្នកមិនទាន់បានភ្ជាប់ជាមួយសិស្សណាម្នាក់ឡើយ!</b>\n\n👉 សូមវាយពាក្យបញ្ជា <code>/link [លេខកូដសិស្ស]</code> (ឧទាហរណ៍៖ <code>/link RTC-2026-12345</code>) ដើម្បីភ្ជាប់គណនីរបស់អ្នក\n(Telegram Chat ID: <code>{$chatId}</code>)");
            }
            return ['status' => 'ok', 'action' => 'myid_sent'];
        }

        // ── Command: /chatid (Show Telegram Chat ID) ──
        if (preg_match('/^\/chatid(?:@\w+)?/i', $text)) {
            $this->telegram->sendMessage($chatId, "🆔 <b>Telegram Chat ID របស់អ្នកគឺ:</b> <code>{$chatId}</code>");
            return ['status' => 'ok', 'action' => 'chatid_sent'];
        }

        // ── 5. Command: /help ──
        if (preg_match('/^\/help(?:@\w+)?/i', $text)) {
            $adminUser = config('services.telegram.admin_username', env('TELEGRAM_ADMIN_USERNAME', 'DomAi1'));
            $adminUrl = 'https://t.me/' . ltrim($adminUser, '@');

            $helpText = "ℹ️ <b>ការណែនាំអំពីការប្រើប្រាស់ OnlinExam Bot:</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "1. <b>ភ្ជាប់គណនី:</b> វាយ <code>/link &lt;លេខកូដសិស្ស&gt;</code> (ឧទាហរណ៍៖ <code>/link RTC-2026-12345</code>)\n"
                . "2. <b>ឆែកពិន្ទុ:</b> វាយ <code>/myresult</code> (ឬ <code>/myresult RTC-2026-12345</code>) ដើម្បីមើលពិន្ទុវិញ្ញាសាដែលបានប្រឡង\n"
                . "3. <b>ផ្ញើសារដំណឹង:</b> រាល់ពេលអ្នកចុច Submit ការប្រឡង ប្រព័ន្ធនឹងផ្ញើពិន្ទុមកកាន់ទីនេះភ្លាមៗ\n"
                . "4. <b>ទំនាក់ទំនង Admin:</b> @{$adminUser}";

            $extra = [
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '👨‍💼 ទំនាក់ទំនង Admin', 'url' => $adminUrl]]
                    ]
                ])
            ];

            $this->telegram->sendMessage($chatId, $helpText, $extra);
            return ['status' => 'ok', 'action' => 'help_sent'];
        }

        // ── 6. Command: /myresult ──
        if (preg_match('/^\/myresult(?:@\w+)?(?:\s+(.+))?$/i', $text, $m)) {
            $queryCode = trim($m[1] ?? '');

            // If user didn't provide code, try to find student by their Telegram Chat ID!
            if (empty($queryCode)) {
                $student = Student::where('TelegramChatId', $chatId)->first();
            } else {
                $student = Student::where('StudentCode', $queryCode)
                    ->orWhere('Phone', $queryCode)
                    ->orWhere('StudentId', $queryCode)
                    ->first();
            }

            if (!$student) {
                if (!empty($queryCode)) {
                    $this->telegram->sendMessage($chatId, "❌ <b>រកមិនឃើញទិន្នន័យសិស្សឡើយ!</b>\n\nមិនមានសិស្សដែលមានលេខកូដ <code>{$queryCode}</code> ក្នុងប្រព័ន្ធទេ។ សូមពិនិត្យលេខកូដសិស្សរបស់អ្នកឡើងវិញ ឬវាយ <code>/help</code> សម្រាប់ជំនួយ។");
                    return ['status' => 'ok', 'action' => 'student_not_found'];
                }

                $unlinkedMsg = "⚠️ <b>គណនី Telegram របស់អ្នកមិនទាន់បានភ្ជាប់ជាមួយសិស្សណាម្នាក់ទេ!</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "ដើម្បីអាចពិនិត្យមើលពិន្ទុ និងលទ្ធផលប្រឡងបាន សូមជ្រើសរើសជម្រើសខាងក្រោម៖\n\n"
                    . "1️⃣ <b>ភ្ជាប់គណនី (ដើម្បីចុច /myresult មើលពិន្ទុភ្លាមៗ):</b>\n"
                    . "👉 វាយពាក្យ <code>/link [លេខកូដសិស្ស]</code>\n"
                    . "<i>(ឧទាហរណ៍៖ <code>/link RTC-2026-12345</code>)</i>\n\n"
                    . "2️⃣ <b>ឬឆែកមើលពិន្ទុដោយវាយលេខកូដផ្ទាល់:</b>\n"
                    . "👉 វាយ <code>/myresult [លេខកូដសិស្ស]</code>\n"
                    . "<i>(ឧទាហរណ៍៖ <code>/myresult RTC-2026-12345</code>)</i>\n"
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
                $this->telegram->sendMessage($chatId, "ℹ️ សិស្ស <b>{$studentName}</b> (<code>{$studentCode}</code>) មិនទាន់មានប្រវត្តិបញ្ចប់ការប្រឡងណាមួយនៅឡើយទេ។");
                return ['status' => 'ok', 'action' => 'no_submissions'];
            }

            $msg = "📊 <b>លទ្ធផលប្រឡងរបស់: {$studentName}</b>\n"
                . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n";

            foreach ($submissions as $idx => $sub) {
                $testName = $sub->test->TestName ?? 'វិញ្ញាសា';
                $score = $sub->Score ?? 0;
                $total = $sub->test->TotalMarks ?? 100;
                $passed = $score >= ($total / 2) ? '✅ ជាប់' : '❌ ធ្លាក់';
                $date = $sub->CompletedAt ? $sub->CompletedAt->format('d/m/Y H:i') : '';

                $num = $idx + 1;
                $msg .= "{$num}. 📝 <b>វិញ្ញាសា:</b> <b>{$testName}</b>\n"
                    . "🎯 <b>ពិន្ទុ:</b> <b>{$score}/{$total}</b> ({$passed})\n"
                    . "📅 <b>កាលបរិច្ឆេទ:</b> {$date}\n\n";
            }

            $msg .= "━━━━━━━━━━━━━━━━━━━━\n🌐 <i>ប្រព័ន្ធប្រឡង OnlinExam</i>";
            $this->telegram->sendMessage($chatId, $msg);
            return ['status' => 'ok', 'action' => 'results_sent'];
        }

        // ── Default response for other messages ──
        $this->telegram->sendMessage($chatId, "🤖 សួស្ដី {$firstName}! ខ្ញុំមិនទាន់ស្គាល់ពាក្យបញ្ជានេះទេ។\n\n👉 សូមចុច ឬវាយ <code>/start</code> ដើម្បីបើកមើលមុខងារទាំងអស់\n👉 វាយ <code>/help</code> ដើម្បីមើលការណែនាំ\n👉 វាយ <code>/myid</code> ដើម្បីមើល Student ID របស់អ្នក\n👉 វាយ <code>/chatid</code> ដើម្បីមើល Telegram Chat ID");
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

        $student->TelegramChatId = null;
        $student->TelegramUsername = null;
        $student->save();

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

        $student = Student::where('StudentCode', $code)
            ->orWhere('StudentId', $code)
            ->orWhere('Phone', $code)
            ->first();

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

