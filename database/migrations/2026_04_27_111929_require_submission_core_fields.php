<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('submissions')->whereNull('current_structure')->update([
            'current_structure' => 'Non renseigné',
        ]);
        DB::table('submissions')->whereNull('current_service')->update([
            'current_service' => 'Non renseigné',
        ]);
        DB::table('submissions')->whereNull('cv_path')->update([
            'cv_path' => 'submissions/missing/cv.pdf',
        ]);

        Schema::table('submissions', function (Blueprint $table) {
            $table->string('current_structure')->nullable(false)->change();
            $table->string('current_service')->nullable(false)->change();
            $table->string('cv_path')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('current_structure')->nullable()->change();
            $table->string('current_service')->nullable()->change();
            $table->string('cv_path')->nullable()->change();
        });
    }
};
