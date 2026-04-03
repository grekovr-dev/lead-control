<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->text('landing_url')->nullable()->after('origin');
            $table->string('visit_attribution_source')->nullable()->after('created_at');
            $table->string('visit_attribution_medium')->nullable()->after('visit_attribution_source');
            $table->string('visit_attribution_campaign')->nullable()->after('visit_attribution_medium');
            $table->string('visit_attribution_content')->nullable()->after('visit_attribution_campaign');
            $table->string('visit_attribution_term')->nullable()->after('visit_attribution_content');
            $table->string('visit_attribution_gclid')->nullable()->after('visit_attribution_term');
            $table->string('visit_attribution_fbclid')->nullable()->after('visit_attribution_gclid');
            $table->string('visit_attribution_msclkid')->nullable()->after('visit_attribution_fbclid');
            $table->text('visit_attribution_referrer')->nullable()->after('visit_attribution_msclkid');
            $table->string('visitor_attribution_source')->nullable()->after('visit_attribution_referrer');
            $table->string('visitor_attribution_medium')->nullable()->after('visitor_attribution_source');
            $table->string('visitor_attribution_campaign')->nullable()->after('visitor_attribution_medium');
            $table->string('visitor_attribution_content')->nullable()->after('visitor_attribution_campaign');
            $table->string('visitor_attribution_term')->nullable()->after('visitor_attribution_content');
            $table->string('visitor_attribution_gclid')->nullable()->after('visitor_attribution_term');
            $table->string('visitor_attribution_fbclid')->nullable()->after('visitor_attribution_gclid');
            $table->string('visitor_attribution_msclkid')->nullable()->after('visitor_attribution_fbclid');
            $table->text('visitor_attribution_referrer')->nullable()->after('visitor_attribution_msclkid');
        });

        DB::statement('
            UPDATE leads
            SET
                visit_attribution_source = attribution_source,
                visit_attribution_medium = attribution_medium,
                visit_attribution_campaign = attribution_campaign,
                visit_attribution_content = attribution_content,
                visit_attribution_term = attribution_term,
                visit_attribution_gclid = attribution_gclid,
                visit_attribution_fbclid = attribution_fbclid,
                visit_attribution_msclkid = attribution_msclkid,
                visitor_attribution_source = attribution_source,
                visitor_attribution_medium = attribution_medium,
                visitor_attribution_campaign = attribution_campaign,
                visitor_attribution_content = attribution_content,
                visitor_attribution_term = attribution_term,
                visitor_attribution_gclid = attribution_gclid,
                visitor_attribution_fbclid = attribution_fbclid,
                visitor_attribution_msclkid = attribution_msclkid
        ');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'attribution_source',
                'attribution_medium',
                'attribution_campaign',
                'attribution_content',
                'attribution_term',
                'attribution_gclid',
                'attribution_fbclid',
                'attribution_msclkid',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('attribution_source')->nullable()->after('created_at');
            $table->string('attribution_medium')->nullable()->after('attribution_source');
            $table->string('attribution_campaign')->nullable()->after('attribution_medium');
            $table->string('attribution_content')->nullable()->after('attribution_campaign');
            $table->string('attribution_term')->nullable()->after('attribution_content');
            $table->string('attribution_gclid')->nullable()->after('attribution_term');
            $table->string('attribution_fbclid')->nullable()->after('attribution_gclid');
            $table->string('attribution_msclkid')->nullable()->after('attribution_fbclid');
        });

        DB::statement('
            UPDATE leads
            SET
                attribution_source = visit_attribution_source,
                attribution_medium = visit_attribution_medium,
                attribution_campaign = visit_attribution_campaign,
                attribution_content = visit_attribution_content,
                attribution_term = visit_attribution_term,
                attribution_gclid = visit_attribution_gclid,
                attribution_fbclid = visit_attribution_fbclid,
                attribution_msclkid = visit_attribution_msclkid
        ');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'landing_url',
                'visit_attribution_source',
                'visit_attribution_medium',
                'visit_attribution_campaign',
                'visit_attribution_content',
                'visit_attribution_term',
                'visit_attribution_gclid',
                'visit_attribution_fbclid',
                'visit_attribution_msclkid',
                'visit_attribution_referrer',
                'visitor_attribution_source',
                'visitor_attribution_medium',
                'visitor_attribution_campaign',
                'visitor_attribution_content',
                'visitor_attribution_term',
                'visitor_attribution_gclid',
                'visitor_attribution_fbclid',
                'visitor_attribution_msclkid',
                'visitor_attribution_referrer',
            ]);
        });
    }
};
