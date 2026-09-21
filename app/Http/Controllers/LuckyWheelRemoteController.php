<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LuckyWheelRemoteController extends Controller
{
    /**
     * Store key-value in database cache table directly (guaranteed persistent across serverless lambdas).
     */
    protected function dbPut(string $key, $value, int $ttlSeconds = 43200): void
    {
        try {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            DB::table('cache')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $encoded,
                    'expiration' => now()->timestamp + $ttlSeconds
                ]
            );
        } catch (\Throwable $e) {
            try {
                Cache::put($key, $value, $ttlSeconds);
            } catch (\Throwable $ex) {}
        }
    }

    /**
     * Retrieve key-value from database cache table directly.
     */
    protected function dbGet(string $key, $default = null)
    {
        try {
            $record = DB::table('cache')
                ->where('key', $key)
                ->where('expiration', '>', now()->timestamp)
                ->first();

            if ($record && isset($record->value)) {
                $decoded = json_decode($record->value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                // Fallback for legacy serialized cache
                $unserialized = @unserialize($record->value);
                if ($unserialized !== false || $record->value === 'b:0;') {
                    return $unserialized;
                }
                return $record->value;
            }
        } catch (\Throwable $e) {
            try {
                return Cache::get($key, $default);
            } catch (\Throwable $ex) {}
        }

        return $default;
    }

    /**
     * Check existence of valid non-expired key in database cache table.
     */
    protected function dbHas(string $key): bool
    {
        try {
            return DB::table('cache')
                ->where('key', $key)
                ->where('expiration', '>', now()->timestamp)
                ->exists();
        } catch (\Throwable $e) {
            try {
                return Cache::has($key);
            } catch (\Throwable $ex) {
                return false;
            }
        }
    }

    /**
     * Create or retrieve a Game Room PIN for remote pairing.
     */
    public function createOrGetRoom(Request $request)
    {
        $requestedPin = $request->input('room') ?: $request->query('room');

        if ($requestedPin && strlen((string)$requestedPin) === 4) {
            $pin = (string) $requestedPin;
            if ($this->dbHas("wheel_room_{$pin}")) {
                $state = $this->dbGet("wheel_room_{$pin}");
            } else {
                $state = [
                    'view' => 'SETUP_VIEW',
                    'currentWord' => '',
                    'currentExplainer' => '',
                    'currentScore' => 0,
                    'wordsPerRound' => 5,
                    'currentWordIndex' => 0,
                    'timerSeconds' => 60,
                    'isTimerPaused' => false,
                    'isSpinning' => false,
                    'isWordVisible' => true,
                    'updatedAt' => now()->timestamp,
                ];
                $this->dbPut("wheel_room_{$pin}", $state, 43200);
            }
        } else {
            // Generate unique 4-digit PIN between 1000 and 9999
            do {
                $pin = (string) random_int(1000, 9999);
            } while ($this->dbHas("wheel_room_{$pin}"));

            $state = [
                'view' => 'SETUP_VIEW',
                'currentWord' => '',
                'currentExplainer' => '',
                'currentScore' => 0,
                'wordsPerRound' => 5,
                'currentWordIndex' => 0,
                'timerSeconds' => 60,
                'isTimerPaused' => false,
                'isSpinning' => false,
                'isWordVisible' => true,
                'updatedAt' => now()->timestamp,
            ];

            $this->dbPut("wheel_room_{$pin}", $state, 43200);
        }

        $this->dbPut("wheel_host_active_{$pin}", true, 45);

        return response()->json([
            'success' => true,
            'room' => $pin,
            'state' => $state,
        ]);
    }

    /**
     * Main screen updates game state to persistent cache.
     */
    public function syncState(Request $request)
    {
        $room = $request->input('room') ?: $request->query('room');
        $state = $request->input('state');

        if (!$room || !is_array($state)) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters'], 422);
        }

        $state['updatedAt'] = now()->timestamp;
        $this->dbPut("wheel_room_{$room}", $state, 43200);
        $this->dbPut("wheel_host_active_{$room}", true, 45);

        return response()->json(['success' => true]);
    }

    /**
     * Phone fetches the current game state.
     */
    public function getState(Request $request)
    {
        $room = $request->query('room') ?: $request->input('room');

        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Room code required'], 422);
        }

        // Lockout protection against brute-forcing room PINs
        $ip = $request->ip();
        $lockoutKey = "wheel_fail_{$ip}";
        $failedAttempts = (int) $this->dbGet($lockoutKey, 0);
        if ($failedAttempts >= 8) {
            return response()->json([
                'success' => false,
                'message' => 'ការព្យាយាមចូលបន្ទប់ខុសច្រើនដងពេក សូមរង់ចាំបន្តិចសិន (Too many failed attempts. Please wait 10 minutes).',
            ], 429);
        }

        // Room MUST exist in the database (created by desktop host)
        if (!$this->dbHas("wheel_room_{$room}")) {
            $this->dbPut($lockoutKey, $failedAttempts + 1, 600);
            return response()->json([
                'success' => false,
                'message' => 'បន្ទប់មិនត្រឹមត្រូវ ឬមិនទាន់បានបើកឡើយ (Room not found or inactive).',
            ], 404);
        }

        if ($failedAttempts > 0) {
            $this->dbPut($lockoutKey, 0, 60);
        }

        $state = $this->dbGet("wheel_room_{$room}");

        return response()->json([
            'success' => true,
            'room' => (string) $room,
            'state' => $state,
        ]);
    }

    /**
     * Phone sends an action command to the main screen.
     */
    public function sendCommand(Request $request)
    {
        $room = $request->input('room') ?: $request->query('room');
        $action = $request->input('action') ?: $request->query('action');
        $payload = $request->input('payload', []);

        if (!$room || !$action) {
            return response()->json(['success' => false, 'message' => 'Room and action are required'], 422);
        }

        if (!$this->dbHas("wheel_room_{$room}")) {
            return response()->json([
                'success' => false,
                'message' => 'បន្ទប់មិនត្រឹមត្រូវ ឬមិនទាន់បានបើកឡើយ (Room not found or inactive).',
            ], 404);
        }

        $cmd = [
            'id' => uniqid('cmd_', true),
            'action' => $action,
            'payload' => $payload,
            'time' => microtime(true),
        ];

        // Store command with 300 second (5 min) expiration
        $this->dbPut("wheel_cmd_{$room}", $cmd, 300);
        $this->dbPut("wheel_phone_active_{$room}", true, 45);

        return response()->json([
            'success' => true,
            'command' => $cmd,
        ]);
    }

    /**
     * Main screen polls for new commands from the phone.
     */
    public function poll(Request $request)
    {
        $room = $request->query('room') ?: $request->input('room');
        $lastCmdId = $request->query('last_cmd_id') ?: $request->input('last_cmd_id');

        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Room code required'], 422);
        }

        $cmd = $this->dbGet("wheel_cmd_{$room}");
        $phoneActive = $this->dbHas("wheel_phone_active_{$room}");

        $hasNewCmd = false;
        if ($cmd && is_array($cmd)) {
            $isRecent = isset($cmd['time']) && (microtime(true) - $cmd['time'] < 45);
            if ($isRecent && (!isset($lastCmdId) || $cmd['id'] !== $lastCmdId)) {
                $hasNewCmd = true;
            }
        }

        return response()->json([
            'success' => true,
            'hasNewCommand' => $hasNewCmd,
            'command' => $hasNewCmd ? $cmd : null,
            'phoneActive' => $phoneActive,
        ]);
    }

    /**
     * Phone or Desktop sends heartbeat ping to maintain connection status.
     */
    public function ping(Request $request)
    {
        $room = $request->input('room') ?: $request->query('room');
        $role = $request->input('role', 'phone');

        if ($room) {
            if ($role === 'phone') {
                $this->dbPut("wheel_phone_active_{$room}", true, 45);
            } elseif ($role === 'host') {
                $this->dbPut("wheel_host_active_{$room}", true, 45);
            }
        }

        $phoneActive = $this->dbHas("wheel_phone_active_{$room}");
        $hostActive = $this->dbHas("wheel_host_active_{$room}");

        return response()->json([
            'success' => true,
            'phoneActive' => $phoneActive,
            'hostActive' => $hostActive,
        ]);
    }
}
