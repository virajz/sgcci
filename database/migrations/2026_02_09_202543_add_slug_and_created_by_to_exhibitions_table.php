<?php

use App\Models\Exhibition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->foreignId('created_by')->nullable()->after('end_date')->constrained('users')->nullOnDelete();
        });

        // Populate slugs for existing exhibitions
        Exhibition::whereNull('slug')->each(function (Exhibition $exhibition) {
            $slug = Str::slug($exhibition->title);
            $originalSlug = $slug;
            $counter = 1;

            while (Exhibition::where('slug', $slug)->where('id', '!=', $exhibition->id)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }

            $exhibition->updateQuietly(['slug' => $slug]);
        });

        Schema::table('exhibitions', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['slug', 'created_by']);
        });
    }
};
