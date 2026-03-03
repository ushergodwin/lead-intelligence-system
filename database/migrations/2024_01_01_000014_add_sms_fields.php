<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which channel (email / sms) each log entry belongs to
        Schema::table('outreach_logs', function (Blueprint $table) {
            $table->string('channel', 10)->default('email')->after('lead_id');
        });

        // Quick-access flag on the lead itself so we can show SMS sent state in the UI
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('sms_sent_at')->nullable()->after('follow_up_sent');
        });
    }

    public function down(): void
    {
        Schema::table('outreach_logs', function (Blueprint $table) {
            $table->dropColumn('channel');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('sms_sent_at');
        });
    }
};
