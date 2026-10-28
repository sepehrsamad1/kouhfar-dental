<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Http\Requests\ContentItemStoreRequest;
use App\Http\Requests\ContentItemUpdateRequest;
use App\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ContentItemController
{
    public function index(): View
    {
        Gate::authorize('viewAny', ContentItem::class);

        return view('staff.content.index', [
            'items' => ContentItem::query()
                ->orderBy('content_key')
                ->orderByDesc('version')
                ->paginate(30),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ContentItem::class);

        return view('staff.content.editor', [
            'item' => null,
        ]);
    }

    public function store(ContentItemStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', ContentItem::class);

        /** @var StaffUser $actor */
        $actor = $request->user('staff');

        $item = ContentItem::query()->create([
            ...$request->validated(),
            'version' => 1,
            'status' => ContentItemStatus::Draft,
            'created_by' => $actor->id,
        ]);

        $this->audit($actor, 'content.created', $item);

        return redirect()->route('staff.content.edit', $item)->with('success', 'نسخه پیش‌نویس ایجاد شد.');
    }

    public function edit(ContentItem $contentItem): View
    {
        Gate::authorize('view', $contentItem);

        return view('staff.content.editor', ['item' => $contentItem]);
    }

    public function update(ContentItemUpdateRequest $request, ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('update', $contentItem);

        /** @var StaffUser $actor */
        $actor = $request->user('staff');

        $contentItem->fill($request->validated());
        $contentItem->save();

        $this->audit($actor, 'content.updated', $contentItem);

        return back()->with('success', 'محتوا به‌روزرسانی شد.');
    }

    public function preview(ContentItem $contentItem): View
    {
        Gate::authorize('view', $contentItem);

        return view('content.show', [
            'item' => $contentItem,
            'preview' => true,
        ]);
    }

    public function submitReview(ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('submitReview', $contentItem);

        /** @var StaffUser $actor */
        $actor = request()->user('staff');

        $contentItem->update([
            'status' => ContentItemStatus::Review,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $this->audit($actor, 'content.submitted_for_review', $contentItem);

        return back()->with('success', 'محتوا برای بررسی ارسال شد.');
    }

    public function approve(ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('approve', $contentItem);

        /** @var StaffUser $actor */
        $actor = request()->user('staff');

        $contentItem->update([
            'owner_approved_by' => $actor->id,
            'owner_approved_at' => now('UTC'),
            'reviewed_by' => $actor->id,
            'reviewed_at' => now('UTC'),
        ]);

        $this->audit($actor, 'content.approved', $contentItem);

        return back()->with('success', 'تأیید صریح ثبت شد. محتوا هنوز منتشر نشده است.');
    }

    public function publish(ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('publish', $contentItem);

        /** @var StaffUser $actor */
        $actor = request()->user('staff');

        DB::transaction(function () use ($contentItem, $actor): void {
            ContentItem::query()
                ->where('content_key', $contentItem->content_key)
                ->where('locale', $contentItem->locale)
                ->where('id', '!=', $contentItem->id)
                ->where('status', ContentItemStatus::Published->value)
                ->update([
                    'status' => ContentItemStatus::Archived->value,
                    'archived_at' => now('UTC'),
                ]);

            $contentItem->update([
                'status' => ContentItemStatus::Published,
                'published_by' => $actor->id,
                'published_at' => now('UTC'),
            ]);
        });

        $this->audit($actor, 'content.published', $contentItem);

        return back()->with('success', 'محتوا منتشر شد.');
    }

    public function archive(ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('archive', $contentItem);

        /** @var StaffUser $actor */
        $actor = request()->user('staff');

        $contentItem->update([
            'status' => ContentItemStatus::Archived,
            'archived_at' => now('UTC'),
        ]);

        $this->audit($actor, 'content.archived', $contentItem);

        return back()->with('success', 'نسخه منتشرشده بایگانی شد.');
    }

    public function createVersion(ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('createVersion', $contentItem);

        /** @var StaffUser $actor */
        $actor = request()->user('staff');

        $version = (int) ContentItem::query()
            ->where('content_key', $contentItem->content_key)
            ->where('locale', $contentItem->locale)
            ->max('version') + 1;

        $item = ContentItem::query()->create([
            'content_key' => $contentItem->content_key,
            'kind' => $contentItem->kind,
            'locale' => $contentItem->locale,
            'version' => $version,
            'body' => $contentItem->body,
            'status' => ContentItemStatus::Draft,
            'approval_scope' => $contentItem->approval_scope,
            'created_by' => $actor->id,
        ]);

        $this->audit($actor, 'content.version_created', $item, [
            'source_version' => $contentItem->version,
        ]);

        return redirect()->route('staff.content.edit', $item)->with('success', 'نسخه جدید ایجاد شد.');
    }

    public function show(string $contentKey, string $locale): View
    {
        $item = ContentItem::query()
            ->published()
            ->where('content_key', $contentKey)
            ->where('locale', $locale)
            ->orderByDesc('version')
            ->firstOrFail();

        return view('content.show', [
            'item' => $item,
            'preview' => false,
        ]);
    }

    private function audit(StaffUser $actor, string $action, ContentItem $item, array $meta = []): void
    {
        DB::table('audit_logs')->insert([
            'id' => (string) str()->uuid(),
            'actor_id' => $actor->id,
            'action' => $action,
            'target_type' => ContentItem::class,
            'target_id' => $item->id,
            'occurred_at' => now('UTC'),
            'request_id' => request()->attributes->get('request_id'),
            'meta' => json_encode([
                'content_key' => $item->content_key,
                'locale' => $item->locale,
                'version' => $item->version,
                ...$meta,
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
