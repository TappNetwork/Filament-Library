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
        Schema::create('library_item_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('library_item_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_item_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['library_item_id', 'library_item_tag_id'], 'library_item_tag_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_item_tag_pivot');
        Schema::dropIfExists('library_item_tags');
    }
};
