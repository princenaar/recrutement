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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('category')->nullable();
            $table->string('current_position')->nullable();
            $table->date('position_start_date')->nullable();
            $table->string('service')->nullable();
            $table->string('structure')->nullable();
            $table->string('district')->nullable();
            $table->string('region')->nullable();
            $table->string('employer')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('agent_status')->nullable();
            $table->date('entry_date')->nullable();
            $table->string('marital_status')->nullable();
            $table->timestamp('ihris_imported_at')->nullable();
            $table->timestamps();

            $table->index('structure');
            $table->index('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
