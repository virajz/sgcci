<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->json('additional_persons')->nullable()->after('sub_business_segment');
        });
    }

    public function down(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->dropColumn('additional_persons');
        });
    }
};
