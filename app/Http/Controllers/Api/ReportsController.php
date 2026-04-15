<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgramEnrollment;
use App\Models\ProgramUpdateEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $enrollmentsByProgram = ProgramEnrollment::query()
            ->join('programs', 'programs.id', '=', 'program_enrollments.program_id')
            ->select('programs.program_code', 'programs.program_name', DB::raw('count(program_enrollments.id) as total'))
            ->groupBy('programs.program_code', 'programs.program_name')
            ->orderBy('programs.program_code')
            ->get();

        $updatesByStatus = ProgramUpdateEntry::query()
            ->leftJoin('program_status_options', 'program_status_options.id', '=', 'program_update_entries.status_option_id')
            ->select(
                DB::raw("coalesce(program_status_options.status_label, 'Unspecified') as status_label"),
                DB::raw('count(program_update_entries.id) as total'),
                DB::raw('coalesce(sum(program_update_entries.amount_received), 0) as total_amount')
            )
            ->when($from, fn ($q) => $q->whereDate('program_update_entries.update_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('program_update_entries.update_date', '<=', $to))
            ->groupBy(DB::raw("coalesce(program_status_options.status_label, 'Unspecified')"))
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'data' => [
                'enrollments_by_program' => $enrollmentsByProgram,
                'updates_by_status' => $updatesByStatus,
            ],
        ]);
    }
}
