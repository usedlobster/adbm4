@extends( 'layout.app')
@section( 'post-load')
    @include( 'wd.table')
@endsection
@section( 'main-content')
    <div id="comp" class="m-0 w-full h-full"></div>
@endsection
@section('script')
    <script>
        window.addEventListener('load', function () {
            new wdDataTable('comp', {
                'title': 'List of comapanies',
                'columns': {
                    'name':     {'title': 'Name'    , 'width': 5},
                    'address':  {'title': 'Address' , 'width': 5},
                    'phone':    {'title': 'Phone'   , 'width': 5},
                    'email':    {'title': 'Email'   , 'width': 5},
                    'website':  {'title': 'Website' , 'width': 5},
                } ,
                'api' : {
                    url: 'https://api.usedlobster.test/api/v0/data' ,
                    params: {
                        act: 'list',
                        dataset: 'comps'
                    }
                }

            }, null);
        });
    </script>
@endsection

