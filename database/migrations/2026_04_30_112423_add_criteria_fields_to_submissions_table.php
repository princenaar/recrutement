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
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('cv_path')->nullable()->change();
            $table->json('responses')->nullable()->after('cv_path');
            $table->json('region_choices')->nullable()->after('responses');
            $table->json('score_breakdown')->nullable()->after('region_choices');
            $table->unsignedSmallInteger('raw_score')->nullable()->after('score_breakdown');
            $table->decimal('normalized_score', 5, 2)->nullable()->after('raw_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'responses',
                'region_choices',
                'score_breakdown',
                'raw_score',
                'normalized_score',
            ]);
            $table->string('cv_path')->nullable(false)->change();
        });
    }
};
