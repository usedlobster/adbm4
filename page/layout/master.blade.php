<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @yield( 'meta-head')
    @yield( 'meta' )
    @yield( 'pre-load')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdn.jsdelivr.net">
        <link href="/css/tw.min.css?q={!! _BUILD !!}" rel="stylesheet">
        <title>ADBM:{{ $title ?? ''}}</title>
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>
        <script>
            window.Auth = {
                token: "{{ $_SESSION['_id']['atkn'] ?? '' }}",
                refreshUrl: "/auth/token-refresh" // Point to your PHP route
            };
        </script>
        <script src="/js/master.min.js"></script>

    @yield( 'post-load')
    @yield( 'css' )
    @yield( 'script' )
    @yield( 'head' )
</head>
@yield( 'body' )
@yield( 'body-end')
</html>