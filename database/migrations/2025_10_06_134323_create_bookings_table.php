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
            $table->string('gst_number')->nullable();
            $table->json('product_profile');
            $table->boolean('has_exhibited_before')->default(false);
            $table->json('participation_years')->nullable();
            $table->boolean('is_sgcci_member')->default(false);
            $table->string('membership_type')->nullable();
            $table->json('selected_stalls');
            $table->decimal('total_area', 10, 2);
            $table->decimal('price_per_sqm', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_with_gst', 10, 2);
            $table->string('status')->default('pending_approval');

            // Approval fields
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('super_admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('super_admin_approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            // Payment fields
            $table->string('payment_link')->nullable();
            $table->timestamp('payment_link_sent_at')->nullable();
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('payment_completed_at')->nullable();

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
