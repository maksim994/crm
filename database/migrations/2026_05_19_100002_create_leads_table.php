<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();

            $table->string('channel', 16);
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('form_description')->nullable();

            $table->string('lead_status', 32)->default('not_processed');
            $table->string('manager_name')->nullable();

            $table->string('inn', 12)->nullable();
            $table->string('city')->nullable();
            $table->string('product_request')->nullable();
            $table->unsignedInteger('sku_count')->nullable();
            $table->text('manager_comment')->nullable();
            $table->decimal('expected_amount', 14, 2)->nullable();

            $table->string('metrika_client_id', 64)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_campaign_first')->nullable();
            $table->string('advertising_channel')->nullable();
            $table->string('landing_domain')->nullable();
            $table->string('visitor_ip', 45)->nullable();

            $table->string('call_recording_url')->nullable();
            $table->unsignedInteger('call_duration_sec')->nullable();

            $table->boolean('is_duplicate')->default(false);

            $table->string('acc_status')->nullable();
            $table->text('acc_comment')->nullable();
            $table->string('ppc_status')->nullable();
            $table->text('ppc_comment')->nullable();
            $table->string('acc_ppc_summary', 16)->nullable();

            $table->json('raw_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['site_id', 'created_at']);
            $table->index(['site_id', 'phone']);
            $table->index(['site_id', 'email']);
            $table->index('lead_status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
