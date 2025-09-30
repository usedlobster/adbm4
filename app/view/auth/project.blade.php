@extends('layouts.auth')
@section('content')

    @include( 'snip.logo-head', ['title'=>'Pick Project'] )
    @include( 'snip.error-block' , ['error'=> $error ?? false ])


    <form class="mt-8 space-y-6" action="#" method="POST">
        @csrf
        <div class="rounded-md shadow-sm space-y-4">

            @if ( isset($list) && is_array($list) && count($list) > 0 )
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
                <div class="flex items-center justify-center text-center">
                    <span class="text-lg text-red-500">You have no active projects available<br/>Please contact site</span>
                </div>
            @endif


        </div>

        <div>
            @if ( count($list) > 0 )
                @component('comp.button',['name'=>'_login'])
                    Select Project
                @endcomponent
            @endif
        </div>

        <div>
            <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
        </div>

    </form>

@endsection