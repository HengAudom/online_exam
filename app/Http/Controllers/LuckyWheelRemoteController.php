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

    /**
     * Fetch high-quality image URL for a given word using Wikipedia/Commons.
     */
    public function fetchWordImage(Request $request)
    {
        $rawWord = (string) ($request->input('word') ?: $request->query('word') ?: '');
        if (!$rawWord) {
            return response()->json(['success' => false, 'message' => 'Word required'], 422);
        }

        $builtinMap = [
            'phone' => '/images/lucky-wheel/phone.jpg',
            'smartphone' => '/images/lucky-wheel/phone.jpg',
            'mobile' => '/images/lucky-wheel/phone.jpg',
            'telephone' => '/images/lucky-wheel/phone.jpg',
            'ទូរស័ព្ទ' => '/images/lucky-wheel/phone.jpg',
            'ទូរស័ព្ទឆ្លាតវៃ' => '/images/lucky-wheel/phone.jpg',

            // Hardware, Peripherals & Media (Verified high-res local images)
            'usb flash drive' => '/images/lucky-wheel/usb-flash-drive.jpg',
            'មេម៉ូរី flash drive' => '/images/lucky-wheel/usb-flash-drive.jpg',
            'flash drive' => '/images/lucky-wheel/usb-flash-drive.jpg',
            'usb drive' => '/images/lucky-wheel/usb-flash-drive.jpg',

            'ខ្សែ type-c' => '/images/lucky-wheel/type-c.jpg',
            'ខ្សែសាក type-c' => '/images/lucky-wheel/type-c.jpg',
            'type-c' => '/images/lucky-wheel/type-c.jpg',
            'type c' => '/images/lucky-wheel/type-c.jpg',
            'usb-c' => '/images/lucky-wheel/type-c.jpg',
            'usb c' => '/images/lucky-wheel/type-c.jpg',

            'រន្ធ usb' => '/images/lucky-wheel/usb-port.jpg',
            'usb port' => '/images/lucky-wheel/usb-port.jpg',
            'usb ports' => '/images/lucky-wheel/usb-port.jpg',
            'usb' => '/images/lucky-wheel/usb-flash-drive.jpg',

            'solid state drive' => '/images/lucky-wheel/ssd.jpg',
            'ssd' => '/images/lucky-wheel/ssd.jpg',

            'hard disk' => '/images/lucky-wheel/hhd.jpg',
            'hard drive' => '/images/lucky-wheel/hhd.jpg',
            'hhd' => '/images/lucky-wheel/hhd.jpg',
            'hdd' => '/images/lucky-wheel/hhd.jpg',
            'ឌីសរឹង' => '/images/lucky-wheel/hhd.jpg',
            'ហាដឌីស' => '/images/lucky-wheel/hhd.jpg',

            'ram' => '/images/lucky-wheel/ram.jpg',
            'រ៉េម' => '/images/lucky-wheel/ram.jpg',
            'រ៉ាម' => '/images/lucky-wheel/ram.jpg',

            'compact disc' => '/images/lucky-wheel/cd-dvd.jpg',
            'ស៊ីឌី' => '/images/lucky-wheel/cd-dvd.jpg',
            'cd' => '/images/lucky-wheel/cd-dvd.jpg',
            'dvd' => '/images/lucky-wheel/cd-dvd.jpg',
            'ឌីវីឌី' => '/images/lucky-wheel/cd-dvd.jpg',

            'matboard' => '/images/lucky-wheel/matboard.jpg',
            'motherboard' => '/images/lucky-wheel/matboard.jpg',
            'mainboard' => '/images/lucky-wheel/matboard.jpg',
            'ម៉េដបត' => '/images/lucky-wheel/matboard.jpg',

            'processor' => '/images/lucky-wheel/cpu.jpg',
            'cpu' => '/images/lucky-wheel/cpu.jpg',
            'ស៊ីភីយូ' => '/images/lucky-wheel/cpu.jpg',

            'ups' => '/images/lucky-wheel/ups.jpg',
            'អាគុយជំនួយភ្លើង' => '/images/lucky-wheel/ups.jpg',

            'windows' => '/images/lucky-wheel/windows.jpg',
            'វីនដូ' => '/images/lucky-wheel/windows.jpg',

            'wi-fi' => '/images/lucky-wheel/wifi.jpg',
            'wifi' => '/images/lucky-wheel/wifi.jpg',
            'វ៉ាយហ្វាយ' => '/images/lucky-wheel/wifi.jpg',
            'router' => '/images/lucky-wheel/wifi.jpg',

            'ម៉ាស៊ីនហ្គេម' => '/images/lucky-wheel/console.jpg',
            'game console' => '/images/lucky-wheel/console.jpg',
            'ps5' => '/images/lucky-wheel/console.jpg',

            'ទូរទស្សន៍ឆ្លាតវៃ' => '/images/lucky-wheel/tv.jpg',
            'smart tv' => '/images/lucky-wheel/tv.jpg',

            'ថេបប្លេត' => '/images/lucky-wheel/tablet.jpg',
            'tablet' => '/images/lucky-wheel/tablet.jpg',
            'ipad' => '/images/lucky-wheel/tablet.jpg',

            'កុំព្យូទ័រលើតុ' => '/images/lucky-wheel/desktop.jpg',
            'desktop' => '/images/lucky-wheel/desktop.jpg',

            'កុំព្យូទ័រយួរដៃ' => '/images/lucky-wheel/laptop.jpg',
            'laptop' => '/images/lucky-wheel/laptop.jpg',

            'camera' => '/images/lucky-wheel/camera.jpg',
            'កាមេរ៉ា' => '/images/lucky-wheel/camera.jpg',

            'source code' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
            'code' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
            'programming' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
            'coding' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
            'developer' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
            'កូដកម្មវិធី' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',

            'computer' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
            'laptop' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
            'pc' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
            'កុំព្យូទ័រ' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',

            'keyboard' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
            'ក្តារចុច' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
            'ក្ដារចុច' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',

            'mouse' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
            'computer mouse' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
            'កណ្ដុរ' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
            'កណ្តុរ' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',

            'printer' => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80',
            'ម៉ាស៊ីនបោះពុម្ព' => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80',

            'internet' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
            'network' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
            'អ៊ីនធឺណិត' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',

            'database' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',
            'server' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',
            'ទិន្នន័យ' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',

            'monitor' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
            'screen' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
            'display' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
            'អេក្រង់' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',

            'web browser' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
            'browser' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
            'website' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
            'កម្មវិធីរុករក' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',

            'social media' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',
            'social' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',
            'បណ្តាញសង្គម' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',
            'បណ្ដាញសង្គម' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',

            'cybersecurity' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=800&q=80',
            'security' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=800&q=80',
            'សន្តិសុខឌីជីថល' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=800&q=80',

            'robot' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',
            'មនុស្សយន្ត' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',
            'រ៉ូបូត' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',

            'ai' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',
            'artificial intelligence' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',
            'បញ្ញាសិប្បនិម្មិត' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',

            'camera' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
            'កាមេរ៉ា' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',

            'headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
            'កាស' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',

            'wifi' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=800&q=80',
            'វ៉ាយហ្វាយ' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=800&q=80',

            'book' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=800&q=80',
            'សៀវភៅ' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=800&q=80',

            'school' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80',
            'សាលារៀន' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80',

            'teacher' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80',
            'គ្រូបង្រៀន' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80',

            'student' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80',
            'សិស្ស' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80',

            'car' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
            'ឡាន' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
            'រថយន្ត' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',

            'airplane' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',
            'យន្តហោះ' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',

            'clock' => 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80',
            'watch' => 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80',
            'នាឡិកា' => 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80'
        ];

        // 1. Direct URL check: "Word | https://..."
        if (str_contains($rawWord, '|')) {
            $parts = explode('|', $rawWord, 2);
            $possibleUrl = trim($parts[1]);
            if (filter_var($possibleUrl, FILTER_VALIDATE_URL)) {
                return response()->json(['success' => true, 'url' => $possibleUrl, 'source' => 'direct']);
            }
        }

        // 2. Immediate check in Curated Builtin Dictionary (0ms, 100% relevant, no blocked requests)
        // Sort keys by descending length so "usb flash drive" matches before "usb", "ខ្សែ type-c" matches before "type-c"
        uksort($builtinMap, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $lowerRaw = mb_strtolower($rawWord, 'UTF-8');
        foreach ($builtinMap as $term => $url) {
            if (str_contains($lowerRaw, $term)) {
                return response()->json(['success' => true, 'url' => $url, 'source' => 'builtin']);
            }
        }

        // 3. Extract Keyword (check English in parentheses first)
        $keyword = '';
        if (preg_match('/[\(\[]([a-zA-Z0-9\s\-]+)[\)\]]/u', $rawWord, $matches)) {
            $keyword = trim($matches[1]);
        } elseif (preg_match('/[a-zA-Z\s]{2,}/u', $rawWord, $matches)) {
            $keyword = trim($matches[0]);
        } else {
            // Khmer or unicode word
            $keyword = trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $rawWord));
        }

        if (!$keyword) {
            $keyword = trim($rawWord);
        }

        $lowerKw = mb_strtolower($keyword, 'UTF-8');
        foreach ($builtinMap as $term => $url) {
            if (str_contains($lowerKw, $term)) {
                return response()->json(['success' => true, 'url' => $url, 'source' => 'builtin']);
            }
        }

        $cacheKey = 'wheel_img_' . md5($lowerKw);
        $cached = $this->dbGet($cacheKey);
        if ($cached && is_string($cached) && filter_var($cached, FILTER_VALIDATE_URL)) {
            // Filter out old legacy circuit board if accidentally cached
            if (!str_contains($cached, 'photo-1518770660439')) {
                return response()->json(['success' => true, 'url' => $cached, 'cached' => true]);
            }
        }

        $imageUrl = null;
        $userAgent = 'OnlineXam-LuckyWheel/1.0 (info@onlinexam.site; contact@onlinexam.site)';

        // Step A0: If Khmer characters present, search Khmer Wikipedia (km.wikipedia.org)
        if (preg_match('/[\x{1780}-\x{17FF}]/u', $rawWord)) {
            $khmerSearchTerm = trim(preg_replace('/[^\x{1780}-\x{17FF}\s]/u', '', $rawWord));
            if ($khmerSearchTerm) {
                try {
                    $kmWikiUrl = 'https://km.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($khmerSearchTerm) . '&gsrlimit=4&prop=pageimages&pithumbsize=640&piprop=thumbnail&format=json';
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => "User-Agent: {$userAgent}\r\n",
                            'timeout' => 3
                        ]
                    ]);
                    $kmJson = @file_get_contents($kmWikiUrl, false, $context);
                    if ($kmJson) {
                        $kmData = json_decode($kmJson, true);
                        $pages = $kmData['query']['pages'] ?? [];
                        foreach ($pages as $p) {
                            if (!empty($p['thumbnail']['source'])) {
                                $src = $p['thumbnail']['source'];
                                if (!str_contains($src, 'Disambig') && !str_contains($src, '.svg')) {
                                    $imageUrl = explode('?', $src)[0];
                                    break;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }

        // Step A: English Wikipedia Generator Search (handles English keywords and loanwords)
        if (!$imageUrl && $keyword) {
            try {
                $wikiSearchUrl = 'https://en.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($keyword) . '&gsrlimit=5&prop=pageimages&pithumbsize=640&piprop=thumbnail&format=json';
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "User-Agent: {$userAgent}\r\n",
                        'timeout' => 4
                    ]
                ]);
                $respJson = @file_get_contents($wikiSearchUrl, false, $context);
                if ($respJson) {
                    $respData = json_decode($respJson, true);
                    $pages = $respData['query']['pages'] ?? [];
                    foreach ($pages as $p) {
                        if (!empty($p['thumbnail']['source'])) {
                            $src = $p['thumbnail']['source'];
                            if (!str_contains($src, 'Disambig') && !str_contains($src, '.svg')) {
                                // CRITICAL: Strip tracking query parameters (?utm_source=...) so Brave Shields & adblockers do not block the image
                                $imageUrl = explode('?', $src)[0];
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Step B: Wikimedia Commons search if Wikipedia had no suitable thumbnail
        if (!$imageUrl) {
            try {
                $commonsUrl = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=' . urlencode($keyword) . '&gsrlimit=3&prop=imageinfo&iiprop=url&iiurlwidth=640&format=json';
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "User-Agent: {$userAgent}\r\n",
                        'timeout' => 4
                    ]
                ]);
                $respJson = @file_get_contents($commonsUrl, false, $context);
                if ($respJson) {
                    $respData = json_decode($respJson, true);
                    $pages = $respData['query']['pages'] ?? [];
                    foreach ($pages as $p) {
                        if (!empty($p['imageinfo'][0]['thumburl'])) {
                            $thumb = $p['imageinfo'][0]['thumburl'];
                            $imageUrl = explode('?', $thumb)[0];
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Step C: Fallback to Pollinations AI
        if (!$imageUrl) {
            $imageUrl = "https://image.pollinations.ai/prompt/" . urlencode($keyword . ' clean photo object') . "?width=640&height=480&nologo=true";
        }

        if ($imageUrl) {
            $this->dbPut($cacheKey, $imageUrl, 86400 * 30); // Cache for 30 days
            return response()->json(['success' => true, 'url' => $imageUrl]);
        }

        return response()->json(['success' => false, 'message' => 'Image not found'], 404);
    }
}

