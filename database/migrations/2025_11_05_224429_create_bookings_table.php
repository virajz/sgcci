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

            // Company & Contact Information
            $table->string('brand_name');
            $table->string('contact_person');
            $table->string('phone_code', 10);
            $table->string('phone_number', 20);
            $table->string('email');
            $table->string('city');
            $table->string('gst_number')->nullable();

            // Product & Participation Details
            $table->json('product_profile');
            $table->boolean('has_exhibited_before')->default(false);
            $table->json('participation_years')->nullable();
            $table->boolean('is_sgcci_member')->default(false);
            $table->string('membership_type')->nullable();

            // Stall & Pricing Details
            $table->json('selected_stalls');
            $table->string('space_type')->default('standard');
            $table->decimal('total_area', 10, 2);
            $table->decimal('price_per_sqm', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('price_after_discount', 10, 2)->default(0);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_with_gst', 10, 2);

            // Status & Workflow
            $table->string('status')->default('pending_approval');
            $table->boolean('is_manual_block')->default(false);

            // Approval Fields
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('super_admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('super_admin_approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable();

            // Payment Fields
            $table->string('payment_link')->nullable();
            $table->timestamp('payment_link_sent_at')->nullable();
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->json('payment_response')->nullable();
            $table->string('payment_tracking_id')->nullable();
            $table->string('payment_bank_ref_no')->nullable();
            $table->timestamp('payment_initiated_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('booking_code');
            $table->index('exhibition_id');
            $table->index('status');
            $table->index('is_manual_block');
            $table->index(['status', 'is_manual_block']);
            $table->index('email');
            $table->index('created_at');
            // Indexes for search performance
            $table->index('brand_name');
            $table->index('contact_person');
            $table->index('phone_number');
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
