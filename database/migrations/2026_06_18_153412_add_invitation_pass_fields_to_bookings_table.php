<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('invitation_logo_path')->nullable()->after('company_logo_original_name');
            $table->string('invitation_stall_no')->nullable()->after('invitation_logo_path');
            $table->string('invitation_company_name')->nullable()->after('invitation_stall_no');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'invitation_logo_path',
                'invitation_stall_no',
                'invitation_company_name',
            ]);
        });
    }
};
