<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->index();
            $table->string('category');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->text('google_maps_url');
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('reviews_count')->nullable();
            $table->string('website')->nullable();
            $table->integer('ai_score')->nullable()->index();
            $table->boolean('approved_for_outreach')->default(false);
            $table->boolean('contacted')->default(false);
            $table->enum('email_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();

            $table->unique(['business_name', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
