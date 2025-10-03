<?php

    namespace app\traits;

    trait AppMenuTrait {


        public function getMenu() {
            return [
                    'group' => [
                            'title' => 'Menu' ,
                            'items' => [
                                    [
                                            'title' => 'Home' ,
                                            'items' => [
                                                    ['title' => 'Task 1' , 'link' => '/'] ,
                                            ]
                                    ] ,
                                    [
                                            'title' => 'Bookings' ,
                                            'items' => [
                                                    ['title' => 'Book 1'] ,
                                                    ['title' => 'Book 2'] ,
                                                    ['title' => 'Book 3'] ,
                                                    ['title' => 'Book 4'] ,

                                            ]
                                    ] ,
                                    [
                                            'title' => 'Setup' ,
                                            'items' => [
                                                    ['title' => 'Companies','href'=> '/setup/companies'] ,
                                            ]
                                    ] ,


                            ]
                    ]
            ];
        }

    }