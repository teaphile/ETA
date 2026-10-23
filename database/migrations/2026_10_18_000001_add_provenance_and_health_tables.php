<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the standard data-reliability metadata model, integration health telemetry,
 * funding/abuse bookkeeping and the indexes needed by the search screens.
 *
 * The metadata vocabulary is intentionally shared across tables (see
 * App\Integrations\Support\DataStatus) instead of being forced into every table in
 * identical form: each table gets only the fields its module genuinely uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_centres', function (Blueprint $t) {
            $t->string('district')->nullable()->after('state');
            $t->string('data_status')->default('unverified')->index()->after('verification_status');
            $t->string('provider_reference')->nullable();
            $t->string('error_or_limitation')->nullable();
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->timestamp('retrieved_at')->nullable();
            $t->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
        });

        Schema::table('exam_centres', function (Blueprint $t) {
            $t->index(['city', 'status'], 'exam_centres_city_status_index');
            $t->index(['state', 'status'], 'exam_centres_state_status_index');
            $t->index(['latitude', 'longitude'], 'exam_centres_coords_index');
        });

        Schema::table('local_services', function (Blueprint $t) {
            $t->string('source_name')->nullable()->after('source');
            $t->string('source_type')->default('curated')->after('source_name');
            $t->string('source_url')->nullable();
            $t->string('provider_reference')->nullable();
            $t->string('data_status')->default('unverified')->index()->after('source_type');
            $t->string('opening_hours')->nullable();
            $t->string('error_or_limitation')->nullable();
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->timestamp('retrieved_at')->nullable();
        });

        Schema::table('local_services', function (Blueprint $t) {
            $t->index(['exam_centre_id', 'category', 'status'], 'local_services_near_index');
            $t->index(['city', 'category'], 'local_services_city_cat_index');
        });

        Schema::table('community_posts', function (Blueprint $t) {
            $t->boolean('is_locked')->default(false);
            $t->unsignedInteger('reports_count')->default(0);
            $t->timestamp('last_reply_at')->nullable();
        });

        Schema::table('community_posts', function (Blueprint $t) {
            $t->index(['status', 'created_at'], 'community_posts_status_created_index');
        });

        Schema::table('chat_messages', function (Blueprint $t) {
            $t->string('provider')->nullable()->after('role');
            $t->string('category')->nullable()->after('provider');
            $t->string('limitation')->nullable();
            $t->timestamp('reported_at')->nullable();
            $t->string('report_reason')->nullable();
        });

        Schema::table('search_logs', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->index(['type', 'created_at'], 'search_logs_type_created_index');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('last_login_at')->nullable();
            $t->unsignedInteger('reports_received_count')->default(0);
            $t->timestamp('terms_accepted_at')->nullable();
        });

        Schema::table('itineraries', function (Blueprint $t) {
            $t->string('data_status')->default('estimated');
            $t->text('data_limitations')->nullable();
            $t->timestamp('last_viewed_at')->nullable();
        });

        Schema::table('content_reports', function (Blueprint $t) {
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
        });

        Schema::table('import_batches', function (Blueprint $t) {
            $t->unsignedInteger('updated_rows')->default(0);
            $t->text('notes')->nullable();
        });

        if (! Schema::hasTable('integration_events')) {
            Schema::create('integration_events', function (Blueprint $t) {
                $t->id();
                $t->string('provider')->index();
                $t->string('operation');
                $t->boolean('success')->default(true);
                $t->unsignedSmallInteger('http_status')->nullable();
                $t->unsignedInteger('latency_ms')->nullable();
                $t->string('error_category')->nullable()->index();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['provider', 'created_at']);
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('type');
                $t->morphs('notifiable');
                $t->text('data');
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
            });
        }

        // Backfill the new shared status field from the pre-existing column so no
        // record is left in an ambiguous state.
        DB::table('exam_centres')->update(['data_status' => DB::raw('verification_status')]);
        DB::table('local_services')->update([
            'data_status' => DB::raw("CASE WHEN is_demo = 1 THEN 'unverified' ELSE 'admin_verified' END"),
            'source_name' => DB::raw('source'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('integration_events');

        Schema::table('import_batches', function (Blueprint $t) {
            $t->dropColumn(['updated_rows', 'notes']);
        });

        Schema::table('content_reports', function (Blueprint $t) {
            $t->dropConstrainedForeignId('resolved_by');
            $t->dropColumn('resolved_at');
        });

        Schema::table('itineraries', function (Blueprint $t) {
            $t->dropColumn(['data_status', 'data_limitations', 'last_viewed_at']);
        });

        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['last_login_at', 'reports_received_count', 'terms_accepted_at']);
        });

        Schema::table('search_logs', function (Blueprint $t) {
            $t->dropIndex('search_logs_type_created_index');
            $t->dropConstrainedForeignId('user_id');
        });

        Schema::table('chat_messages', function (Blueprint $t) {
            $t->dropColumn(['provider', 'category', 'limitation', 'reported_at', 'report_reason']);
        });

        Schema::table('community_posts', function (Blueprint $t) {
            $t->dropIndex('community_posts_status_created_index');
            $t->dropColumn(['is_locked', 'reports_count', 'last_reply_at']);
        });

        Schema::table('local_services', function (Blueprint $t) {
            $t->dropIndex('local_services_near_index');
            $t->dropIndex('local_services_city_cat_index');
            $t->dropColumn([
                'source_name', 'source_type', 'source_url', 'provider_reference', 'data_status',
                'opening_hours', 'error_or_limitation', 'valid_from', 'valid_until', 'retrieved_at',
            ]);
        });

        Schema::table('exam_centres', function (Blueprint $t) {
            $t->dropIndex('exam_centres_city_status_index');
            $t->dropIndex('exam_centres_state_status_index');
            $t->dropIndex('exam_centres_coords_index');
            $t->dropConstrainedForeignId('import_batch_id');
            $t->dropColumn([
                'district', 'data_status', 'provider_reference', 'error_or_limitation',
                'valid_from', 'valid_until', 'retrieved_at',
            ]);
        });
    }
};
