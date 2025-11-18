@extends( 'layouts.app' ,['editable'=>false])
@section( 'main-content')
    @include( 'comp.wdtable.modal' )
    <div id="comp" class="m-0 w-full h-full"></div>
@endsection
@section( 'script' )
    @include ( 'comp.wddatatableform')
    <script>
        window.addEventListener('load', function () {

            new wdDataTableForm({
                id: 'comp' ,
                table: {
                    title: 'List of Companies' ,
                    qbar : [
                        { name: 'qbar_comp' , type: 'bar' , list: [ 'All','Active','Inactive' ] , default : 0},

                    ],
                    ajax: {
                        url: '/api/v1/companies',
                        token: '{{$app->generateBearer()}}'
                    },
                    columns: [
                        {head: 'Code'     , field: 'code' , size: 4 ,sort:1 },
                        {head: 'Name'     , field: 'name' , size: 25 },
                        {head: 'Postcode' , field: 'postcode' , size: 10},
                        {head: 'Active'   , field: 'active', size: 1 , type: 'boolean' },
                        {head: 'Created'  , field: 'd_cr' , size: 8 , type: 'iso-date-str' },
                        {head: 'ID'       , field: 'cid' , size: 5 },
                    ],
                },
                form: {}
            },null)
        });


    </script>
@endsection
