<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('visitor_id');
            $table->text('landing_url');
            $table->text('referrer')->nullable();
            $table->dateTime('occurred_at');
            $table->string('attribution_source')->nullable();
            $table->string('attribution_medium')->nullable();
            $table->string('attribution_campaign')->nullable();
            $table->string('attribution_content')->nullable();
            $table->string('attribution_term')->nullable();
            $table->string('attribution_gclid')->nullable();
            $table->string('attribution_fbclid')->nullable();
            $table->string('attribution_msclkid')->nullable();

            $table->index(['visitor_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
