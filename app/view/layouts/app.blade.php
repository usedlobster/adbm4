@extends( 'layouts.master' )

@section( 'head' )
    @if ( ($editable ?? false))
        <script src="/js/wd/editor.min.js?q={{_BUILD}}"></script>
    @endif
@endsection

@section( 'body' )
    @if (( $editable ?? true))
        <form id="_editorModeForm" method="POST" style="">
            <input type="hidden" name="_editorMode" id="_editorModeInput" value="0">
            <input type="hidden" name="_editor_token" value="{{ $_SESSION['_editor_csrf'] ?? '' }}">
        </form>
    @endif

    <script>
        function pageData() {
            return {
                sidebarOpen: false,
                sidebarExpanded: false,
                darkMode: false,
                editorAllow: {!! $allow ?? 0 !!},
                editorMode:  {!! $mode ?? 0 !!} ,
                changed: false,
                setDark(v) {
                    // persist dark mode
                    localStorage.setItem('dark-mode', v ? 'dark' : 'light');
                    this.darkMode = v;
                    if (v)
                        document.body.classList.add('dark');
                    else
                        document.body.classList.remove('dark');
                },
                setEdit(v) {
                    if (v !== this.editorMode) {
                        const form = document.getElementById('_editorModeForm');
                        if (form) {
                            if ((v & ~3) === 0) {
                                v &= +this.editorAllow;
                                let p = +this.editorMode & +this.editorAllow;
                                if ( this.changed ) {
                                    if (!(v & 1) && (p & 1))
                                        _wd_postEditorsToForm(form, 1);
                                    else if (!(v & 2) && (p & 2))
                                       _wd_postEditorsToForm(form, 2);
                                }
                            } else
                                v = 0;

                            document.getElementById('_editorModeInput').value = v;
                            form.submit();
                        }
                    }
                },
                toggleEdit() {

                    let n = this.editorMode ;
                    if ((this.editorAllow & 3)==3)
                        n=(n+1)%3 ;
                    else if (this.editorAllow & 2 )
                        n=(n^2)&2;
                    else if ( this.editorAllow&1 )
                        n=(n^1)&1;
                    else
                        n= 0;
                    this.setEdit( n & this.editorAllow&3) ;
                },
                cancelEditMode() {
                    this.setEdit(-1)
                },
                saveEditAndClose() {
                    this.setEdit(0)
                },

                init: function () {
                    this.darkMode = this.setDark(localStorage.getItem('dark-mode') === 'dark');
                    this.sidebarExpanded = localStorage.getItem('sidebar-expanded') == 'true';
                    this.$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value));
                }

            }
        }

        window.addEventListener('load', function () {
            //

        });
    </script>


    <body hidden x-data="pageData()"
          class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400"
          :class="{ 'sidebar-expanded': sidebarExpanded }">
    <div class="flex h-[100dvh] overflow-hidden">

        <!-- Sidebar -->
        @include('layouts.app.sidebar')
        <!-- Content area -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

            <!-- Site header -->
            @include( 'layouts.app.siteheader')
            <main class="grow">
                <div class="px-4 py-2 full max-w-384 mx-auto min-h-full">
                    @yield( 'main-content')
                </div>
            </main>
            @include( 'layouts.app.sitefooter')
        </div>
    </div>
    </body>

@endsection