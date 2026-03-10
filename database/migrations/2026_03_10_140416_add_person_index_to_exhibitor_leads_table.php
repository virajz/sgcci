<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibitor_leads', function (Blueprint $table) {
            // Drop old unique constraint that didn't account for person_index
            $table->dropUnique(['booking_id', 'exhibition_visitor_id']);

            // null = primary visitor, 0+ = additional person array index
            $table->unsignedTinyInteger('person_index')->nullable()->after('exhibition_visitor_id');

            $table->unique(['booking_id', 'exhibition_visitor_id', 'person_index']);
        });
    }

    public function down(): void
    {
        Schema::table('exhibitor_leads', function (Blueprint $table) {
            $table->dropUnique(['booking_id', 'exhibition_visitor_id', 'person_index']);
            $table->dropColumn('person_index');
            $table->unique(['booking_id', 'exhibition_visitor_id']);
        });
    }
};
