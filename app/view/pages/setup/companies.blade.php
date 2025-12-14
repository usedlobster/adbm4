@extends( 'layouts.app' ,['editable'=>false])
@section( 'main-content')
    @include( 'comp.wdtable.modal' )
    <div id="comp" class="m-0 w-full h-full"></div>
@endsection
@section( 'script' )
    @include ( 'comp.wddatatableform')
    <script>
        window.addEventListener('load', function () {
            const cfg = {
                id: 'comp',
                token: '{{$app->generateBearer()}}',
                table: {
                    title: 'List of Companies',
                    qbar: [{
                        name: 'qbar_comp', type: 'bar', list: ['All', 'Active', 'Inactive'], default: 0
                    },

                    ],
                    ajax: {
                        url: '/api/v1/companies',

                    },
                    columns: [
                        {head: 'Code', field: 'code', size: 4, sort: 1},
                        {head: 'Name', field: 'name', size: 25},
                        {head: 'Postcode', field: 'postcode', size: 10},
                        {head: 'Active'  , field: 'active', size: 1, type: 'boolean'},
                        {head: 'Created' , field: 'd_cr', size: 8, type: 'iso-date-str'},
                        {head: 'ID', field: 'cid', size: 5, type: 'button'},
                    ],
                },
                form: {
                        buttons: {
                            defs: {
                                submit: {
                                    act: 'submit',
                                    class: 'sbtn'
                                },
                                c: {label: 'Create', like: 'submit' },
                            },
                            mode: {c: ['c']},
                        },

                        pages: [{

                            sections: [{
                                multi: false,

                                elements: [
                                    {
                                        fld: 'code'    ,
                                        type: 'text'   ,
                                        label: 'Code'  ,
                                        primary: true  ,
                                        required: true ,
                                        readonly: true ,
                                        opts: {
                                            maxlen: 4,
                                            list : [{
                                                name: 'ccodes',
                                                source: {
                                                    url: '/api/v1/companies/codelist'
                                                },
                                            }
                                            ]
                                        },
                                        transform: [{code: true}],
                                        check: [
                                            {required: true, when: ['change']},
                                            {stop: true },
                                            {code24: true, when: ['change']},
                                            {unique: 'ccodes', when: ['change' ] },

                                        ],

                                    },
                                    {fld: 'name', type: 'text', label: 'Company Name', transform: [{title: true}]},
                                    {
                                        fld: 'postcode', type: 'text', label: 'Postcode (or ISO 3166-2)',
                                        check: [
                                            { ukpost: true },
                                        ]

                                    },
                                    {fld: 'active', type: 'bool', label: 'Active'},
                                    {fld: 'email', type: 'text', label: 'email', transform: [{email: true}]},
                                    {fld: 'd_cr', type: 'date', label: 'Created'}
                                ]

                            }, {

                                elements: [{fld: 'test', type: 'text', label: 'test'}]
                            }]
                        }]
                    }
            } ;
            new wdDataTableForm( cfg , null);

        });


    </script>
@endsection
