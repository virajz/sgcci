<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->string('invitation_background_path')->nullable()->after('pass_name_color');
            $table->unsignedSmallInteger('invitation_stall_x')->nullable()->after('invitation_background_path');
            $table->unsignedSmallInteger('invitation_stall_y')->nullable()->after('invitation_stall_x');
            $table->unsignedSmallInteger('invitation_company_x')->nullable()->after('invitation_stall_y');
            $table->unsignedSmallInteger('invitation_company_y')->nullable()->after('invitation_company_x');
            $table->unsignedSmallInteger('invitation_logo_x')->nullable()->after('invitation_company_y');
            $table->unsignedSmallInteger('invitation_logo_y')->nullable()->after('invitation_logo_x');
            $table->unsignedSmallInteger('invitation_logo_size')->nullable()->after('invitation_logo_y');
            $table->string('invitation_text_color', 9)->nullable()->after('invitation_logo_size');
        });
    }

    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->dropColumn([
                'invitation_background_path',
                'invitation_stall_x',
                'invitation_stall_y',
                'invitation_company_x',
                'invitation_company_y',
                'invitation_logo_x',
                'invitation_logo_y',
                'invitation_logo_size',
                'invitation_text_color',
            ]);
        });
    }
};
