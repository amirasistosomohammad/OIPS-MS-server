<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeneficiaryNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = BeneficiaryNotification::query()
            ->with([
                'enrollment.beneficiary:id,last_name,first_name',
                'enrollment.program:id,program_code,program_name',
            ])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(function (BeneficiaryNotification $row): array {
                return [
                    'id' => $row->id,
                    'program_enrollment_id' => $row->program_enrollment_id,
                    'notification_type' => $row->notification_type,
                    'title' => $row->title,
                    'message' => $row->message,
                    'due_date' => optional($row->due_date)->toDateString(),
                    'status' => $row->status,
                    'read_at' => optional($row->read_at)->toISOString(),
                    'beneficiary_name' => trim(($row->enrollment?->beneficiary?->last_name ?? '').', '.($row->enrollment?->beneficiary?->first_name ?? '')),
                    'program_name' => $row->enrollment?->program?->program_name,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function markAsRead(Request $request, BeneficiaryNotification $notification): JsonResponse
    {
        $notification->update([
            'read_at' => now(),
            'status' => 'read',
        ]);

        return response()->json([
            'data' => $notification->fresh(),
            'message' => 'Notification marked as read.',
        ]);
    }

    public function destroy(BeneficiaryNotification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully.']);
    }
}
