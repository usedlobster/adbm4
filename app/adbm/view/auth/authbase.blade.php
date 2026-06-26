@extends( 'layout.master' , [ 'bodyClass'=>'h-full'])
@section( 'body' )
    <div class="min-h-full flex flex-col px-2 sm:px-0">
        <main class="flex-1 flex items-center justify-center">
            <div class="max-w-md w-full space-y-4">
                @include( 'layout.logo.head' )
                @hasSection( 'form-error')
                    @yield( 'form-error')
                @else
                    @if ( !empty($errormsg))
                        @component('snip.info' , ['type'=>'error'])
                            {!! ( $errormsg ?? '' ) !!}
                        @endcomponent
                    @endif
                @endif
                @hasSection( 'form')
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
                @endif
            </div>
        </main>
    </div>
    @yield( 'bscript' )
@endsection