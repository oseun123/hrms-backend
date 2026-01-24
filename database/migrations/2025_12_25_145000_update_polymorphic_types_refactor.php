<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update audit_logs table
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Employee')
                ->update(['auditable_type' => 'App\Models\Hris\Employee']);

            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Department')
                ->update(['auditable_type' => 'App\Models\Hris\Department']);

            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Position')
                ->update(['auditable_type' => 'App\Models\Hris\Position']);

            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Preference')
                ->update(['auditable_type' => 'App\Models\Preference\Preference']);
        }

        // Update personal_access_tokens table
        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\Models\Employee')
                ->update(['tokenable_type' => 'App\Models\Hris\Employee']);
        }

        // Update model_has_roles/permissions if using Spatie (assumed standard tables)
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_type', 'App\Models\Employee')
                ->update(['model_type' => 'App\Models\Hris\Employee']);
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->where('model_type', 'App\Models\Employee')
                ->update(['model_type' => 'App\Models\Hris\Employee']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse updates
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Hris\Employee')
                ->update(['auditable_type' => 'App\Models\Employee']);

            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Hris\Department')
                ->update(['auditable_type' => 'App\Models\Department']);

            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Hris\Position')
                ->update(['auditable_type' => 'App\Models\Position']);

            DB::table('audit_logs')
                ->where('auditable_type', 'App\Models\Preference\Preference')
                ->update(['auditable_type' => 'App\Models\Preference']);
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\Models\Hris\Employee')
                ->update(['tokenable_type' => 'App\Models\Employee']);
        }
    }
};
