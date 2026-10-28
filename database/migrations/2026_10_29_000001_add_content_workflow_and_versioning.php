<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table): void {
            $table->string('content_key', 160)->nullable()->after('id');
            $table->unsignedInteger('version')->default(1)->after('locale');
            $table->string('approval_scope', 30)->default('general')->after('status');
            $table->uuid('created_by')->nullable()->after('approval_scope');
            $table->uuid('reviewed_by')->nullable()->after('created_by');
            $table->timestampTz('reviewed_at')->nullable()->after('reviewed_by');
            $table->uuid('owner_approved_by')->nullable()->after('reviewed_at');
            $table->timestampTz('owner_approved_at')->nullable()->after('owner_approved_by');
            $table->uuid('published_by')->nullable()->after('published_at');
            $table->timestampTz('archived_at')->nullable()->after('published_by');
        });

        DB::statement('UPDATE content_items SET content_key = kind || \'-\' || locale WHERE content_key IS NULL');

        Schema::table('content_items', function (Blueprint $table): void {
            $table->string('content_key', 160)->nullable(false)->change();
            $table->dropUnique(['kind', 'locale']);
            $table->unique(['content_key', 'locale', 'version']);
            $table->foreign('created_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->foreign('owner_approved_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->foreign('published_by')->references('id')->on('staff_users')->nullOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX content_items_one_published_version ON content_items (content_key, locale) WHERE status = \'published\'');
        DB::statement('CREATE INDEX content_items_knowledge_lookup ON content_items (content_key, locale, status, published_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS content_items_knowledge_lookup');
        DB::statement('DROP INDEX IF EXISTS content_items_one_published_version');

        Schema::table('content_items', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['owner_approved_by']);
            $table->dropForeign(['published_by']);
            $table->dropUnique(['content_key', 'locale', 'version']);
            $table->dropColumn([
                'content_key',
                'version',
                'approval_scope',
                'created_by',
                'reviewed_by',
                'reviewed_at',
                'owner_approved_by',
                'owner_approved_at',
                'published_by',
                'archived_at',
            ]);
            $table->unique(['kind', 'locale']);
        });
    }
};
