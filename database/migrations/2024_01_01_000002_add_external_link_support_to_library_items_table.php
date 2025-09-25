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
        Schema::table('library_items', function (Blueprint $table) {
            // Change the type enum to include 'link'
            $table->enum('type', ['folder', 'file', 'link'])->change();

            // Add external link fields
            $table->string('external_url')->nullable()->after('slug');
            $table->string('link_icon')->nullable()->after('external_url');
            $table->text('link_description')->nullable()->after('link_icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_items', function (Blueprint $table) {
            // Remove external link fields
            $table->dropColumn(['external_url', 'link_icon', 'link_description']);

            // Revert type enum to original
            $table->enum('type', ['folder', 'file'])->change();
        });
    }
};






