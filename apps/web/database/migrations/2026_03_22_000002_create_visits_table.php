<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('visitor_id');
            $table->dateTime('started_at');
            $table->dateTime('last_touched_at');
            $table->string('first_attribution_source')->nullable();
            $table->string('first_attribution_medium')->nullable();
            $table->string('first_attribution_campaign')->nullable();
            $table->string('first_attribution_content')->nullable();
            $table->string('first_attribution_term')->nullable();
            $table->string('first_attribution_gclid')->nullable();
            $table->string('first_attribution_fbclid')->nullable();
            $table->string('first_attribution_msclkid')->nullable();
            $table->string('last_attribution_source')->nullable();
            $table->string('last_attribution_medium')->nullable();
            $table->string('last_attribution_campaign')->nullable();
            $table->string('last_attribution_content')->nullable();
            $table->string('last_attribution_term')->nullable();
            $table->string('last_attribution_gclid')->nullable();
            $table->string('last_attribution_fbclid')->nullable();
            $table->string('last_attribution_msclkid')->nullable();

            $table->index(['visitor_id', 'last_touched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
