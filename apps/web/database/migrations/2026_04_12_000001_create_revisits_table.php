<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisits', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('visitor_id');
            $table->string('visit_id');
            $table->text('landing_url');
            $table->dateTime('occurred_at');

            $table->index(['visitor_id', 'occurred_at']);
            $table->index(['visit_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisits');
    }
};
