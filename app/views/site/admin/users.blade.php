@extends( 'layout.app' )
@section( 'app-script' )
    <script src="/js/wd/wdTable.min.js?q={{_BUILD}}"></script>
    <script src="/js/wd/wdForm.min.js?q={{_BUILD}}"></script>
    <script src="/js/wd/wdTableForm.min.js?q={{_BUILD}}"></script>
@endsection
@section( 'app' )
    @include( 'layout.modal.table' )
    <div id="users"></div>
@endsection
@section( 'app-exec' )
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new wdTableForm( 'users' , {!!( $mvc?->_json ?? 'null' ) !!}  , [{{ $id ?? 0 }}]) ;
        })
    </script>
@endsection

