<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theses', function (Blueprint $table) {
            // Adding them as nullable strings so they can be empty before grading
            // 'after' places them neatly in your database GUI
            $table->string('grade')->nullable()->after('status');
            $table->string('grade_status')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('theses', function (Blueprint $table) {
            // Drops the columns if you ever need to rollback
            $table->dropColumn(['grade', 'grade_status']);
        });
    }
};
