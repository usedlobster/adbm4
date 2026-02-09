@extends( 'master')
@section( 'body' )
    <body class="h-full">
        <div class="min-h-full flex flex-col px-2 sm:px-0">
            <main class="flex-1 flex items-center justify-center">
                <div class="max-w-md w-full space-y-4">
                    @include( 'layout.logo.head' )
                    @hasSection( 'form-error')
                        @yield( 'form-error')
                    @else
                        @if ( !empty($errormsg))
                            @component('snip.info-block',['type'=>'error'])
                                    <?php echo is_numeric($errormsg) ? \sys\Error::msg($errormsg) : $errormsg; ?>
                            @endcomponent
                        @endif
                    @endif
                    <form class="wd-adbm-form" action="" method="POST">
                        @csrf
                        <div class="flex flex-col space-y-2">
                            @yield( 'form-head' )
                            @yield( 'form' )
                            @yield( 'form-submit')
                        </div>
                    </form>
                    @hasSection( 'form-foot')
                        @yield( 'form-foot')
                    @else
                        <hr/>
                    @endif

                </div>
            </main>
        </div>
    </body>

@endsection