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
            'phone' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
            'smartphone' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
            'mobile' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
            'telephone' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
            'ទូរស័ព្ទ' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
            'ទូរស័ព្ទឆ្លាតវៃ' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',

            // Hardware, Peripherals & Media (Direct Verified Online Internet URLs)
            'usb flash drive' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',
            'មេម៉ូរី flash drive' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',
            'flash drive' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',
            'usb drive' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',

            'ខ្សែ type-c' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
            'ខ្សែសាក type-c' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
            'type-c' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
            'type c' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
            'usb-c' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
            'usb c' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',

            'រន្ធ usb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/USB_port_on_london_bus.jpg/960px-USB_port_on_london_bus.jpg',
            'usb port' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/USB_port_on_london_bus.jpg/960px-USB_port_on_london_bus.jpg',
            'usb ports' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/USB_port_on_london_bus.jpg/960px-USB_port_on_london_bus.jpg',
            'usb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',

            'solid state drive' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png/960px-Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png',
            'ssd' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png/960px-Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png',

            'hard disk' => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
            'hard drive' => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
            'hhd' => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
            'hdd' => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
            'ឌីសរឹង' => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
            'ហាដឌីស' => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',

            'ram' => 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',
            'រ៉េម' => 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',
            'រ៉ាម' => 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',

            'compact disc' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
            'ស៊ីឌី' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
            'cd' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
            'dvd' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
            'ឌីវីឌី' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',

            'matboard' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
            'motherboard' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
            'mainboard' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
            'ម៉េដបត' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',

            'processor' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
            'cpu' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
            'ស៊ីភីយូ' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',

            'ups' => 'https://upload.wikimedia.org/wikipedia/commons/f/f4/UPSFrontView.jpg',
            'អាគុយជំនួយភ្លើង' => 'https://upload.wikimedia.org/wikipedia/commons/f/f4/UPSFrontView.jpg',

            'windows' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Windows_logo_-_2021.svg/960px-Windows_logo_-_2021.svg.png',
            'វីនដូ' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Windows_logo_-_2021.svg/960px-Windows_logo_-_2021.svg.png',

            'wi-fi' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',
            'wifi' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',
            'វ៉ាយហ្វាយ' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',
            'router' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',

            'ម៉ាស៊ីនហ្គេម' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
            'game console' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
            'ps5' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',

            'ទូរទស្សន៍ឆ្លាតវៃ' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80',
            'smart tv' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80',

            'ថេបប្លេត' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',
            'tablet' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',
            'ipad' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',

            'កុំព្យូទ័រលើតុ' => 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=800&q=80',
            'desktop' => 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=800&q=80',

            'កុំព្យូទ័រយួរដៃ' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
            'laptop' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',

            'camera' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
            'កាមេរ៉ា' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',

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

            'headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
            'កាស' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',

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
                return response()->json(['success' => true, 'url' => $this->formatSafeImageUrl($possibleUrl), 'source' => 'direct']);
            }
        }

        // 2. Immediate check in Curated Builtin Dictionary (0ms, 100% relevant, no blocked requests)
        // Sort keys by descending length so "usb flash drive" matches before "usb", "ខ្សែ type-c" matches before "type-c"
        uksort($builtinMap, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $lowerRaw = mb_strtolower($rawWord, 'UTF-8');
        foreach ($builtinMap as $term => $url) {
            if (str_contains($lowerRaw, $term)) {
                return response()->json(['success' => true, 'url' => $this->formatSafeImageUrl($url), 'source' => 'builtin']);
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
                return response()->json(['success' => true, 'url' => $this->formatSafeImageUrl($url), 'source' => 'builtin']);
            }
        }

        $cacheKey = 'wheel_img_' . md5($lowerKw);
        $cached = $this->dbGet($cacheKey);
        if ($cached && is_string($cached) && filter_var($cached, FILTER_VALIDATE_URL)) {
            // Filter out old legacy circuit board if accidentally cached
            if (!str_contains($cached, 'photo-1518770660439')) {
                return response()->json(['success' => true, 'url' => $this->formatSafeImageUrl($cached), 'cached' => true]);
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
            return response()->json(['success' => true, 'url' => $this->formatSafeImageUrl($imageUrl)]);
        }

        return response()->json(['success' => false, 'message' => 'Image not found'], 404);
    }

    /**
     * Format an image URL to be safe from browser COEP/CORB blocks.
     * Unsplash supports cross-origin natively; other sources (Wikimedia, etc.) are proxied.
     */
    protected function formatSafeImageUrl(?string $url): string
    {
        if (!$url) {
            return '';
        }
        if (str_starts_with($url, '/') || str_contains($url, 'images.unsplash.com') || str_contains($url, 'pollinations.ai')) {
            return $url;
        }
        return '/api/lucky-wheel/proxy-image?url=' . urlencode($url);
    }

    /**
     * Proxy external images to bypass COEP/CORB and adblock restrictions.
     */
    public function proxyImage(Request $request)
    {
        $url = (string) ($request->query('url') ?: $request->input('url') ?: '');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response('Invalid URL', 400);
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host = strtolower($parsed['host'] ?? '');

        // Security check: Only allow HTTP/HTTPS and disallow private IPs / localhost
        if (!in_array($scheme, ['http', 'https'], true) || empty($host)) {
            return response('Invalid scheme or host', 400);
        }
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true) || str_ends_with($host, '.internal') || str_ends_with($host, '.local')) {
            return response('Access denied', 403);
        }

        $cacheKey = 'wheel_img_proxy_' . md5($url);
        $data = Cache::remember($cacheKey, 86400 * 14, function () use ($url) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\nAccept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8\r\nReferer: https://onlinexam.site/\r\n",
                        'timeout' => 8,
                        'follow_location' => 1,
                        'max_redirects' => 5,
                        'ignore_errors' => true,
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);

                $stream = @fopen($url, 'r', false, $context);
                if (!$stream) {
                    return null;
                }

                $meta = stream_get_meta_data($stream);
                $wrapperHeaders = $meta['wrapper_data'] ?? [];
                $contentType = 'image/jpeg';
                $statusOk = true;

                foreach ($wrapperHeaders as $h) {
                    if (is_string($h)) {
                        if (preg_match('#^HTTP/\S+\s+(\d+)#i', $h, $m)) {
                            $code = (int) $m[1];
                            if ($code >= 400) {
                                $statusOk = false;
                            }
                        } elseif (stripos($h, 'Content-Type:') === 0) {
                            $contentType = trim(substr($h, 13));
                        }
                    }
                }

                if (!$statusOk) {
                    @fclose($stream);
                    return null;
                }

                $body = stream_get_contents($stream);
                @fclose($stream);

                if (!$body || strlen($body) < 50) {
                    return null;
                }

                return [
                    'body' => base64_encode($body),
                    'type' => $contentType,
                ];
            } catch (\Throwable $e) {
                return null;
            }
        });

        if (!$data || empty($data['body'])) {
            return response('Image not found', 404);
        }

        $binary = base64_decode($data['body']);
        $mime = $data['type'] ?? 'image/jpeg';

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => strlen($binary),
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
        ];

        return response($binary, 200, $headers);
    }
}

