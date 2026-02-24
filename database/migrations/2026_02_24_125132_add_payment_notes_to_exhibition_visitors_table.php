<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->text('payment_notes')->nullable()->after('payment_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->dropColumn('payment_notes');
        });
    }
};
