<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_client_id')->constrained('agency_clients')->cascadeOnDelete();
            $table->string('name');
            $table->json('domains');
            $table->string('metrika_counter_id')->nullable();
            $table->string('timezone', 64)->default('Europe/Moscow');
            $table->string('token_hash', 64);
            $table->string('status', 32)->default('active');
            $table->string('email_inbound_address')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
