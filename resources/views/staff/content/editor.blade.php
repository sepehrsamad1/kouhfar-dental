@extends('layouts.staff')

@section('title', $item ? 'ویرایش محتوا' : 'محتوای جدید')

@section('content')
<section class="staff-card">
    <div class="staff-card__header">
        <div>
            <p class="staff-eyebrow">ویرایشگر امن</p>
            <h1>{{ $item ? 'نسخه '.$item->version.' — '.$item->content_key : 'محتوای جدید' }}</h1>
            <p>HTML خام ذخیره یا رندر نمی‌شود؛ متن در پیش‌نمایش با escaping استاندارد Blade نمایش داده می‌شود.</p>
        </div>
        @if ($item)<a class="staff-button" href="{{ route('staff.content.preview', $item) }}">پیش‌نمایش</a>@endif
    </div>

    <form method="post" action="{{ $item ? route('staff.content.update', $item) : route('staff.content.store') }}" class="staff-form">
        @csrf
        @if ($item) @method('PATCH') @endif
        @unless ($item)
            <label>کلید محتوا<input name="content_key" value="{{ old('content_key') }}" required pattern="[a-z0-9][a-z0-9_-]*"></label>
            <label>نوع<select name="kind" required>@foreach (AppEnumsContentKind::cases() as $kind)<option value="{{ $kind->value }}">{{ $kind->value }}</option>@endforeach</select></label>
            <label>زبان<select name="locale" required><option value="fa">fa</option><option value="en">en</option></select></label>
        @endunless
        <label>دامنه تأیید
            <select name="approval_scope" required>
                @foreach (['general'=>'عمومی','clinical'=>'ادعای بالینی','pricing'=>'قیمت','testimonial'=>'توصیه/رضایت مشتری'] as $value=>$label)
                    <option value="{{ $value }}" @selected(old('approval_scope', $item?->approval_scope ?? 'general') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>عنوان<input name="body[title]" value="{{ old('body.title', $item?->body['title'] ?? '') }}" required maxlength="240"></label>
        <label>متن<textarea name="body[text]" rows="14" maxlength="20000" required>{{ old('body.text', $item?->body['text'] ?? '') }}</textarea></label>
        <button class="staff-button" type="submit">{{ $item ? 'ذخیره نسخه' : 'ایجاد پیش‌نویس' }}</button>
    </form>
    @if ($item)
        <div class="staff-card__footer"><strong>وضعیت:</strong> {{ $item->status->value }} <span>نسخه: {{ $item->version }}</span>
        @if ($item->requiresOwnerApproval())<span>نیازمند تأیید صریح مالک/کلینیک</span>@endif</div>
    @endif
</section>
@endsection
