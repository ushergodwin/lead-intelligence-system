<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('follow_up_due_at')->nullable()->after('email_status');
            $table->boolean('follow_up_sent')->default(false)->after('follow_up_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['follow_up_due_at', 'follow_up_sent']);
        });
    }
};
