<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LuckyWheelRemoteController extends Controller
{
    /**
     * Create or retrieve a Game Room PIN for remote pairing.
     */
    public function createOrGetRoom(Request $request)
    {
        $requestedPin = $request->input('room');

        if ($requestedPin && Cache::has("wheel_room_{$requestedPin}")) {
            $pin = $requestedPin;
            $state = Cache::get("wheel_room_{$pin}");
        } else {
            // Generate unique 4-digit PIN between 1000 and 9999
            do {
                $pin = (string) random_int(1000, 9999);
            } while (Cache::has("wheel_room_{$pin}"));

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

            Cache::put("wheel_room_{$pin}", $state, now()->addHours(12));
        }

        return response()->json([
            'success' => true,
            'room' => $pin,
            'state' => $state,
        ]);
    }

    /**
     * Main screen updates game state to cache.
     */
    public function syncState(Request $request)
    {
        $room = $request->input('room');
        $state = $request->input('state');

        if (!$room || !is_array($state)) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters'], 422);
        }

        $state['updatedAt'] = now()->timestamp;
        Cache::put("wheel_room_{$room}", $state, now()->addHours(12));

        return response()->json(['success' => true]);
    }

    /**
     * Phone fetches the current game state.
     */
    public function getState(Request $request)
    {
        $room = $request->query('room');

        if (!$room || !Cache::has("wheel_room_{$room}")) {
            return response()->json(['success' => false, 'message' => 'Room not found or expired'], 404);
        }

        $state = Cache::get("wheel_room_{$room}");

        return response()->json([
            'success' => true,
            'room' => $room,
            'state' => $state,
        ]);
    }

    /**
     * Phone sends an action command to the main screen.
     */
    public function sendCommand(Request $request)
    {
        $room = $request->input('room');
        $action = $request->input('action');
        $payload = $request->input('payload', []);

        if (!$room || !$action) {
            return response()->json(['success' => false, 'message' => 'Room and action are required'], 422);
        }

        $cmd = [
            'id' => uniqid('cmd_', true),
            'action' => $action,
            'payload' => $payload,
            'time' => microtime(true),
        ];

        Cache::put("wheel_cmd_{$room}", $cmd, now()->addMinutes(3));
        Cache::put("wheel_phone_active_{$room}", true, now()->addSeconds(25));

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
        $room = $request->query('room');
        $lastCmdId = $request->query('last_cmd_id');

        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Room code required'], 422);
        }

        $cmd = Cache::get("wheel_cmd_{$room}");
        $phoneActive = Cache::has("wheel_phone_active_{$room}");

        $hasNewCmd = ($cmd && (!isset($lastCmdId) || $cmd['id'] !== $lastCmdId));

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
        $room = $request->input('room');
        $role = $request->input('role', 'phone'); // 'phone' or 'host'

        if ($room) {
            if ($role === 'phone') {
                Cache::put("wheel_phone_active_{$room}", true, now()->addSeconds(25));
            } elseif ($role === 'host') {
                Cache::put("wheel_host_active_{$room}", true, now()->addSeconds(25));
            }
        }

        $phoneActive = Cache::has("wheel_phone_active_{$room}");
        $hostActive = Cache::has("wheel_host_active_{$room}");

        return response()->json([
            'success' => true,
            'phoneActive' => $phoneActive,
            'hostActive' => $hostActive,
        ]);
    }
}
