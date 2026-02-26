<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table): void {
            $table->string('business_segment')->nullable()->change();
            $table->string('sub_business_segment')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table): void {
            $table->string('business_segment')->nullable(false)->change();
            $table->string('sub_business_segment')->nullable(false)->change();
        });
    }
};
