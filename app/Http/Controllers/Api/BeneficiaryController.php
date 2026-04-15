<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BeneficiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $beneficiaries = Beneficiary::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('beneficiary_no', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('ofw_name', 'like', "%{$search}%")
                        ->orWhere('field_office', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json(['data' => $beneficiaries]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'barangay' => ['nullable', 'string', 'max:120'],
            'municipality' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'field_office' => ['nullable', 'string', 'max:120'],
            'ofw_name' => ['nullable', 'string', 'max:255'],
            'relationship_to_ofw' => ['nullable', 'string', 'max:120'],
            'profile_photo' => ['nullable', 'image', 'max:3072'],
            'category' => ['nullable', 'string', 'max:120'],
            'jobsite' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->ensureNoDuplicateName($data['last_name'], $data['first_name'], $data['middle_name'] ?? null);
        $data['beneficiary_no'] = $this->generateBeneficiaryNo();
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('beneficiaries/photos', 'public');
        }
        unset($data['profile_photo']);

        $beneficiary = Beneficiary::query()->create($data);
        $this->logAction($request, 'CREATE', $beneficiary, null, $beneficiary->toArray());

        return response()->json([
            'data' => $beneficiary,
            'message' => 'Beneficiary created successfully.',
        ], 201);
    }

    public function show(Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary->load(['enrollments.program']);

        return response()->json(['data' => $beneficiary]);
    }

    public function update(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $oldValues = $beneficiary->toArray();

        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'barangay' => ['nullable', 'string', 'max:120'],
            'municipality' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'field_office' => ['nullable', 'string', 'max:120'],
            'ofw_name' => ['nullable', 'string', 'max:255'],
            'relationship_to_ofw' => ['nullable', 'string', 'max:120'],
            'profile_photo' => ['nullable', 'image', 'max:3072'],
            'category' => ['nullable', 'string', 'max:120'],
            'jobsite' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->ensureNoDuplicateName(
            $data['last_name'],
            $data['first_name'],
            $data['middle_name'] ?? null,
            $beneficiary->id
        );

        if ($request->hasFile('profile_photo')) {
            if (! empty($beneficiary->profile_photo_path)) {
                Storage::disk('public')->delete($beneficiary->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('beneficiaries/photos', 'public');
        }
        unset($data['profile_photo']);

        $beneficiary->update($data);
        $fresh = $beneficiary->fresh();
        $this->logAction($request, 'UPDATE', $fresh, $oldValues, $fresh->toArray());

        return response()->json([
            'data' => $fresh,
            'message' => 'Beneficiary updated successfully.',
        ]);
    }

    public function destroy(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $oldValues = $beneficiary->toArray();
        $beneficiaryId = $beneficiary->id;
        if (! empty($beneficiary->profile_photo_path)) {
            Storage::disk('public')->delete($beneficiary->profile_photo_path);
        }
        $beneficiary->delete();

        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => 'DELETE',
            'table_name' => 'beneficiaries',
            'record_id' => $beneficiaryId,
            'description' => 'Beneficiary DELETE operation',
            'old_values' => $oldValues,
            'new_values' => null,
            'action_time' => now(),
        ]);

        return response()->json(['message' => 'Beneficiary deleted successfully.']);
    }

    private function logAction(Request $request, string $action, Beneficiary $beneficiary, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => $action,
            'table_name' => 'beneficiaries',
            'record_id' => $beneficiary->id,
            'description' => "Beneficiary {$action} operation",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'action_time' => now(),
        ]);
    }

    private function ensureNoDuplicateName(string $lastName, string $firstName, ?string $middleName = null, ?int $exceptId = null): void
    {
        $query = Beneficiary::query()
            ->whereRaw('LOWER(last_name) = ?', [strtolower(trim($lastName))])
            ->whereRaw('LOWER(first_name) = ?', [strtolower(trim($firstName))])
            ->whereRaw('LOWER(COALESCE(middle_name, "")) = ?', [strtolower(trim((string) $middleName))]);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Duplicate beneficiary name detected. Please verify if this person is already registered.',
            ]);
        }
    }

    private function generateBeneficiaryNo(): string
    {
        $year = now()->year;
        $prefix = "OWWA-R9-{$year}-";

        $last = Beneficiary::query()
            ->where('beneficiary_no', 'like', "{$prefix}%")
            ->orderByDesc('beneficiary_no')
            ->value('beneficiary_no');

        $next = 1;
        if ($last) {
            $lastSeq = (int) substr($last, strrpos($last, '-') + 1);
            $next = $lastSeq + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
