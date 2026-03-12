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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number')->unique();
            $table->string('contact_name');
            $table->string('company')->nullable();

            // Address A
            $table->string('address_type_a')->nullable();
            $table->string('address1_a')->nullable();
            $table->string('address2_a')->nullable();
            $table->string('area_a')->nullable();
            $table->string('city_a')->nullable();
            $table->string('state_a')->nullable();
            $table->string('pincode_a')->nullable();

            // Address B
            $table->string('address_type_b')->nullable();
            $table->string('address1_b')->nullable();
            $table->string('address2_b')->nullable();
            $table->string('area_b')->nullable();
            $table->string('city_b')->nullable();
            $table->string('state_b')->nullable();
            $table->string('pincode_b')->nullable();

            // Contact
            $table->string('office_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('cell_no')->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();

            // Membership
            $table->string('post')->nullable();
            $table->string('type')->nullable();

            // Personal
            $table->string('dob')->nullable();
            $table->string('aadhar_no')->nullable();
            $table->string('pan_no')->nullable();
            $table->string('gst_no')->nullable();

            // Business
            $table->string('nature_of_business')->nullable();
            $table->string('business_segment')->nullable();
            $table->string('turn_over')->nullable();
            $table->string('scale_of_business')->nullable();

            // Family
            $table->string('spouse_name')->nullable();
            $table->string('spouse_phone_no')->nullable();
            $table->string('blood_group')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
