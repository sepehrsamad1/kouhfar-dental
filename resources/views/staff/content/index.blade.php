@extends('layouts.staff')

@section('title', 'مدیریت محتوا')

@section('content')
<section class="staff-card">
    <div class="staff-card__header">
        <div>
            <p class="staff-eyebrow">مدیریت محتوا</p>
            <h1>محتوای سایت</h1>
            <p>هر نسخه باید پیش از انتشار از چرخه پیش‌نویس → بررسی → تأیید → انتشار عبور کند.</p>
        </div>
        <a class="staff-button" href="{{ route('staff.content.create') }}">محتوای جدید</a>
    </div>
    <div class="staff-table-wrap">
        <table class="staff-table">
            <thead><tr><th>کلید</th><th>نسخه</th><th>نوع</th><th>زبان</th><th>وضعیت</th><th>تأیید مالک</th><th>عملیات</th></tr></thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->content_key }}</td><td>{{ $item->version }}</td><td>{{ $item->kind->value }}</td><td>{{ $item->locale }}</td>
                    <td>{{ $item->status->value }}</td><td>{{ $item->owner_approved_at ? 'تأیید شده' : 'ثبت نشده' }}</td>
                    <td><div class="staff-actions">
                        <a href="{{ route('staff.content.edit', $item) }}">ویرایش/پیش‌نمایش</a>
                        <a href="{{ route('staff.content.preview', $item) }}">پیش‌نمایش</a>
                        @if ($item->status->value === 'draft')
                            <form method="post" action="{{ route('staff.content.review', $item) }}">@csrf<button type="submit">ارسال برای بررسی</button></form>
                        @endif
                        @if ($item->status->value === 'review')
                            <form method="post" action="{{ route('staff.content.approve', $item) }}">@csrf<button type="submit">ثبت تأیید</button></form>
                            @if (! $item->requiresOwnerApproval() || $item->owner_approved_at)
                                <form method="post" action="{{ route('staff.content.publish', $item) }}">@csrf<button type="submit">انتشار</button></form>
                            @endif
                        @endif
                        @if ($item->status->value === 'published')
                            <form method="post" action="{{ route('staff.content.version', $item) }}">@csrf<button type="submit">نسخه جدید</button></form>
                            <form method="post" action="{{ route('staff.content.archive', $item) }}">@csrf<button type="submit">بایگانی</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="7">هنوز محتوای مدیریتشده‌ای ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</section>
@endsection
