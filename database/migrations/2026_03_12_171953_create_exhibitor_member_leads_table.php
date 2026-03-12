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
        Schema::create('exhibitor_member_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('membership_number');
            $table->enum('member_type', ['committee', 'sgcci']);
            $table->string('member_name');
            $table->string('member_phone')->nullable();
            $table->timestamp('captured_at');

            $table->unique(['booking_id', 'membership_number']);
            $table->index('membership_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibitor_member_leads');
    }
};
