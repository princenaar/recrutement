<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('years_in_service');
            $table->date('service_entry_date')->nullable()->after('current_service');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('service_entry_date');
            $table->unsignedSmallInteger('years_in_service')->nullable()->after('current_service');
        });
    }
};
