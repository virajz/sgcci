<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('description');
            $table->string('pass_background_path')->nullable()->after('logo_path');
            $table->unsignedSmallInteger('pass_qr_x')->nullable()->after('pass_background_path');
            $table->unsignedSmallInteger('pass_qr_y')->nullable()->after('pass_qr_x');
            $table->unsignedSmallInteger('pass_qr_size')->nullable()->after('pass_qr_y');
            $table->unsignedSmallInteger('pass_name_x')->nullable()->after('pass_qr_size');
            $table->unsignedSmallInteger('pass_name_y')->nullable()->after('pass_name_x');
        });
    }

    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'pass_background_path',
                'pass_qr_x',
                'pass_qr_y',
                'pass_qr_size',
                'pass_name_x',
                'pass_name_y',
            ]);
        });
    }
};
