<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramBotController;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Run a single poll pass and exit} {--timeout=15 : Long polling timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Telegram API for incoming messages and execute bot commands (ideal for local development)';

    public function handle(TelegramService $telegram, TelegramBotController $controller): int
    {
        $this->info("🤖 Starting Telegram Long Polling for @onlinexam_bot...");

        $webhookInfo = $telegram->getWebhookInfo();
        if (!empty($webhookInfo['result']['url'])) {
            $this->warn("⚠️ Active webhook detected: " . $webhookInfo['result']['url']);
            $this->info("ℹ️ Telegram Bot is currently running 24/7 on Google Cloud Bridge.");
            $this->line("Skipping webhook deletion to keep 24/7 Telegram Bot alive.");
        }

        $offset = 0;
        $runOnce = $this->option('once');
        $timeout = (int) $this->option('timeout');

        do {
            try {
                $response = $telegram->getUpdates($offset, 100, $timeout);

                if (!empty($response['ok']) && !empty($response['result'])) {
                    foreach ($response['result'] as $update) {
                        $updateId = $update['update_id'] ?? 0;
                        $offset = max($offset, $updateId + 1);

                        $msg = $update['message'] ?? $update['edited_message'] ?? null;
                        $text = $msg['text'] ?? ($msg['caption'] ?? '(non-text)');
                        $user = $msg['from']['first_name'] ?? 'User';
                        $chatId = $msg['chat']['id'] ?? 'unknown';

                        $this->line("📩 [{$chatId}] {$user}: {$text}");

                        $result = $controller->handleUpdate($update);
                        $this->info("   ↳ Handled: " . json_encode($result, JSON_UNESCAPED_UNICODE));
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Polling error: " . $e->getMessage());
                sleep(2);
            }

            if ($runOnce) {
                break;
            }

            usleep(250000); // 250ms interval between requests
        } while (true);

        return 0;
    }
}
