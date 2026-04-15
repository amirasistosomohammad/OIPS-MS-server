<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    private const LOGO_SLOT_FIELD = [
        'primary' => 'logo_primary_path',
        'secondary' => 'logo_secondary_path',
        'tertiary' => 'logo_tertiary_path',
        'legacy' => 'logo_path',
    ];

    public function public(Request $request): JsonResponse
    {
        $settings = SystemSetting::singleton();
        $logoPath = $settings->logo_primary_path ?: $settings->logo_path;

        return response()->json([
            'app_name' => $settings->app_name,
            'topbar_title' => $settings->app_name,
            'login_title' => $settings->app_name,
            'logo_path' => $settings->logo_path,
            'logo_url' => $this->assetUrl($settings->logo_path, $request),
            'logo_primary_path' => $logoPath,
            'logo_primary_url' => $this->assetUrl($logoPath, $request),
            'logo_secondary_path' => $settings->logo_secondary_path,
            'logo_secondary_url' => $this->assetUrl($settings->logo_secondary_path, $request),
            'logo_tertiary_path' => $settings->logo_tertiary_path,
            'logo_tertiary_url' => $this->assetUrl($settings->logo_tertiary_path, $request),
            'auth_background_path' => $settings->auth_background_path,
            'auth_background_url' => $this->assetUrl($settings->auth_background_path, $request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
        ]);

        $settings = SystemSetting::singleton();
        $settings->update([
            'app_name' => $data['app_name'],
            'topbar_title' => $data['app_name'],
            'login_title' => $data['app_name'],
        ]);

        return response()->json([
            'message' => 'System settings updated successfully.',
            'app_name' => $settings->app_name,
            'topbar_title' => $settings->app_name,
            'login_title' => $settings->app_name,
            'logo_path' => $settings->logo_path,
            'logo_url' => $this->assetUrl($settings->logo_path, $request),
            'logo_primary_path' => $settings->logo_primary_path,
            'logo_primary_url' => $this->assetUrl($settings->logo_primary_path, $request),
            'logo_secondary_path' => $settings->logo_secondary_path,
            'logo_secondary_url' => $this->assetUrl($settings->logo_secondary_path, $request),
            'logo_tertiary_path' => $settings->logo_tertiary_path,
            'logo_tertiary_url' => $this->assetUrl($settings->logo_tertiary_path, $request),
            'auth_background_path' => $settings->auth_background_path,
            'auth_background_url' => $this->assetUrl($settings->auth_background_path, $request),
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
            'slot' => ['nullable', 'in:primary,secondary,tertiary,legacy'],
        ]);

        $settings = SystemSetting::singleton();
        $slot = $request->input('slot', 'legacy');
        $field = self::LOGO_SLOT_FIELD[$slot] ?? 'logo_path';

        if ($settings->{$field}) {
            Storage::disk('public')->delete($settings->{$field});
        }

        $path = $request->file('logo')->store('settings/logos', 'public');
        $settings->update([$field => $path]);

        return response()->json([
            'message' => 'Logo updated successfully.',
            'slot' => $slot,
            'logo_path' => $settings->logo_path,
            'logo_url' => $this->assetUrl($settings->logo_path, $request),
            'logo_primary_path' => $settings->logo_primary_path,
            'logo_primary_url' => $this->assetUrl($settings->logo_primary_path, $request),
            'logo_secondary_path' => $settings->logo_secondary_path,
            'logo_secondary_url' => $this->assetUrl($settings->logo_secondary_path, $request),
            'logo_tertiary_path' => $settings->logo_tertiary_path,
            'logo_tertiary_url' => $this->assetUrl($settings->logo_tertiary_path, $request),
        ]);
    }

    public function removeLogo(Request $request, string $slot): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless(isset(self::LOGO_SLOT_FIELD[$slot]), 422, 'Invalid logo slot.');

        $settings = SystemSetting::singleton();
        $field = self::LOGO_SLOT_FIELD[$slot];

        $logoFields = ['logo_primary_path', 'logo_secondary_path', 'logo_tertiary_path'];
        $remainingCount = collect($logoFields)->filter(fn ($logoField) => ! empty($settings->{$logoField}))->count();
        if ($field !== 'logo_path' && $remainingCount <= 1 && ! empty($settings->{$field})) {
            return response()->json([
                'message' => 'At least one logo must remain.',
            ], 422);
        }

        if ($settings->{$field}) {
            Storage::disk('public')->delete($settings->{$field});
            $settings->update([$field => null]);
        }

        return response()->json([
            'message' => 'Logo removed successfully.',
            'slot' => $slot,
            'logo_path' => $settings->logo_path,
            'logo_url' => $this->assetUrl($settings->logo_path, $request),
            'logo_primary_path' => $settings->logo_primary_path,
            'logo_primary_url' => $this->assetUrl($settings->logo_primary_path, $request),
            'logo_secondary_path' => $settings->logo_secondary_path,
            'logo_secondary_url' => $this->assetUrl($settings->logo_secondary_path, $request),
            'logo_tertiary_path' => $settings->logo_tertiary_path,
            'logo_tertiary_url' => $this->assetUrl($settings->logo_tertiary_path, $request),
        ]);
    }

    public function uploadAuthBackground(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $request->validate([
            'background' => ['required', 'image', 'max:5120'],
        ]);

        $settings = SystemSetting::singleton();
        if ($settings->auth_background_path) {
            Storage::disk('public')->delete($settings->auth_background_path);
        }

        $path = $request->file('background')->store('settings/auth-backgrounds', 'public');
        $settings->update(['auth_background_path' => $path]);

        return response()->json([
            'message' => 'Auth background updated successfully.',
            'auth_background_path' => $path,
            'auth_background_url' => $this->assetUrl($path, $request),
        ]);
    }

    /**
     * Build absolute URL for a public disk path. Prefer the incoming request host so branding
     * URLs work when APP_URL is wrong in production (e.g. still localhost behind a reverse proxy).
     */
    private function assetUrl(?string $path, ?Request $request = null): ?string
    {
        if (! $path) {
            return null;
        }

        $relative = Storage::url($path);
        if (! str_starts_with($relative, '/')) {
            $relative = '/'.$relative;
        }

        if ($request !== null) {
            return rtrim($request->getSchemeAndHttpHost(), '/').$relative;
        }

        return url($relative);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (($user->role ?? null) === 'admin' || $user->email === 'admin@admin.com'), 403, 'Forbidden.');
    }
}
