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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 8)->unique();
            $table->foreignId('exhibition_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name');
            $table->string('contact_person');
            $table->string('phone_code', 10);
            $table->string('phone_number', 20);
            $table->string('email');
            $table->string('city');
            $table->json('product_profile');
            $table->boolean('has_exhibited_before')->default(false);
            $table->json('participation_years')->nullable();
            $table->boolean('is_sgcci_member')->default(false);
            $table->string('membership_type')->nullable();
            $table->json('selected_stalls');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
