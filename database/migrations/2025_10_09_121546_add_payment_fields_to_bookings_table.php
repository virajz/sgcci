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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_transaction_id')->nullable()->after('payment_completed_at');
            $table->string('payment_method')->nullable()->after('payment_transaction_id');
            $table->string('payment_status')->nullable()->after('payment_method');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_status');
            $table->json('payment_response')->nullable()->after('payment_amount');
            $table->string('payment_tracking_id')->nullable()->after('payment_response');
            $table->string('payment_bank_ref_no')->nullable()->after('payment_tracking_id');
            $table->timestamp('payment_initiated_at')->nullable()->after('payment_bank_ref_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_transaction_id',
                'payment_method',
                'payment_status',
                'payment_amount',
                'payment_response',
                'payment_tracking_id',
                'payment_bank_ref_no',
                'payment_initiated_at',
            ]);
        });
    }
};
