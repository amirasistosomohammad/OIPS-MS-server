<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (($user->role ?? null) === 'admin' || $user->email === 'admin@admin.com'), 403, 'Forbidden.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $logs = ActivityLog::query()
            ->with('user:id,name,email,role')
            ->orderByDesc('action_time')
            ->limit(300)
            ->get();

        return response()->json([
            'data' => $logs,
        ]);
    }

    public function show(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $this->ensureAdmin($request);

        $activityLog->load('user:id,name,email,role');

        return response()->json([
            'data' => $activityLog,
        ]);
    }
}
