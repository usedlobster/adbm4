@extends( 'layout.master' , ['bodyClass'=>'h-full'])
@section( 'body' )

    <div class="min-h-full flex flex-col px-2 sm:px-0">
        <main class="flex-1 flex items-center justify-center">
            <div class="max-w-md w-full space-y-4">
                @include( 'layout.logo.head' , ['title'=>'Welcome'] )
                <div class="text-center">
                    <span><b>ADBM</b></span>
                    <span>Advance Delivery Booking Management</span>

                </div>
                <a href="/portal" class="bar-button button-hover inline-block text-center">
                    Enter<sup>*</sup>
                </a>
                <a href="/more" class="bar-button button-hover cyan inline-block text-center">
                    Find Out More
                </a>

                <div class="text-center mt-8 text-xs">* By continuing you agree to the sites <a class="underline" href="/terms">terms and conditions</a></div>

            </div>

        </main>
    </div>
@endsection