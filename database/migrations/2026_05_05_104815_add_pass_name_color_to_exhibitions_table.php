<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->string('pass_name_color', 9)->nullable()->after('pass_name_y');
        });
    }

    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->dropColumn('pass_name_color');
        });
    }
};
