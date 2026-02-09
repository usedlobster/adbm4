<template id="table_modal" x-ignore>

    <!-- Modal header -->
    <div class="flex items-center justify-between border-b pb-3">
        <h1 class="text-2xl font-bold text-heading" x-text="data.title + ' (Options)'"></h1>
        <button type="button" class="zbtn" @click="action( 'close')">
            <svg class="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M19,3H16.3H7.7H5A2,2 0 0,0 3,5V7.7V16.4V19A2,2 0 0,0 5,21H7.7H16.4H19A2,2 0 0,0 21,19V16.3V7.7V5A2,2 0 0,0 19,3M15.6,17L12,13.4L8.4,17L7,15.6L10.6,12L7,8.4L8.4,7L12,10.6L15.6,7L17,8.4L13.4,12L17,15.6L15.6,17Z"/>
            </svg>

        </button>
    </div>

    <!-- Modal body -->
    <div class="overflow-auto h-[80vh]">
        <div class="flex flex-row justify-between ">
            <div class="flex-grow">
                <table class="text-sm text-left text-body mt-2">
                    <caption>
                        <input type="text" class="w-full" maxlength="50" :value="data.title">
                    </caption>
                    <thead>
                    <tr>
                        <th class="px-2 text-center">Name</th>
                        <th class="px-2 text-center">Up</th>
                        <th class="px-2 text-center">Down</th>
                        <th class="px-2 text-center">Size</th>
                        <th class="px-2 text-center w-6 h-6" title="visible">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17
                    ,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z"/>
                            </svg>
                        </th>
                        <th class="px-2 text-center w-6 h-6" title="export">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M21.17 3.25Q21.5 3.25 21.76 3.5 22 3.74 22 4.08V19.92Q22 20.26 21.76 20.5 21.5 20.75 21.17 20.75H7.83Q7.5 20.75 7.24 20.5 7 20.26 7 19.92V17H2.83Q2.5 17 2.24 16.76 2 16.5 2 16.17V7.83Q2 7.5 2.24 7.24 2.5 7 2.83 7H7V4.08Q7 3.74 7.24 3.5 7.5 3.25 7.83 3.25M7 13.06L8.18 15.28H9.97L8 12.06L9.93 8.89H8.22L7.13 10.9L7.09 10.96L7.06 11.03Q6.8 10.5 6.5 9.96 6.25 9.43 5.97 8.89H4.16L6.05 12.08L4 15.28H5.78M13.88 19.5V17H8.25V19.5M13.88 15.75V12.63H12V15.75M13.88 11.38V8.25H12V11.38M13.88 7V4.5H8.25V7M20.75 19.5V17H15.13V19.5M20.75 15.75V12.63H15.13V15.75M20.75 11.38V8.25H15.13V11.38M20.75 7V4.5H15.13V7Z"/>
                            </svg>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="border">

                    <template x-for="(v,k) in data.defs">
                        <tr class="h-12">
                            <td class="px-2 border-l"><input type="text" class="w-fit qh-10" maxlength="24"
                                                             @change="action('name',k,$event.target.value)"
                                                             :value="v?.name"></td>
                            <td class="px-2 border-l">
                                <button type="button" class="mbtn" :disabled="(k<1)" @click="action('up',k)">⭡</button>
                            </td>
                            <td class="px-2 border-l">
                                <button type="button" class="mbtn" :disabled="(k>=data.defs.length-1)"
                                        @click="action('down',k)">⭣
                                </button>
                            </td>
                            <td class="px-2 border-l"><input type="number" min="0.25" max="40.0" step="0.25"
                                                             class="w-24 h-10"
                                                             @change="action('size',k,$event.target.value)"
                                                             :value="v.size"/></td>
                            <td class="px-2 border-l"><input type="checkbox" class="w-6 h-6" x-model="v.vis"
                                                             @click="action('vis',k,v.vis)"/></td>
                            <td class="px-2 border-l border-r"><input type="checkbox" class="w-6 h-6" x-model="v.exp"
                                                                      @click="action('exp',k,v.exp)"/></td>
                        </tr>
                    </template>

                    </tbody>
                </table>
            </div>
            {{--            <div class="flex-shrink-0 justify-end w-24 border-l border-default p-2">--}}
            {{--                <div class="">XX</div>--}}
            {{--            </div>--}}
        </div>
    </div>

    <!-- Modal footer -->
    <div class="flex items-center border-t border-default space-x-4 pt-3 justify-end  gap-2">
        <div class="flex gap-1 flex-wrap">
            <button class="bg-none button-hover sm:mr-12" @click="action('close') " title="Close without saving">
                Cancel
            </button>
            <button class="bg-gray-200 button-hover" @click="action('reset')" title="Reset to default view">
                Defaults
            </button>
            <button class="bg-gray-200 button-hover" @click="action('undo')" title="Revert back to previous view">
                Revert
            </button>
            <button class="bg-green-200 button-hover" @click="action('apply')" title="Save view for next time">
                Save
            </button>

        </div>

    </div>


</template>


