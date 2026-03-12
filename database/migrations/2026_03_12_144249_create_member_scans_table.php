<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_scans', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number')->index();
            $table->enum('member_type', ['committee', 'sgcci']);
            $table->string('member_name');
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_scans');
    }
};
