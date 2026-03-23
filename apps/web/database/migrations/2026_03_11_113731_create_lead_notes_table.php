<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('lead_id');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
    }
};
