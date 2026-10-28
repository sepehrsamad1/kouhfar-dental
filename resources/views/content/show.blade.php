<!doctype html>
<html lang="{{ $item->locale }}" dir="{{ $item->locale === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="{{ $preview ? 'noindex, nofollow' : 'index, follow' }}">
    <title>{{ $item->body['title'] ?? 'محتوا' }}</title>
    <style>body{font-family:Estedad,sans-serif;max-width:860px;margin:3rem auto;padding:0 1rem;line-height:1.9}.notice{padding:.75rem 1rem;border:1px solid #ccc;margin-bottom:1.5rem}</style>
</head>
<body>
@if ($preview)<div class="notice">پیش‌نمایش داخلی — این نسخه لزوماً منتشر نشده است.</div>@endif
<article><h1>{{ $item->body['title'] ?? '' }}</h1><div>{{ $item->body['text'] ?? '' }}</div></article>
</body>
</html>
