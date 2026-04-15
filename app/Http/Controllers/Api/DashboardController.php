<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryNotification;
use App\Models\ProgramEnrollment;
use App\Models\ProgramUpdateEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $stats = [
            'beneficiaries' => Beneficiary::query()->count(),
            'active_enrollments' => ProgramEnrollment::query()->where('enrollment_status', 'active')->count(),
            'program_updates' => ProgramUpdateEntry::query()->count(),
            'users' => User::query()->count(),
            'notifications_open' => BeneficiaryNotification::query()->where('status', 'open')->count(),
            'notifications_due_today' => BeneficiaryNotification::query()
                ->where('status', 'open')
                ->whereDate('due_date', $today)
                ->count(),
            'notifications_overdue' => BeneficiaryNotification::query()
                ->where('status', 'open')
                ->whereDate('due_date', '<', $today)
                ->count(),
        ];

        $upcomingNotifications = BeneficiaryNotification::query()
            ->with(['enrollment.beneficiary:id,last_name,first_name', 'enrollment.program:id,program_code,program_name'])
            ->where('status', 'open')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function (BeneficiaryNotification $row): array {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'due_date' => optional($row->due_date)->toDateString(),
                    'program_name' => $row->enrollment?->program?->program_name,
                    'beneficiary_name' => trim(($row->enrollment?->beneficiary?->last_name ?? '').', '.($row->enrollment?->beneficiary?->first_name ?? '')),
                    'status' => $row->status,
                ];
            });

        return response()->json([
            'data' => [
                'stats' => $stats,
                'upcoming_notifications' => $upcomingNotifications,
            ],
        ]);
    }
}
