<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'app_name',
        'topbar_title',
        'topbar_subtitle',
        'login_title',
        'login_subtitle',
        'logo_path',
        'logo_primary_path',
        'logo_secondary_path',
        'logo_tertiary_path',
        'auth_background_path',
        'backup_frequency',
        'backup_run_at_time',
        'backup_timezone',
        'backup_last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'backup_last_run_at' => 'datetime',
        ];
    }

    public static function singleton(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            [
                'app_name' => 'OIPSMS',
                'topbar_title' => 'Overseas Workers Welfare Administration - Region 9',
                'topbar_subtitle' => 'Integrated Programs and Services Monitoring System',
                'login_title' => 'Overseas Workers Welfare Administration - Region 9',
                'login_subtitle' => 'Integrated Programs and Services Monitoring System',
                'backup_frequency' => 'off',
                'backup_run_at_time' => '02:00',
                'backup_timezone' => 'Asia/Manila',
            ]
        );
    }
}
