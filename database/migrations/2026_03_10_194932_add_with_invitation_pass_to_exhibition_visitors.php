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
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->boolean('with_invitation_pass')->default(false)->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->dropColumn('with_invitation_pass');
        });
    }
};
