<?php

    namespace app;

    trait AppMasterMenuTrait
    {


        public static function getMenu()
        {
            return [
                    'group' => [
                            'title' => 'Menu' ,
                            'items' => [
                                    [
                                            'title' => 'Portal' ,
                                            'icon'  => '/img/icons/portal.svg' ,
                                            'open'  => 'portal' ,
                                            'items' => [
                                                    [ 'title' => 'Home' , 'link' => '/' ] ,
                                            ]
                                    ] ,
                                    [
                                            'title' => 'Bookings' ,
                                            'items' => [
                                                    [ 'title' => 'Book 1' ] ,
                                                    [ 'title' => 'Book 2' ] ,
                                                    [ 'title' => 'Book 3' ] ,
                                                    [ 'title' => 'Book 4' ] ,

                                            ]
                                    ] ,
                                    [
                                            'title' => 'Setup' ,

                                            'items' => [
                                                    [ 'title' => 'Companies' , 'href' => '/setup/companies' ] ,
                                            ]
                                    ] ,
                            ]
                    ]
            ];
        }


    }