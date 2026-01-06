@extends('layout.auth.base')
@section('content')

    @include( 'layout.logo.head', ['title'=>'Pick Project'] )
{{--    @include( 'layout.snip.error-block' , ['errormsg'=> $errormsg ?? ''])--}}


    <form class="mt-8 space-y-6" action="#" method="POST">
        @csrf
        <div class="rounded-md shadow-sm space-y-4">

            @if ( is_array($list) && count($list) > 0 )
                <div>
                    <label for="pick-project" class="block text-sm font-medium text-gray-700">Pick Project
                        <select id="pick-project"
                                name="pick-project"
                                aria-required="true"
                                class="mt-1 block w-full rounded-md">
                            @foreach( $list as $p )
                                <option value="{{$p->pid ?? 0}}">{{$p->title ?? ''}}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            @else
                <div class="flex items-center justify-center text-center">
                    <span class="text-lg text-red-500">
                        Sorry, You have no active projects
                    </span>
                </div>
            @endif


        </div>

        <div>
            @if ( is_array($list) && count($list) > 0  )
                @component('layout.snip.bar-button',['name'=>'_login','value'=>1])
                    Select Project
                @endcomponent
            @endif
        </div>

        <div>
            <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500 underline">Cancel Sign In</a>
        </div>

    </form>

@endsection