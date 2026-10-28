<?php

declare(strict_types=1);

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use App\Services\PublishedContentProvider;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function task20AuthenticateStaff(string $role = 'admin'): StaffUser
{
    $staff = StaffUser::factory()->active()->create([
        'role' => $role,
    ]);

    $staff->forceFill([
        'password_hash' => Hash::make('task20-password'),
    ])->save();

    test()->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'task20-password',
    ])->assertRedirect();

    return $staff;
}

function task20Draft(array $overrides = []): ContentItem
{
    return ContentItem::factory()->create($overrides);
}

it('keeps unpublished content out of public rendering and AI knowledge context', function (): void {
    task20AuthenticateStaff();

    $draft = task20Draft([
        'content_key' => 'public-faq',
        'body' => ['title' => 'Private draft', 'text' => 'Synthetic unpublished text'],
    ]);

    test()->get(route('content.show', ['contentKey' => 'public-faq', 'locale' => 'fa']))
        ->assertNotFound();

    expect(app(PublishedContentProvider::class)->forKnowledge('fa'))
        ->not->toContain(fn (array $item): bool => $item['content_key'] === $draft->content_key);
});

it('enforces admin-only content workflow actions', function (): void {
    task20AuthenticateStaff('reception');

    $draft = task20Draft();

    test()->get(route('staff.content.index'))->assertForbidden();
    test()->post(route('staff.content.review', $draft))->assertForbidden();
});

it('requires review and explicit owner approval before publishing governed content', function (): void {
    $staff = task20AuthenticateStaff('admin');

    $item = task20Draft([
        'content_key' => 'approved-clinical',
        'approval_scope' => 'clinical',
        'body' => ['title' => 'Synthetic approved title', 'text' => 'Synthetic approved clinical copy'],
    ]);

    test()->post(route('staff.content.review', $item))->assertRedirect();
    expect($item->fresh()->status)->toBe(ContentItemStatus::Review);

    test()->post(route('staff.content.publish', $item->fresh()))->assertForbidden();

    test()->post(route('staff.content.approve', $item->fresh()))->assertRedirect();

    $approved = $item->fresh();
    expect($approved->owner_approved_by)->toBe($staff->id)
        ->and($approved->owner_approved_at)->not->toBeNull();

    test()->post(route('staff.content.publish', $approved))->assertRedirect();

    $published = $item->fresh();
    expect($published->status)->toBe(ContentItemStatus::Published)
        ->and($published->published_by)->toBe($staff->id)
        ->and($published->published_at)->not->toBeNull();

    test()->get(route('content.show', ['contentKey' => $item->content_key, 'locale' => $item->locale]))
        ->assertOk()
        ->assertSee('Synthetic approved title')
        ->assertSee('Synthetic approved clinical copy');

    expect(app(PublishedContentProvider::class)->forKnowledge('fa')->pluck('content_key'))
        ->toContain($item->content_key);

    expect(\Illuminate\Support\Facades\DB::table('audit_logs')->where('target_id', $item->id)->pluck('action')->all())
        ->toEqualCanonicalizing([
            'content.submitted_for_review',
            'content.approved',
            'content.published',
        ]);
});

it('escapes content in public rendering instead of treating it as trusted HTML', function (): void {
    task20AuthenticateStaff('admin');

    $item = task20Draft([
        'content_key' => 'escaped-content',
        'body' => ['title' => '<strong>Title</strong>', 'text' => '<script>alert(1)</script>safe'],
    ]);

    test()->post(route('staff.content.review', $item))->assertRedirect();
    test()->post(route('staff.content.publish', $item->fresh()))->assertRedirect();

    test()->get(route('content.show', ['contentKey' => $item->content_key, 'locale' => $item->locale]))
        ->assertOk()
        ->assertSee('&lt;strong&gt;Title&lt;/strong&gt;', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;safe', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('creates traceable immutable published versions instead of editing them in place', function (): void {
    task20AuthenticateStaff('admin');

    $published = task20Draft([
        'content_key' => 'versioned-content',
        'status' => ContentItemStatus::Published,
        'published_at' => now('UTC'),
        'published_by' => auth('staff')->id(),
    ]);

    test()->post(route('staff.content.version', $published))->assertRedirect();

    $newVersion = ContentItem::query()
        ->where('content_key', 'versioned-content')
        ->where('version', 2)
        ->firstOrFail();

    expect($newVersion->status)->toBe(ContentItemStatus::Draft)
        ->and($newVersion->body)->toEqual($published->body)
        ->and($published->fresh()->status)->toBe(ContentItemStatus::Published);

    test()->patch(route('staff.content.update', $published->fresh()), [
        'approval_scope' => 'general',
        'body' => ['title' => 'Should not edit published', 'text' => 'blocked'],
    ])->assertForbidden();
});

it('archives the old publication when a newer version is published', function (): void {
    task20AuthenticateStaff('admin');

    $old = task20Draft([
        'content_key' => 'rolling-content',
        'status' => ContentItemStatus::Published,
        'published_at' => now('UTC'),
        'published_by' => auth('staff')->id(),
    ]);

    $new = ContentItem::factory()->create([
        'content_key' => 'rolling-content',
        'version' => 2,
        'status' => ContentItemStatus::Review,
        'approval_scope' => 'general',
    ]);

    test()->post(route('staff.content.publish', $new))->assertRedirect();

    expect($old->fresh()->status)->toBe(ContentItemStatus::Archived)
        ->and($new->fresh()->status)->toBe(ContentItemStatus::Published)
        ->and(ContentItem::query()->published()->where('content_key', 'rolling-content')->count())->toBe(1);
});

it('allows staff to preview an unpublished version while keeping it non-public', function (): void {
    task20AuthenticateStaff('admin');

    $draft = task20Draft([
        'content_key' => 'preview-only',
        'body' => ['title' => 'Preview title', 'text' => 'Preview body'],
    ]);

    test()->get(route('staff.content.preview', $draft))
        ->assertOk()
        ->assertSee('Preview title')
        ->assertSee('پیش‌نمایش داخلی');

    test()->get(route('content.show', ['contentKey' => 'preview-only', 'locale' => 'fa']))
        ->assertNotFound();
});
