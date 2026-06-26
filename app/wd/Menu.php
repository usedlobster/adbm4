<?php

namespace app\wd;

class Menu
{

    private const string SVG_D_PROJECT = 'M5,3V21H11V17.5H13V21H19V3H5M7,5H9V7H7V5M11,5H13V7H11V5M15,5H17V7H15V5M7,9H9V11H7V9M11,9H13V11H11V9M15,9H17V11H15V9M7,13H9V15H7V13M11,13H13V15H11V13M15,13H17V15H15V13M7,17H9V19H7V17M15,17H17V19H15V17Z';
    private const string SVG_D_SQUARE  = 'M3,3V21H21V3';
    private const string SVG_D_ADMIN   = 'M10 4A4 4 0 0 0 6 8A4 4 0 0 0 10 12A4 4 0 0 0 14 8A4 4 0 0 0 10 4M17 12C16.87 12 16.76 12.09 16.74 12.21L16.55 13.53C16.25 13.66 15.96 13.82 15.7 14L14.46 13.5C14.35 13.5 14.22 13.5 14.15 13.63L13.15 15.36C13.09 15.47 13.11 15.6 13.21 15.68L14.27 16.5C14.25 16.67 14.24 16.83 14.24 17C14.24 17.17 14.25 17.33 14.27 17.5L13.21 18.32C13.12 18.4 13.09 18.53 13.15 18.64L14.15 20.37C14.21 20.5 14.34 20.5 14.46 20.5L15.7 20C15.96 20.18 16.24 20.35 16.55 20.47L16.74 21.79C16.76 21.91 16.86 22 17 22H19C19.11 22 19.22 21.91 19.24 21.79L19.43 20.47C19.73 20.34 20 20.18 20.27 20L21.5 20.5C21.63 20.5 21.76 20.5 21.83 20.37L22.83 18.64C22.89 18.53 22.86 18.4 22.77 18.32L21.7 17.5C21.72 17.33 21.74 17.17 21.74 17C21.74 16.83 21.73 16.67 21.7 16.5L22.76 15.68C22.85 15.6 22.88 15.47 22.82 15.36L21.82 13.63C21.76 13.5 21.63 13.5 21.5 13.5L20.27 14C20 13.82 19.73 13.65 19.42 13.53L19.23 12.21C19.22 12.09 19.11 12 19 12H17M10 14C5.58 14 2 15.79 2 18V20H11.68A7 7 0 0 1 11 17A7 7 0 0 1 11.64 14.09C11.11 14.03 10.56 14 10 14M18 15.5C18.83 15.5 19.5 16.17 19.5 17C19.5 17.83 18.83 18.5 18 18.5C17.16 18.5 16.5 17.83 16.5 17C16.5 16.17 17.17 15.5 18 15.5Z';
    private const string SVG_D_VAN     = 'M3,7C1.89,7 1,7.89 1,9V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V13C23,11.89 22.11,11 21,11L18,7H3M15,8.5H17.5L19.46,11H15V8.5M6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5M18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5Z';

    public static function getMenu()
    {
        return [
            [
                'title' => 'Project',
                'icon'  => ['d' => self::SVG_D_PROJECT],
                'items' => [
                    [
                        'title' => 'Home',
                        'link'  => '/portal',
                        'icon'  => ['d' => 'M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z'],
                    ],
                ],
            ],
            [
                'title' => 'Admin ',
                'icon'  => ['d' => self::SVG_D_ADMIN],
            ],
            [
                'title' => 'Booking',
                'mod'   => 1,
                'icon'  => ['d' => self::SVG_D_VAN],
            ],

        ];
    }

    private static function showIcon($item, $level)
    {
        $icon = $item['icon'] ?? [];
        echo '<div class="fill-current">';
        $svgclass = ($level > 0) ? 'w-4 h-4' : 'w-6 h-6';
        if ($icon['d'] ?? false) {
            echo '<svg class="', $svgclass, '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">';
            echo '<path d="', $icon['d'], '"></path>';
            echo '</svg>';
        }

        echo '</div>';
    }

    private static function showText($item, $level)
    {
        if ($level == 0)
            echo '<span class="text-md ml-2" x-show="sidebarExpanded" x-cloak>', ($item['title'] ?? ''), '</span>';
        else
            echo '<span class="text-sm ml-2" x-show="sidebarExpanded" x-cloak>', ($item['title'] ?? ''), '</span>';
    }

    private static function showDrop($item, $level)
    {
        echo '<div class="flex shrink-0 ml-3 mr-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200 transition">';
        echo '<svg class="w-3 h-3 shrink-0 ml-1 transition-all fill-current text-gray-400 dark:text-gray-500" :class="open ? \'rotate-180\' : \'rotate-0\'" viewBox="0 0 12 12"S>';
        echo '<path d="M6 11.5L.5 6l1.5-1.5 4 4 4-4L11.25 6z"/>';
        echo '</svg>';
        echo '</div>';
    }

    private static function showMenuItem($item, $level)
    {
        $href = $item['href'] ?? '';
        $class = 'hover:scale-110 hover:font-bold  font-medium block truncate transition';
        if ($level == 0)
            echo '<li x-data="{ open: true }" class="mx-1">';
        else
            echo '<li x-data="{ open: true }" class="mx-8">';
        if (!empty($href))
            echo "<a href=\"$href\" class=\"{$class}\"  @click.prevent=\"open = !open; sidebarExpanded = true\">";
        else
            echo "<span  class=\"{$class}\"  @click.prevent=\"open = !open; sidebarExpanded = true\">";

        if ($level === 0) {
            echo '<div class="sidebar-menu-0">';
            echo '<div class="flex">';
            echo '<div>';
            self::showIcon($item, $level);
            echo '</div>';
            echo '<div>';
            self::showText($item, $level);
            echo '</div>';
            echo '</div>';
            echo '<div class="flex items-center justify-end">';
            echo '<div>';
            self::showDrop($item, $level);#
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        elseif ($level === 1) {
            echo '<div class="sidebar-menu-1">';
            echo '<div class="flex">';
            self::showIcon($item, $level);
            echo '</div><div>';
            self::showText($item, $level);
            echo '</div>';
            echo '</div>';
        }

        echo !empty($href) ? '</a>' : '</span>';

        if (count($item['items'] ?? []) > 0)
            self::showSubMenuList($item['items'], $level + 1);

        echo '</li>';
    }

    private static function showSubMenuList($menu, $level)
    {
        echo '<ul class="my-auto  flex items-center justify-start" x-show="open && sidebarExpanded" x-cloak>';
        foreach ($menu as $item) {
            self::showMenuItem($item, $level);
        }
        echo '</ul>';
    }

    private static function needItem($item) : bool
    {
        // return ($item['title'] ?? false ) != 'Project' ;

        return true ;
    }

    private static function showMenuList($menu, $level)
    {
        if (count($menu) > 0) {
            echo '<ul class="mx-1">';
            foreach ($menu as $item) {
                if ( self::needItem( $item ))
                    self::showMenuItem($item, $level);
            }
            echo '</ul>';
        }
    }

    public static function showMenu()
    {
        self::showMenuList( self::getMenu() , 0);
    }

    //    private static function showIcon( $icon )
    //    {
    //        echo '<div class="w-8 h-6 m-auto">' ;
    //        if ( isset($icon['d']))
    //        {
    //            $d = $icon['d'] ?? self::SVG_D_SQUARE ;
    //            $class = $icon['size'] ?? 'w-5 h-5';
    //            $class .= ' fill-current text-gray-500 dark:text-gray-400';
    //            echo '<svg class="' , ($class ?? '' ) , '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' ;
    //            echo '<path d="' , $d , '"></path>' ;
    //            echo '</svg>';
    //        }
    //
    //        echo '</div>' ;
    //    }
    //
    //    private static function showDropDown()
    //    {
    //        echo <<<HTML
    //<div class="flex shrink-0 ml-2 mr-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200 transition">
    //<svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500"
    //:class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
    //<path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z"/>
    //</svg>
    //</div>
    //HTML;
    //    }
    //
    //private static function menuLink( $item  )
    //    {
    //        $href = $item['href'] ?? '#0' ;
    //        echo '<a class="hover:scale-110 hover:font-bold  font-medium block truncate transition" href="' , $href , '" @click.prevent="open = !open; sidebarExpanded = true">';
    //        echo '<div class="flex items-center justify-between hover:text-green-800">' ;
    //        echo '<div class="flex items-center" title="' , $item['title'] ?? '' , '">' ;
    //            self::showIcon( $item['icon'] ?? [] ) ;
    //            echo '<span class="text-md ml-2" x-show="sidebarExpanded" x-cloak>' , ( $item['title'] ?? '' ) , '</span>' ;
    //            echo '<span class="sr-only">' , ( $item['title'] ?? '' ) , '</span>' ;
    //            if ( count( $item['items'] ?? [] ) > 0 ) {
    //                echo '<div>' ;
    //                // self::showDropDown() ;
    //                echo '</div>' ;
    //            }
    //        echo '</div>' ;
    //
    //
    //        echo '</a>';
    //    }
    //
    //    public static function showMenu()
    //    {
    //        $info = $_SESSION['_info'] ?? null;
    //        if (!$info)
    //            return;
    //        $menu = self::getMenu();
    //
    //    }

    /*
     *             @foreach( $menu ?? [] as $group )
                    <div>
                     <!-- Group Title -->
                        <h3 class="sidebar-menu-head pl-3">
                            <span class="2xl:block ellipsis truncate">{{ $group['title'] ?? ''}}</span>
                        </h3>
                        <!-- Group Menu Items -->
                        @if ( count( $group['items']) > 0 )
                            <ul class="mt-2">
                                @foreach( $group['items'] as $item )
                                    <li class="pl-4 pr-3 py-2  mb-0.5 last:mb-0" x-data="{ open: false }">
                                        <!-- Menu Item -->
                                        <a class="hover:scale-105 hover:font-bold font-medium block truncate transition" href="{{ $group['headref'] ?? '#0' }}"
                                           @click.prevent="open = !open; sidebarExpanded = true">
                                            <!-- icon + text + dropdown -->
                                            <div class="flex items-center justify-between">
                                                <!-- icon -->
                                                <div class="flex items-center" title="{{$item['title'] ?? '' }}">
                                                    <svg class="shrink-0 text-blue-500 dark:text-green-500 fill-current w-4 h-4"
                                                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                        @if ( !( $item['icon'] ?? false ))
                                                            <path d="M3,3V21H21V3"></path> <!-- large square -->
                                                        @else
                                                            <path d="{!! $item['icon'] ?? ''  !!}"></path>
                                                        @endif
                                                    </svg>
                                                    <span class="text-sm ml-2" x-show="sidebarExpanded" x-cloak>{{$item['title'] ?? '' }}</span>
                                                    <span class="sr-only">{{$item['title'] ?? '' }}</span>
                                                </div>
                                                <!-- dropdown - if needed  -->
                                                @if ( count(( $item['items'] ?? [] )) > 0 )
                                                    <div class="flex shrink-0 ml-2 mr-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200 transition">
                                                        <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500"
                                                             :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </a>

                                        <div class="hidden sidebar-expanded:block">
                                            @if ( count( $item['items'] ?? [] ) > 0 )
                                                <ul class="pl-8 mt-1" :class="open ? 'block!' : 'hidden'">
                                                    @foreach( $item['items'] ?? [] as $sub )
                                                        <li class="mb-1 pr-1 last:mb-0">
                                                            <a class="hover:scale-105 hover:font-bold text-xs uncate transition block transition truncate"
                                                               href="{!! $sub['href'] ?? '#'  !!}">
                                                                <div class="flex items-center">
                                                                    <svg class="shrink-0 fill-current w-4 h-4"
                                                                         xmlns="http://www.w3.org/2000/svg"
                                                                         viewBox="0 0 24 24">
                                                                        @if ( !( $sub['icon'] ?? false ))
                                                                            <path d="M10,14V10H14V14H10Z"></path>
                                                                            <!-- small square -->
                                                                        @else
                                                                            <path d="{!! $sub['icon'] ?? ''  !!}"></path>
                                                                        @endif
                                                                    </svg>
                                                                    <span class="lg:opacity-0 ml-1 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">{{$sub['title'] ?? '' }}</span>
                                                                </div>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
     */

}