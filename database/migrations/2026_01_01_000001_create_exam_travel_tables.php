<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('student')->index();
            $t->string('phone', 20)->nullable();
            $t->string('current_city')->nullable();
            $t->string('state')->nullable();
            $t->string('preferred_language', 10)->default('en');
            $t->string('account_status')->default('active')->index();
            $t->timestamp('deletion_requested_at')->nullable();
        });

        Schema::create('examinations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('conducting_authority')->nullable();
            $t->string('official_url')->nullable();
            $t->text('description')->nullable();
            $t->string('status')->default('active')->index();
            $t->timestamps();
        });

        Schema::create('exam_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->integer('year')->nullable();
            $t->date('exam_date')->nullable();
            $t->string('status')->default('upcoming')->index();
            $t->timestamps();
        });

        Schema::create('exam_centres', function (Blueprint $t) {
            $t->id();
            $t->foreignId('examination_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('exam_session_id')->nullable()->constrained()->nullOnDelete();
            $t->string('centre_code')->index();
            $t->string('name')->index();
            $t->text('address');
            $t->string('city')->index();
            $t->string('state')->index();
            $t->string('pin_code', 10)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('verification_status')->default('unverified')->index();
            $t->string('source_name')->nullable();
            $t->string('source_url')->nullable();
            $t->timestamp('last_verified_at')->nullable();
            $t->boolean('is_demo')->default(false);
            $t->string('status')->default('active')->index();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['centre_code', 'examination_id', 'exam_session_id']);
        });

        Schema::create('import_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('file_name');
            $t->string('status')->default('pending')->index();
            $t->integer('total_rows')->default(0);
            $t->integer('valid_rows')->default(0);
            $t->integer('error_rows')->default(0);
            $t->integer('duplicate_rows')->default(0);
            $t->integer('imported_rows')->default(0);
            $t->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $t->integer('row_number');
            $t->json('data');
            $t->string('status');
            $t->text('error_message')->nullable();
            $t->timestamps();
        });

        Schema::create('itineraries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('exam_centre_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->string('origin_city');
            $t->decimal('origin_lat', 10, 7)->nullable();
            $t->decimal('origin_lng', 10, 7)->nullable();
            $t->string('destination_city');
            $t->timestamp('departure_at')->nullable();
            $t->decimal('budget', 10, 2)->nullable();
            $t->string('preferred_modes')->nullable();
            $t->json('options_snapshot')->nullable();
            $t->text('notes')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });

        Schema::create('local_services', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_centre_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('category')->index();
            $t->string('city')->index();
            $t->text('address')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('phone')->nullable();
            $t->string('website')->nullable();
            $t->decimal('rating', 3, 1)->nullable();
            $t->integer('reviews_count')->nullable();
            $t->decimal('price_from', 10, 2)->nullable();
            $t->string('source')->default('curated');
            $t->boolean('is_demo')->default(false);
            $t->timestamp('last_verified_at')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });

        Schema::create('saved_places', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('local_service_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'local_service_id']);
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('question');
            $t->text('answer');
            $t->string('category')->index();
            $t->string('source_url')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('community_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('examination_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->text('body');
            $t->string('city')->nullable()->index();
            $t->string('category')->default('general');
            $t->string('status')->default('visible')->index();
            $t->timestamps();
        });

        Schema::create('community_replies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->string('status')->default('visible');
            $t->timestamps();
        });

        Schema::create('content_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reporter_id')->constrained('users');
            $t->string('reportable_type');
            $t->unsignedBigInteger('reportable_id');
            $t->text('reason');
            $t->string('status')->default('pending')->index();
            $t->text('moderator_note')->nullable();
            $t->timestamps();
            $t->index(['reportable_type', 'reportable_id']);
        });

        Schema::create('chat_conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $t->string('role');
            $t->text('content');
            $t->json('sources')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action')->index();
            $t->string('entity_type')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->text('summary')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('search_logs', function (Blueprint $t) {
            $t->id();
            $t->string('type')->index();
            $t->string('query_summary')->nullable();
            $t->integer('results_count')->default(0);
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['search_logs', 'audit_logs', 'chat_messages', 'chat_conversations', 'content_reports', 'community_replies', 'community_posts', 'faqs', 'saved_places', 'local_services', 'itineraries', 'import_rows', 'import_batches', 'exam_centres', 'exam_sessions', 'examinations'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'phone', 'current_city', 'state', 'preferred_language', 'account_status', 'deletion_requested_at']);
        });
    }
};
