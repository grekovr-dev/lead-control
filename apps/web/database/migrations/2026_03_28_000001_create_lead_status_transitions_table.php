<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_status_transitions', function (Blueprint $table): void {
            $table->id();
            $table->string('lead_id');
            $table->string('from_status');
            $table->string('to_status');
            $table->string('rule_key');
            $table->dateTime('changed_at');

            $table->index(['lead_id', 'changed_at']);
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_transitions');
    }
};
