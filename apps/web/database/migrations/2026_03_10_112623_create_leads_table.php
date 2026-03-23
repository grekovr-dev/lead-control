<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('visitor_id')->nullable();
            $table->string('visit_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('new');
            $table->string('origin');
            $table->dateTime('created_at');
            $table->string('attribution_source')->nullable();
            $table->string('attribution_medium')->nullable();
            $table->string('attribution_campaign')->nullable();
            $table->string('attribution_content')->nullable();
            $table->string('attribution_term')->nullable();
            $table->string('attribution_gclid')->nullable();
            $table->string('attribution_fbclid')->nullable();
            $table->string('attribution_msclkid')->nullable();

            $table->index('phone');
            $table->index('status');
            $table->index('visitor_id');
            $table->index('visit_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
