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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();

            $table->string('current_structure');
            $table->string('current_service');
            $table->date('service_entry_date')->nullable();
            $table->text('motivation_note')->nullable();
            $table->string('cv_path')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->string('status')->default('draft');

            $table->timestamp('shortlisted_at')->nullable();
            $table->foreignId('shortlisted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_note')->nullable();

            $table->timestamps();

            $table->unique(['agent_id', 'position_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
