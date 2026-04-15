<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgramEnrollment;
use App\Models\ProgramUpdateEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportsController extends Controller
{
    private function getSummaryData(?string $from, ?string $to): array
    {
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

        return [$enrollmentsByProgram, $updatesByStatus];
    }

    private function buildSummaryFilename(string $extension, ?string $from, ?string $to): string
    {
        $period = 'all-time';
        if ($from && $to) $period = "{$from}_to_{$to}";
        elseif ($from) $period = "from_{$from}";
        elseif ($to) $period = "to_{$to}";

        $period = Str::slug($period, '_');

        return "report_summary_{$period}." . ltrim($extension, '.');
    }

    public function summary(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');

        [$enrollmentsByProgram, $updatesByStatus] = $this->getSummaryData($from, $to);

        return response()->json([
            'data' => [
                'enrollments_by_program' => $enrollmentsByProgram,
                'updates_by_status' => $updatesByStatus,
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        [$enrollmentsByProgram, $updatesByStatus] = $this->getSummaryData($from, $to);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Report Summary')
            ->setSubject('Report Summary')
            ->setDescription('Generated report summary export.');

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Enrollments by Program');
        $sheet1->fromArray([['Program Code', 'Program Name', 'Total Enrollments']], null, 'A1');
        $row = 2;
        foreach ($enrollmentsByProgram as $item) {
            $sheet1->setCellValue("A{$row}", (string) ($item->program_code ?? ''));
            $sheet1->setCellValue("B{$row}", (string) ($item->program_name ?? ''));
            $sheet1->setCellValue("C{$row}", (int) ($item->total ?? 0));
            $row++;
        }
        $sheet1->getStyle('A1:C1')->getFont()->setBold(true);
        foreach (['A' => 14, 'B' => 40, 'C' => 18] as $col => $width) {
            $sheet1->getColumnDimension($col)->setWidth($width);
        }

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Updates by Status');
        $sheet2->fromArray([['Status', 'Update Count', 'Total Amount Received']], null, 'A1');
        $row = 2;
        foreach ($updatesByStatus as $item) {
            $sheet2->setCellValue("A{$row}", (string) ($item->status_label ?? ''));
            $sheet2->setCellValue("B{$row}", (int) ($item->total ?? 0));
            $sheet2->setCellValue("C{$row}", (float) ($item->total_amount ?? 0));
            $row++;
        }
        $sheet2->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet2->getStyle('C2:C' . max(2, $row - 1))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        foreach (['A' => 28, 'B' => 16, 'C' => 22] as $col => $width) {
            $sheet2->getColumnDimension($col)->setWidth($width);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $this->buildSummaryFilename('xlsx', $from, $to);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function exportPdf(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        [$enrollmentsByProgram, $updatesByStatus] = $this->getSummaryData($from, $to);
        $filename = $this->buildSummaryFilename('pdf', $from, $to);

        $pdf = Pdf::loadView('reports.summary', [
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
            'enrollmentsByProgram' => $enrollmentsByProgram,
            'updatesByStatus' => $updatesByStatus,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
