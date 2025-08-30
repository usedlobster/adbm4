@extends('layout.login')
@section('content')

    <div class="max-w-md w-full space-y-4">

        @include( 'component.logo-head', ['title'=>'Pick Project'] )
        @include( 'component.error-block' , ['error'=> $error ])

        <form class="mt-8 space-y-6" action="#" method="POST">
            @csrf
            <div class="rounded-md shadow-sm space-y-4">

                @if ( count($list) > 0 )
                    <div>
                        <label for="pick-project" class="block text-sm font-medium text-gray-700">Pick Project
                        <select id="pick-project"
                                name="pick-project"
                                aria-required="true"
                                class="mt-1 block w-full rounded-md">
                            @foreach( $list as $p )
                                <option value="{{$p['pid'] ?? 0}}">{{$p['title'] ?? ''}}</option>
                            @endforeach
                        </select>
                        </label>
                    </div>
                @else
                    <div class="flex items-center justify-center">
                        <span class="text-lg text-red-500">You have no active projects to choose</span>
                    </div>
                @endif


            </div>

            <div class="flex items-center justify-between">

            @if ( count($list) > 0 )
            </div>
                @component('component.button', ['name'=>'_submit'])
                    @include('icon.project')
                    Pick Project
                @endcomponent
            <div>
            @endif
                <div>
                    <a href="/auth/signout" class="text-sm underlined text-indigo-600 hover:text-indigo-400">Cancel</a>
                </div>

            </div>



        </form>

    </div>

@endsection