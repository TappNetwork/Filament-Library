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
        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['folder', 'file', 'link']);
            $table->foreignId('parent_id')->nullable()->constrained('library_items')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->enum('general_access', ['inherit', 'private', 'anyone_can_view'])->default('inherit');
            $table->string('external_url')->nullable();
            $table->text('link_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'type']);
            $table->unique(['parent_id', 'slug'], 'lib_items_parent_slug_unique');
        });

        // Add personal_folder_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('personal_folder_id')
                ->nullable()
                ->constrained('library_items')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove personal_folder_id from users table first
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['personal_folder_id']);
            $table->dropColumn('personal_folder_id');
        });

        Schema::dropIfExists('library_items');
    }
};
