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
            $table->string('profile_message_type')->nullable()->after('company_logo_original_name'); // text, image, video
            $table->text('profile_message_text')->nullable()->after('profile_message_type');
            $table->string('profile_message_media')->nullable()->after('profile_message_text');
            $table->string('profile_message_media_original_name')->nullable()->after('profile_message_media');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'profile_message_type',
                'profile_message_text',
                'profile_message_media',
                'profile_message_media_original_name',
            ]);
        });
    }
};
