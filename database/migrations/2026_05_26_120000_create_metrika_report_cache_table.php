<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrika_report_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('report_type', 64);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('group_by', 16)->nullable();
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at');

            $table->unique(['site_id', 'report_type', 'date_from', 'date_to', 'group_by'], 'metrika_report_cache_unique');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrika_report_cache');
    }
};
