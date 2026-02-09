<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="">
<head>
    @yield( 'head' )
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @yield( 'meta' )
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="/css/tw.min.css?q={!! _BUILD !!}" rel="stylesheet">
    @yield( 'link' )
    <title>ADBM:{{ $title ?? ''}}</title>
    {{-- standard alpine / tippy  --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <script src="/js/wd/master.min.js?q={!! _BUILD !!}"></script>
    {{-- user script --}}
    @yield( 'script' )
    @if ( $editable ?? false  )
    @endif
</head>
@yield( 'body' )
</html>
