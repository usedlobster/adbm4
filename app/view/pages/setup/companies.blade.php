@extends( 'layouts.app' ,['editable'=>false])
@section( 'main-content' )

    <div id="comp" class="m-0 w-full h-full"></div>

@endsection
@section( 'script' )
    @include ( 'comp.wddatatableform')

    <script>
        window.addEventListener('load', function () {

            new wdDataTableForm({
                id: 'comp' ,
                table: {
                    ajax: {
                        url: '/api/v1/companies',
                        token: '{{$app->generateBearer()}}'
                    },
                    columns: [
                        {head: 'CODE', field: 'code' , size: 4},
                        {head: 'Name', field: 'name' , size: 25 },
                        {head: 'Postcode', field: 'postcode' , size: 10},
                        {head: 'id', field: 'cid' , size: 5 },
                        {head: 'active', field: 'active', size: 1 },
                    ],








                },
                form: {}

            })
        });


    </script>
@endsection
