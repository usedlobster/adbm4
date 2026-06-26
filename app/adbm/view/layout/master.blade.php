<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="">
<head>
    @yield( 'head' )
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @yield( 'meta' )
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="/css/tw.min.css?q={!! _BUILD !!}" rel="stylesheet">
    @yield( 'link' )
    <title>ADBM:{{$title}}</title>
    {{-- standard alpine / tippy  --}}
    <script defer src="/js/vendor/alpine-mask.min.js?q={!! _BUILD !!}"></script>
    <script defer src="/js/vendor/alpine-focus.min.js?q={!! _BUILD !!}"></script>
    <script defer src="/js/vendor/alpine.min.js?q={!! _BUILD !!}"></script>
    <script src="/js/vendor/popper.min.js?q={!! _BUILD !!}"></script>
    <script src="/js/vendor/tippy.min.js?q={!! _BUILD !!}"></script>
    <script src="/js/wd/master.min.js?q={!! _BUILD !!}"></script>
    @yield( 'posthead')
    @yield( 'editor' )
</head>
<body class="{!! $bodyClass ?? '' !!}">
@yield( 'body' )
<div id="privacy-banner" class="fixed bottom-0 left-0 w-full bg-slate-900 text-slate-100 p-4 z-[9999] hidden">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-sm">
        <p>
            We use essential cookies for login and settings. We do not use third-party tracking or marketing.
            <a href="/privacy" class="underline hover:text-blue-400">Learn More</a>
        </p>
        <div class="flex gap-2">
            <button onclick="dismissBanner(false)" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded">
                Dismiss
            </button>
            <button onclick="dismissBanner(true)" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 font-bold rounded">
                Don't show again
            </button>
        </div>
    </div>
</div>
<script>
    (function() {
        try {
            // Check if they've already said "Don't show again"
            if (!localStorage.getItem('hide_privacy_notice')) {
                document.getElementById('privacy-banner').style.display = 'block';
            }
        } catch (e) {
            // If localStorage is blocked, just show the banner every time
            document.getElementById('privacy-banner').style.display = 'block';
        }
    })();

    function dismissBanner(isPermanent) {
        document.getElementById('privacy-banner').style.display = 'none';
        if (isPermanent) {
            try {
                localStorage.setItem('hide_privacy_notice', 'true');
            } catch (e) {
                // Do nothing if we can't save; they just see banner again and again and again,  next time
            }
        }
    }
</script>


</body>
</html>
