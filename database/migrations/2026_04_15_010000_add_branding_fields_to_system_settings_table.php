<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->string('topbar_title')->nullable()->after('app_name');
            $table->string('topbar_subtitle')->nullable()->after('topbar_title');
            $table->string('login_title')->nullable()->after('topbar_subtitle');
            $table->string('login_subtitle')->nullable()->after('login_title');
            $table->string('logo_primary_path')->nullable()->after('auth_background_path');
            $table->string('logo_secondary_path')->nullable()->after('logo_primary_path');
            $table->string('logo_tertiary_path')->nullable()->after('logo_secondary_path');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'topbar_title',
                'topbar_subtitle',
                'login_title',
                'login_subtitle',
                'logo_primary_path',
                'logo_secondary_path',
                'logo_tertiary_path',
            ]);
        });
    }
};
