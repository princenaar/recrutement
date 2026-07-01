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
        Schema::create('district_chief_diplomas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_chief_academic_profile_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('obtained_year');
            $table->string('scan_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_chief_diplomas');
    }
};
