<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('email_inbound_seo')->nullable()->after('email_inbound_address');
            $table->string('email_inbound_other')->nullable()->after('email_inbound_seo');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['email_inbound_seo', 'email_inbound_other']);
        });
    }
};
