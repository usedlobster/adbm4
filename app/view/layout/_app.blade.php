@extends( 'layout.master' )

@section( 'head' )
    @if ( ($editable ?? false))
        @if (($_SESSION['_editor_mode'] ?? 0 ) !== 0 )

        @endif

    @endif
@endsection

@section( 'body' )

    @if ( $editable )
        <form id="_editorModeForm" method="POST" style="">
            <input type="hidden" name="_editorMode" id="_editorModeInput">
            <input type="hidden" name="_editor_token" value="{{ $_SESSION['_editor_csrf'] ?? '' }}">
        </form>
    @endif

    <script>
        function pageData() {
            return {
                sidebarOpen: false,
                sidebarExpanded: false,
                darkMode: false,
                editorMode: {!! ($editable ? $_SESSION['_editor_mode'] ?? 0 : 0 ) !!},
                editorAllow: {!! ($editable ? $info['editor_allowed'] ?? 0 : 0)  !!},
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
                    v &= this.editorAllow ;
                    if (v !== this.editorMode) {

                        if ( !(+v & 1) && ( +this.editorMode & +this.editorAllow & 1)) {
                            // gone from not editing to [ editing mode 1 ]
                            const form = document.getElementById('_editorModeForm');
                            const editors = document.querySelectorAll('div[data-editor]');
                            if ( editors.length > 0 ) {

                                editors.forEach(editor => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = '_editor_content[]';
                                    input.value = editor.getAttribute('data-editor') + ':"' + encodeURI( editor.textContent || '' ) + '"' ;
                                    form.appendChild(input);
                                });

                                document.getElementById('_editorModeInput').value = v;
                            }
                            form.submit();
                        }


                        document.getElementById('_editorModeInput').value = v;
                        document.getElementById('_editorModeForm').submit();
                    }
                },
                toggleEdit() {
                    switch( this.editorAllow & 3 ) {
                        case 0 :
                            this.setEdit(0 ) ;
                            break;
                        case 1 :
                            this.setEdit( this.editorMode ^ 1 ) ;
                            break ;
                        case 2 :
                            this.setEdit( this.editorMode ^2 ) ;
                            break ;
                        case 3 :
                            this.setEdit((this.editorMode + 1) % 3);
                            break ;
                    }

                },
                init: function () {
                    this.darkMode = this.setDark(localStorage.getItem('dark-mode') === 'dark');
                    this.sidebarExpanded = localStorage.getItem('sidebar-expanded') == 'true';
                    this.$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value));
                }

            }
        }

        window.addEventListener('load', function () {

        });
    </script>



    <body hidden x-data="pageData()"
          class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400"
          :class="{ 'sidebar-expanded': sidebarExpanded }">
    <div class="flex h-[100dvh] overflow-hidden">

        <!-- Sidebar -->
        @include('layout.sub.sidebar')
        <!-- Content area -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

            <!-- Site header -->
            @include( 'layout.sub.siteheader')
            <main class="grow">
                <div class="px-4 py-2 full max-w-384 mx-auto min-h-full">
                    @yield( 'main-content')
                </div>
            </main>
            @include( 'layout.sub.footer')
        </div>



    </div>
    </body>
    @if ( $editable )
        <script>


        </script>
    @endif
@endsection