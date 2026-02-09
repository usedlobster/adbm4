@extends( 'layout.app' )
@section( 'app-script' )
    <script src="/js/wd/wdTable.min.js?q={{_BUILD}}"></script>
@endsection
@section( 'app' )
    @include( 'layout.modal.table' )
    <div id="users" class="m-0 w-full h-full wd-table"></div>
@endsection
@section( 'app-exec' )
        new wdTable( 'users' , {!!( $mvc?->_json ?? 'null' ) !!} ) ;
@endsection

