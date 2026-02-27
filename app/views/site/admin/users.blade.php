@extends( 'layout.app' )
@section( 'app-script' )

    <script src="/js/wd/wdTable.min.js?q={{_BUILD}}"></script>
    <script src="/js/wd/wdForm.min.js?q={{_BUILD}}"></script>
    <script src="/js/wd/wdTableForm.min.js?q={{_BUILD}}"></script>
@endsection
@section( 'app' )
    @include( 'layout.modal.table' )

{{--    <div role="group" tabindex="0" aria-label="Query1" class="inline-flex border-outline  border divide-x rounded-sm" >--}}
{{--        <button title="sm" value="A" aria-label="A" :aria-pressed="'All'" class="px-2 py-1 h-9 text-sm w-full border-outline rounded-l-sm">--}}
{{--            All--}}
{{--        </button>--}}
{{--        <button title="md" value="B" aria-label="B" :aria-pressed="'Active'" class="px-2 py-1 h-9 text-sm w-full border-outline">--}}
{{--            Active--}}
{{--        </button>--}}
{{--        <button title="lg" --}}
{{--                value="C" --}}
{{--                aria-label="C" --}}
{{--                :aria-pressed="'Inactive'" --}}
{{--                class="px-2 py-1 h-9 text-sm w-full border-outline rounded-r-sm">--}}
{{--            Inactive--}}
{{--        </button>--}}
{{--    </div>--}}

    <div id="users">Loading...</div>
@endsection
@section( 'app-exec' )
        new wdTableForm( 'users' , {!!( $mvc?->_json ?? 'null' ) !!}  , {{ $id ?? 0 }}) ;
@endsection

