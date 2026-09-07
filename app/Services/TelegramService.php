<?php

namespace App\Services;

use App\Models\StudentSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $token;
    protected ?string $defaultChatId;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token') ?: (env('TELEGRAM_BOT_TOKEN') ?: '8870474657:AAFe-VKKQku4dCGYnCEH1mL2bopzS4pxJQs');
        $this->defaultChatId = config('services.telegram.admin_chat_id') ?: (env('TELEGRAM_ADMIN_CHAT_ID') ?: '7752474480');
    }

    /**
     * Check if Telegram Bot token is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Format exam duration as clean integer minutes and seconds without any decimals.
     * Example: "7 នាទី 16 វិនាទី" or "0 នាទី 9 វិនាទី"
     */
    public function formatDuration($startedAt, $completedAt): string
    {
        if (!$startedAt || !$completedAt) {
            return 'N/A';
        }

        try {
            $startTs = $startedAt instanceof \DateTimeInterface 
                ? $startedAt->getTimestamp() 
                : strtotime((string) $startedAt);
            $endTs = $completedAt instanceof \DateTimeInterface 
                ? $completedAt->getTimestamp() 
                : strtotime((string) $completedAt);

            $totalSecs = (int) max(0, abs($endTs - $startTs));
            $mins = (int) floor($totalSecs / 60);
            $secs = (int) ($totalSecs % 60);

            if ($secs === 0 && $mins > 0) {
                return "{$mins} នាទី";
            }

            return "{$mins} នាទី {$secs} វិនាទី";
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    /**
     * Send a general text message via Telegram.
     */
    public function sendMessage(?string $chatId, string $text, array $extra = []): array
    {
        $targetChatId = $chatId ?: $this->defaultChatId;

        // Prevent sending real Telegram messages when running automated tests (e.g. php artisan test)
        if (app()->environment('testing')) {
            return ['ok' => true, 'description' => 'Suppressed in testing environment'];
        }

        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token is not configured.'];
        }

        if (empty($targetChatId)) {
            return ['ok' => false, 'description' => 'Target Chat ID is missing.'];
        }

        try {
            $payload = array_merge([
                'chat_id' => $targetChatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $extra);

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/sendMessage", $payload);

            return $response->json() ?? ['ok' => false, 'description' => 'Empty response from Telegram API.'];
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Push payload to Google Apps Script 24/7 Cloud Bridge.
     * This offloads bot roster and sheet logging asynchronously.
     */
    public function pushToGoogleAppsScript(array $payload): bool
    {
        $gasUrl = config('services.telegram.google_script_url', env('TELEGRAM_GOOGLE_SCRIPT_URL'));
        if (empty($gasUrl)) {
            return false;
        }

        try {
            $response = Http::timeout(4)->post($gasUrl, $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('TelegramService pushToGoogleAppsScript warning: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send instant alert when a student submits an exam.
     * Sends directly to Student's Telegram and Admin's Telegram in real-time,
     * plus backs up data to Google Apps Script.
     */
    public function sendExamSubmissionAlert(StudentSubmission $submission, ?string $chatId = null): array
    {
        try {
            $submission->loadMissing(['student', 'test']);

            $student = $submission->student;
            $test = $submission->test;

            $studentName = $student 
                ? trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? '')) 
                : 'សិស្ស';
            $studentCode = $student ? ($student->StudentCode ?? ('ID #' . $student->StudentId)) : 'N/A';
            $phone = $student && $student->Phone ? $student->Phone : 'N/A';
            
            $testName = $test ? $test->TestName : ('Test #' . $submission->TestId);
            $totalMarks = $test ? (int) $test->TotalMarks : 100;
            $score = (float) ($submission->Score ?? 0);
            $correct = (int) ($submission->TotalCorrect ?? 0);
            $interruptions = (int) ($submission->Interruptions ?? 0);

            // Duration calculation
            $durationStr = $this->formatDuration($submission->StartedAt, $submission->CompletedAt);
            $passed = ($score >= ($totalMarks / 2));

            $hostingUrl = env('TELEGRAM_HOSTING_URL') ?: config('app.url');
            if (empty($hostingUrl) || str_contains($hostingUrl, 'localhost')) {
                $hostingUrl = 'https://onlin-exam.vercel.app';
            }
            $resultUrl = rtrim($hostingUrl, '/') . "/student/results/{$submission->SubmissionId}";

            $studentChatId = $chatId ?: ($student?->TelegramChatId ?? null);
            $adminChatId = $this->defaultChatId;

            $statusEmoji = $passed ? '🎉 <b>ជាប់ជាស្ថាពរ (PASSED)</b>' : '⚠️ <b>មិនទាន់ជាប់ (FAILED)</b>';
            $interruptionAlert = $interruptions > 0 
                ? "⚠️ <b>ប្ដូរផ្ទាំង/ចាកចេញ (Blur):</b> <code>{$interruptions} ដង</code>" 
                : "🛡️ <b>ប្ដូរផ្ទាំង/ចាកចេញ:</b> គ្មាន (អនុលោមតាមវិន័យល្អ)";

            $resultKeyboard = [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '📊 ពិនិត្យលទ្ធផលលម្អិត (View Detail)', 'url' => $resultUrl],
                        ],
                        [
                            ['text' => '🌐 ចូលគេហទំព័រប្រឡង (Student Portal)', 'url' => rtrim($hostingUrl, '/') . '/student'],
                        ],
                    ]
                ]
            ];

            $studentSent = false;
            $adminSent = false;

            // ── 1. Send Direct Score Card to Student (if connected) ──────
            if (!empty($studentChatId)) {
                $studentMessage = "🎉 <b>អបអរសាទរ! លទ្ធផលប្រឡងរបស់អ្នក (Your Exam Result)</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "👤 <b>សិស្ស:</b> <b>{$studentName}</b>\n"
                    . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                    . "📝 <b>វិញ្ញាសា:</b> <b>{$testName}</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "🎯 <b>ពិន្ទុទទួលបាន:</b> <b>{$score} / {$totalMarks}</b>\n"
                    . "📊 <b>លទ្ធផល:</b> {$statusEmoji}\n"
                    . "✅ <b>ឆ្លើយត្រូវ:</b> {$correct} សំណួរ\n"
                    . "⏱️ <b>រយៈពេលប្រឡង:</b> {$durationStr}\n"
                    . "🕒 <b>កាលបរិច្ឆេទ:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-m-Y H:i:s') . "\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "👉 <i>អ្នកអាចចុចប៊ូតុងខាងក្រោមដើម្បីពិនិត្យមើលចម្លើយលម្អិត៖</i>";

                $studentRes = $this->sendMessage($studentChatId, $studentMessage, $resultKeyboard);
                $studentSent = !empty($studentRes['ok']);
            }

            // ── 2. Send Alert Notification to Admin (Teacher) ───────────
            if (!empty($adminChatId) && (string)$adminChatId !== (string)$studentChatId) {
                $adminMessage = "🎓 <b>សិស្សបានបញ្ចប់ការប្រឡង (Exam Submitted)</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "👤 <b>សិស្ស:</b> <b>{$studentName}</b>\n"
                    . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                    . "📞 <b>ទូរស័ព្ទ:</b> {$phone}\n"
                    . "📝 <b>វិញ្ញាសា:</b> <b>{$testName}</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "🎯 <b>ពិន្ទុទទួលបាន:</b> <b>{$score} / {$totalMarks}</b> ({$statusEmoji})\n"
                    . "✅ <b>ឆ្លើយត្រូវ:</b> {$correct} សំណួរ\n"
                    . "⏱️ <b>រយៈពេលប្រឡង:</b> {$durationStr}\n"
                    . "{$interruptionAlert}\n"
                    . "🕒 <b>ម៉ោងបញ្ជូន:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-m-Y H:i:s') . "\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "🌐 <i>ប្រព័ន្ធប្រឡង OnlinExam</i>";

                $adminRes = $this->sendMessage($adminChatId, $adminMessage, $resultKeyboard);
                $adminSent = !empty($adminRes['ok']);
            } elseif (!empty($adminChatId) && empty($studentChatId)) {
                // If student has NOT linked Telegram, notify admin with notice
                $adminMessage = "🎓 <b>សិស្សបានបញ្ចប់ការប្រឡង (Exam Submitted)</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "👤 <b>សិស្ស:</b> <b>{$studentName}</b>\n"
                    . "🆔 <b>អត្តលេខ:</b> <code>{$studentCode}</code>\n"
                    . "📞 <b>ទូរស័ព្ទ:</b> {$phone}\n"
                    . "📝 <b>វិញ្ញាសា:</b> <b>{$testName}</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "🎯 <b>ពិន្ទុទទួលបាន:</b> <b>{$score} / {$totalMarks}</b> ({$statusEmoji})\n"
                    . "✅ <b>ឆ្លើយត្រូវ:</b> {$correct} សំណួរ\n"
                    . "⏱️ <b>រយៈពេលប្រឡង:</b> {$durationStr}\n"
                    . "{$interruptionAlert}\n"
                    . "⚠️ <i>(សិស្សមិនទាន់បានភ្ជាប់ Telegram ផ្ទាល់ខ្លួនទេ)</i>\n"
                    . "🕒 <b>ម៉ោងបញ្ជូន:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-m-Y H:i:s') . "\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "🌐 <i>ប្រព័ន្ធប្រឡង OnlinExam</i>";

                $adminRes = $this->sendMessage($adminChatId, $adminMessage, $resultKeyboard);
                $adminSent = !empty($adminRes['ok']);
            }

            // ── 3. Asynchronously push to Google Apps Script as backup ──
            $gasPayload = [
                'action' => 'save_result',
                'studentCode' => $studentCode,
                'studentName' => $studentName,
                'phone' => $phone,
                'testName' => $testName,
                'score' => $score,
                'total' => $totalMarks,
                'correct' => $correct,
                'duration' => $durationStr,
                'passed' => $passed ? '✅ ជាប់' : '❌ ធ្លាក់',
                'interruptions' => $interruptions,
                'date' => now()->setTimezone('Asia/Phnom_Penh')->format('d/m/Y H:i'),
                'submittedAt' => now()->setTimezone('Asia/Phnom_Penh')->format('d-m-Y H:i:s'),
                'resultUrl' => $resultUrl,
                'chatId' => $studentChatId ?: null,
                'adminChatId' => $adminChatId,
            ];
            $this->pushToGoogleAppsScript($gasPayload);

            return [
                'ok' => $studentSent || $adminSent,
                'studentSent' => $studentSent,
                'adminSent' => $adminSent,
                'studentChatId' => $studentChatId,
            ];
        } catch (\Throwable $e) {
            Log::error('sendExamSubmissionAlert error: ' . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Send personalized exam result directly to student's Telegram.
     * Delegates to sendExamSubmissionAlert to leverage Google Apps Script cloud bridge.
     */
    public function sendStudentResultNotification(StudentSubmission $submission, ?string $chatId = null): array
    {
        return $this->sendExamSubmissionAlert($submission, $chatId);
    }

    /**
     * Send real-time security warning if a student repeatedly leaves the exam tab.
     */
    public function sendInterruptionAlert(StudentSubmission $submission, int $count, ?string $chatId = null): array
    {
        try {
            $submission->loadMissing(['student', 'test']);
            $student = $submission->student;
            $test = $submission->test;

            $studentName = $student 
                ? trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? '')) 
                : 'Unknown';
            $studentCode = $student ? ($student->StudentCode ?? ('ID #' . $student->StudentId)) : 'N/A';
            $testName = $test ? $test->TestName : ('Test #' . $submission->TestId);
            $timeStr = now()->setTimezone('Asia/Phnom_Penh')->format('d-m-Y H:i:s');

            // Push to Google Apps Script cloud bridge
            $gasPayload = [
                'action' => 'interruption_alert',
                'studentCode' => $studentCode,
                'studentName' => $studentName,
                'testName' => $testName,
                'count' => $count,
                'time' => $timeStr,
                'adminChatId' => $chatId ?: $this->defaultChatId,
            ];

            if ($this->pushToGoogleAppsScript($gasPayload)) {
                return ['ok' => true, 'description' => 'Interruption alert dispatched to Google Apps Script.'];
            }

            // Fallback: Direct message
            $message = "🚨 <b>ការព្រមានសុវត្ថិភាព (Exam Interruption Alert)</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "សិស្សបានចាកចេញពីផ្ទាំងប្រឡង (Switch Tab / Focus Loss)!\n\n"
                . "👤 <b>សិស្ស:</b> <b>{$studentName}</b> (<code>{$studentCode}</code>)\n"
                . "📝 <b>វិញ្ញាសា:</b> {$testName}\n"
                . "⚠️ <b>ចំនួនដងបំពាន:</b> <code>{$count} ដង</code>\n"
                . "🕒 <b>ពេលវេលា:</b> {$timeStr}\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "<i>សូម Admin ឬលោកគ្រូ/អ្នកគ្រូ មេត្តាពិនិត្យ Live Monitor!</i>";

            return $this->sendMessage($chatId, $message);
        } catch (\Throwable $e) {
            Log::error('sendInterruptionAlert error: ' . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get updates to discover incoming chats and Chat IDs (supports long polling).
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not set.'];
        }

        try {
            $params = [];
            if ($offset > 0) {
                $params['offset'] = $offset;
            }
            if ($limit > 0) {
                $params['limit'] = $limit;
            }
            if ($timeout > 0) {
                $params['timeout'] = $timeout;
            }

            $response = Http::timeout($timeout + 15)->get("https://api.telegram.org/bot{$this->token}/getUpdates", $params);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get bot info.
     */
    public function getMe(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not set.'];
        }

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getMe");
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get Webhook Info.
     */
    public function getWebhookInfo(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not set.'];
        }

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getWebhookInfo");
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Set Webhook URL.
     */
    public function setWebhook(string $url): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not set.'];
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/setWebhook", [
                'url' => $url,
            ]);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Delete Webhook (switch back to long polling).
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not set.'];
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/deleteWebhook", [
                'drop_pending_updates' => $dropPendingUpdates,
            ]);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
