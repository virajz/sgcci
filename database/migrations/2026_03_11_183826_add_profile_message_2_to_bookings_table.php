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
            $table->string('profile_message_2_type')->nullable()->after('profile_message_media_original_name');
            $table->text('profile_message_2_text')->nullable()->after('profile_message_2_type');
            $table->string('profile_message_2_media')->nullable()->after('profile_message_2_text');
            $table->string('profile_message_2_media_original_name')->nullable()->after('profile_message_2_media');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'profile_message_2_type',
                'profile_message_2_text',
                'profile_message_2_media',
                'profile_message_2_media_original_name',
            ]);
        });
    }
};
