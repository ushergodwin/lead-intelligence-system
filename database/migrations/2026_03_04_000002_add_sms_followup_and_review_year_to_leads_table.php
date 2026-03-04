<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('sms_follow_up_due_at')->nullable()->after('sms_sent_at');
            $table->boolean('sms_follow_up_sent')->default(false)->after('sms_follow_up_due_at');
            $table->smallInteger('last_review_year')->nullable()->after('reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['sms_follow_up_due_at', 'sms_follow_up_sent', 'last_review_year']);
        });
    }
};
