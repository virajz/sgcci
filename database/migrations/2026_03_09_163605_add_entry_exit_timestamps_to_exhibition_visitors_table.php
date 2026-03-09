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
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->timestamp('entered_at')->nullable()->after('payment_notes');
            $table->timestamp('exited_at')->nullable()->after('entered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->dropColumn(['entered_at', 'exited_at']);
        });
    }
};
