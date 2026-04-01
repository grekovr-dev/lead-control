<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->text('landing_url')->nullable()->after('visitor_id');
            $table->text('first_attribution_referrer')->nullable()->after('first_attribution_msclkid');
            $table->text('last_attribution_referrer')->nullable()->after('last_attribution_msclkid');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropColumn([
                'landing_url',
                'first_attribution_referrer',
                'last_attribution_referrer',
            ]);
        });
    }
};
