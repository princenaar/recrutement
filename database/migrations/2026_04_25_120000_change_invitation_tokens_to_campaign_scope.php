<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the campaign_id column + the new [agent_id, campaign_id] index
        // first. The new index covers agent_id with a leftmost prefix, which
        // lets MySQL release the old composite [agent_id, position_id] index
        // that was also serving the agent_id FK.
        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->foreignId('campaign_id')
                ->after('agent_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['agent_id', 'campaign_id']);
        });

        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
        });

        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropIndex(['agent_id', 'position_id']);
        });

        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropColumn('position_id');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        Schema::table('invitation_tokens', function (Blueprint $table) {
            $table->dropIndex(['agent_id', 'campaign_id']);
            $table->dropColumn('campaign_id');

            $table->foreignId('position_id')
                ->after('agent_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['agent_id', 'position_id']);
        });
    }
};
