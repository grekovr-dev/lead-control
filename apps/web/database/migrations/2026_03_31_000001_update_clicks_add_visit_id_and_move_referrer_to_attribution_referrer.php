<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clicks', function (Blueprint $table): void {
            $table->string('visit_id')->nullable()->after('visitor_id');
            $table->text('attribution_referrer')->nullable()->after('landing_url');
        });

        DB::statement('UPDATE clicks SET attribution_referrer = referrer');

        Schema::table('clicks', function (Blueprint $table): void {
            $table->dropColumn('referrer');
        });
    }

    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table): void {
            $table->text('referrer')->nullable()->after('landing_url');
        });

        DB::statement('UPDATE clicks SET referrer = attribution_referrer');

        Schema::table('clicks', function (Blueprint $table): void {
            $table->dropColumn(['visit_id', 'attribution_referrer']);
        });
    }
};
