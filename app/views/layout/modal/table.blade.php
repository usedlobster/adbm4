<template id="table_modal" x-ignore>

    <!-- Modal header -->
    <div class="flex items-center justify-between border-b pb-3">
        <h1 class="text-2xl font-bold text-heading" x-text="data.title + ' (View Options)'"></h1>
        <button type="button" class="w-8 h-8" @click="action( 'close')">
            <svg class="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M19,3H16.3H7.7H5A2,2 0 0,0 3,5V7.7V16.4V19A2,2 0 0,0 5,21H7.7H16.4H19A2,2 0 0,0 21,19V16.3V7.7V5A2,2 0 0,0 19,3M15.6,17L12,13.4L8.4,17L7,15.6L10.6,12L7,8.4L8.4,7L12,10.6L15.6,7L17,8.4L13.4,12L17,15.6L15.6,17Z"/>
            </svg>
        </button>
    </div>
    <!-- Modal body -->
    <div class="overflow-auto h-[80vh] text-xl">
        <div class="flex flex-row gap-2">
            <div class="grow border-r-2 p-2">
                <table class="w-full">
                    <thead class="bg-green-50">
                    <tr>
                        <!-- name -->
                        <th class="text-cente ">
                            <span>Name</span>
                        </th>
                        <!-- move up -->
                        <th class="text-center">

                            <svg class="w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M15,20H9V12H4.16L12,4.16L19.84,12H15V20Z"/>
                            </svg>

                        </th>
                        <!-- move down -->
                        <th>
                            <svg class="w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M9,4H15V12H19.84L12,19.84L4.16,12H9V4Z"/>
                            </svg>

                        </th>
                        <!-- sortable -->
                        <th>
                            <svg class="w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M18 21L14 17H17V7H14L18 3L22 7H19V17H22M2 19V17H12V19M2 13V11H9V13M2 7V5H6V7H2Z"/>
                            </svg>
                        </th>
                        <!-- width -->
                        <th>
                            <svg class="w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M8,14V18L2,12L8,6V10H16V6L22,12L16,18V14H8Z"/>
                            </svg>

                        </th>

                        <!-- visible -->
                        <th>

                            <svg class="w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z"/>
                            </svg>

                        </th>

                        <!-- exportable -->
                        <th>
                            <svg class="w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23,12L19,8V11H10V13H19V16M1,18V6C1,4.89 1.9,4 3,4H15A2,2 0 0,1 17,6V9H15V6H3V18H15V15H17V18A2,2 0 0,1 15,20H3A2,2 0 0,1 1,18Z" /></svg>


                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(v,k) in data.defs">
                        <tr class="border-b :key=v.pos"
                            :class="{
                            'moving-up': v._animate === 'out-up',
                            'moving-down': v._animate === 'out-down',
                            'entering-up': v._animate === 'in-up',
                            'entering-down': v._animate === 'in-down'
                            }"
                            :key=('' + k)
                        >
                            <!-- name -->
                            <td class="min-w-16 max-w-48">
                                <input type="text"
                                       class="border border-gray-300 rounded-md px-2 py-1 w-full "
                                       @change="action('name',k,$event.target.value)"
                                       readonly
                                       x-model="v.name">
                            </td>
                            <!-- move up -->
                            <td>
                                <button type="button" x-show="v?.up>=0" class="border p-1 bg-blue-100 " @click="action('up',k , v )">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M13,20H11V8L5.5,13.5L4.08,12.08L12,4.16L19.92,12.08L18.5,13.5L13,8V20Z"/>
                                    </svg>
                                </button>
                            </td>
                            <!-- move down -->
                            <td>
                                <button type="button" x-show="v?.down >=0 " class="border p-1 bg-blue-100 " @click="action('down',k , v )">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M11,4H13V16L18.5,10.5L19.92,11.92L12,19.84L4.08,11.92L5.5,10.5L11,16V4Z"></path>
                                    </svg>
                                </button>
                            </td>
                            <!-- sort order -->
                            <td>
                                <button class="w-6 h-6 border" x-show="(v?.sortable ?? true)" x-text="( v?.sort ? ( v?.sort < 0 ? '▼' : ( v.sort > 0 ? '▲' :  '=' )) : '')"
                                        @click="action('sort',k,v)">
                                </button>
                            </td>
                            <!-- width -->
                            <td class="max-w-16 mr-1">
                                <input type="number"
                                       class="p-1"
                                       min="0.5"
                                       max="50.0"
                                       step="0.5"
                                       @change="action('size',k,$event.target.value)"
                                       x-model="v.width">
                            </td>
                            <!-- visible -->
                            <td>
                                <input type="checkbox"
                                       class="p-1"
                                       @click="action('vis',k,v.vis)"
                                       x-model="v.vis"
                                >
                            </td>

                            <!-- exportable -->
                            <td>
                                <input type="checkbox"
                                       class="p-1"
                                       @click="action('exp',k,v.vis)"
                                       x-model="v.exp"
                                >
                            </td>


                        </tr>
                    </template>
                    </tbody>
                </table>


            </div>

        </div>
    </div>

    <!-- Modal footer -->
    <div class="flex items-center border-t border-default space-x-4 pt-3 justify-end  gap-2">
        <div class="flex gap-1 flex-wrap">
            <button class="m-btn0" @click="action('reset')">Reset</button>
            <button class="m-btn0" @click="action('close')">Cancel</button>
            <button class="m-btn1" @click="action('save')">Save</button>
        </div>
    </div>


</template>

<script>


</script>