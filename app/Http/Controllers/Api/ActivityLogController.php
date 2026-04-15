<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = ActivityLog::query()
            ->with('user:id,name,email,role')
            ->orderByDesc('action_time')
            ->limit(300)
            ->get();

        return response()->json([
            'data' => $logs,
        ]);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user:id,name,email,role');

        return response()->json([
            'data' => $activityLog,
        ]);
    }
}
