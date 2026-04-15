<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\JsonResponse;

class ProgramMetadataController extends Controller
{
    public function statusOptions(): JsonResponse
    {
        $rows = Program::query()
            ->with(['statusOptions:id,program_id,status_code,status_label,display_order,is_active'])
            ->orderBy('program_name')
            ->get()
            ->flatMap(function (Program $program) {
                return $program->statusOptions->map(function ($option) use ($program): array {
                    return [
                        'id' => $option->id,
                        'program_id' => $program->id,
                        'program_code' => $program->program_code,
                        'program_name' => $program->program_name,
                        'status_code' => $option->status_code,
                        'status_label' => $option->status_label,
                        'display_order' => $option->display_order,
                        'is_active' => $option->is_active,
                    ];
                });
            })
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function fieldTemplates(): JsonResponse
    {
        $rows = Program::query()
            ->with(['fieldTemplates:id,program_id,field_key,field_label,field_type,field_scope,is_required,display_order,is_active'])
            ->orderBy('program_name')
            ->get()
            ->flatMap(function (Program $program) {
                return $program->fieldTemplates->map(function ($field) use ($program): array {
                    return [
                        'id' => $field->id,
                        'program_id' => $program->id,
                        'program_code' => $program->program_code,
                        'program_name' => $program->program_name,
                        'field_key' => $field->field_key,
                        'field_label' => $field->field_label,
                        'field_type' => $field->field_type,
                        'field_scope' => $field->field_scope,
                        'is_required' => $field->is_required,
                        'display_order' => $field->display_order,
                        'is_active' => $field->is_active,
                    ];
                });
            })
            ->values();

        return response()->json(['data' => $rows]);
    }
}
