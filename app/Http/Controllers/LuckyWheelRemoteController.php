<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LuckyWheelRemoteController extends Controller
{
    /**
     * Get persistent cache repository (database store across serverless lambdas).
     */
    protected function getStore()
    {
        try {
            return Cache::store('database');
        } catch (\Throwable $e) {
            return Cache::store();
        }
    }

    /**
     * Create or retrieve a Game Room PIN for remote pairing.
     */
    public function createOrGetRoom(Request $request)
    {
        $requestedPin = $request->input('room') ?: $request->query('room');
        $store = $this->getStore();

        if ($requestedPin && strlen((string)$requestedPin) === 4) {
            $pin = (string) $requestedPin;
            if ($store->has("wheel_room_{$pin}")) {
                $state = $store->get("wheel_room_{$pin}");
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
                $store->put("wheel_room_{$pin}", $state, now()->addHours(12));
            }
        } else {
            // Generate unique 4-digit PIN between 1000 and 9999
            do {
                $pin = (string) random_int(1000, 9999);
            } while ($store->has("wheel_room_{$pin}"));

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

            $store->put("wheel_room_{$pin}", $state, now()->addHours(12));
        }

        $store->put("wheel_host_active_{$pin}", true, now()->addSeconds(30));

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
        $store = $this->getStore();

        if (!$room || !is_array($state)) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters'], 422);
        }

        $state['updatedAt'] = now()->timestamp;
        $store->put("wheel_room_{$room}", $state, now()->addHours(12));
        $store->put("wheel_host_active_{$room}", true, now()->addSeconds(30));

        return response()->json(['success' => true]);
    }

    /**
     * Phone fetches the current game state.
     */
    public function getState(Request $request)
    {
        $room = $request->query('room') ?: $request->input('room');
        $store = $this->getStore();

        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Room code required'], 422);
        }

        if (!$store->has("wheel_room_{$room}")) {
            $defaultState = [
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
            $store->put("wheel_room_{$room}", $defaultState, now()->addHours(12));
        }

        $state = $store->get("wheel_room_{$room}");

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
        $store = $this->getStore();

        if (!$room || !$action) {
            return response()->json(['success' => false, 'message' => 'Room and action are required'], 422);
        }

        $cmd = [
            'id' => uniqid('cmd_', true),
            'action' => $action,
            'payload' => $payload,
            'time' => microtime(true),
        ];

        $store->put("wheel_cmd_{$room}", $cmd, now()->addMinutes(5));
        $store->put("wheel_phone_active_{$room}", true, now()->addSeconds(30));

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
        $store = $this->getStore();

        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Room code required'], 422);
        }

        $cmd = $store->get("wheel_cmd_{$room}");
        $phoneActive = $store->has("wheel_phone_active_{$room}");

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
        $store = $this->getStore();

        if ($room) {
            if ($role === 'phone') {
                $store->put("wheel_phone_active_{$room}", true, now()->addSeconds(30));
            } elseif ($role === 'host') {
                $store->put("wheel_host_active_{$room}", true, now()->addSeconds(30));
            }
        }

        $phoneActive = $store->has("wheel_phone_active_{$room}");
        $hostActive = $store->has("wheel_host_active_{$room}");

        return response()->json([
            'success' => true,
            'phoneActive' => $phoneActive,
            'hostActive' => $hostActive,
        ]);
    }
}
