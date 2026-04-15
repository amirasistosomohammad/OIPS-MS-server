<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\SqlBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(private readonly SqlBackupService $sqlBackupService)
    {
    }

    public function downloadNow(Request $request): BinaryFileResponse
    {
        $this->ensureAdmin($request);
        $filename = 'oipsms-backup-'.now()->format('Ymd-His').'.sql';
        $relativePath = "backups/{$filename}";
        $sql = $this->sqlBackupService->generateSqlDump();
        Storage::disk('local')->put($relativePath, $sql);
        $this->updateScheduleMetadata();

        return response()->download(Storage::disk('local')->path($relativePath), $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $settings = SystemSetting::singleton();

        return response()->json([
            'frequency' => $settings->backup_frequency,
            'run_at_time' => $settings->backup_run_at_time,
            'timezone' => $settings->backup_timezone,
            'last_run_at' => optional($settings->backup_last_run_at)->toISOString(),
            'next_run_at' => $this->nextRunAtIso($settings->backup_frequency, $settings->backup_run_at_time),
            'has_latest_file' => count(Storage::disk('local')->files('backups')) > 0,
        ]);
    }

    public function updateSchedule(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'frequency' => ['required', 'in:off,daily,weekly'],
            'run_at_time' => ['required', 'date_format:H:i'],
        ]);

        $settings = SystemSetting::singleton();
        $settings->update([
            'backup_frequency' => $data['frequency'],
            'backup_run_at_time' => $data['run_at_time'],
        ]);

        return response()->json([
            'frequency' => $settings->backup_frequency,
            'run_at_time' => $settings->backup_run_at_time,
            'timezone' => $settings->backup_timezone,
            'last_run_at' => optional($settings->backup_last_run_at)->toISOString(),
            'next_run_at' => $this->nextRunAtIso($settings->backup_frequency, $settings->backup_run_at_time),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $files = collect(Storage::disk('local')->files('backups'))
            ->sortDesc()
            ->map(fn ($path) => [
                'filename' => basename($path),
                'created_at' => now()->setTimestamp(Storage::disk('local')->lastModified($path))->toISOString(),
            ])->values();

        return response()->json([
            'backups' => $files,
        ]);
    }

    public function downloadLatest(Request $request): BinaryFileResponse|JsonResponse
    {
        $this->ensureAdmin($request);
        $latest = collect(Storage::disk('local')->files('backups'))->sortDesc()->first();
        if (! $latest) {
            return response()->json(['message' => 'No backup file available.'], 404);
        }

        return response()->download(Storage::disk('local')->path($latest), basename($latest), [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function downloadFile(Request $request, string $filename): BinaryFileResponse|JsonResponse
    {
        $this->ensureAdmin($request);
        $safeFilename = basename($filename);
        $path = "backups/{$safeFilename}";

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Backup file not found.'], 404);
        }

        return response()->download(Storage::disk('local')->path($path), $safeFilename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (($user->role ?? null) === 'admin' || $user->email === 'admin@admin.com'), 403, 'Forbidden.');
    }

    private function nextRunAtIso(string $frequency, string $runAt): ?string
    {
        if ($frequency === 'off') {
            return null;
        }

        $next = now()->setTimeFromTimeString($runAt);
        if ($next->isPast()) {
            $next = $frequency === 'weekly' ? $next->addWeek() : $next->addDay();
        }

        return $next->toISOString();
    }

    private function updateScheduleMetadata(): void
    {
        $settings = SystemSetting::singleton();
        $settings->update([
            'backup_last_run_at' => now(),
        ]);
    }
}
