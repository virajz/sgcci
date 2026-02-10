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
        Schema::create('exhibition_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('registration_code', 10)->unique();
            $table->foreignId('exhibition_id')->constrained()->cascadeOnDelete();

            // Visitor Information
            $table->string('phone_number', 20);
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('state');
            $table->string('city');
            $table->string('email')->nullable();
            $table->string('business_segment');
            $table->string('sub_business_segment');

            // Tracking
            $table->string('source')->nullable();

            // Payment Fields
            $table->string('status')->default('pending');
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->string('payment_tracking_id')->nullable();
            $table->string('payment_bank_ref_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->json('payment_response')->nullable();
            $table->timestamp('payment_initiated_at')->nullable();
            $table->timestamp('payment_completed_at')->nullable();

            $table->timestamps();

            // Composite unique: one phone per exhibition
            $table->unique(['exhibition_id', 'phone_number'], 'exhibition_visitor_phone_unique');

            // Indexes
            $table->index('status');
            $table->index('phone_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibition_visitors');
    }
};
