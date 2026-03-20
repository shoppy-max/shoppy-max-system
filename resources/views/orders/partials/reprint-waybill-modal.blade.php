<div
    x-cloak
    x-show="reprintWaybillModalOpen"
    x-transition.opacity
    @keydown.escape.window="closeReprintWaybillModal()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
>
    <div
        @click.outside="closeReprintWaybillModal()"
        class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800"
    >
        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reprint Waybill</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <span x-show="reprintWaybillOrderNumber">
                        Order <span class="font-medium text-gray-900 dark:text-gray-100" x-text="reprintWaybillOrderNumber"></span>.
                    </span>
                    Use the saved waybill ID again. No new waybill will be allocated, and the PDF download starts automatically.
                </p>
            </div>
            <button
                type="button"
                @click="closeReprintWaybillModal()"
                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
            <a
                x-bind:href="reprintWaybillUrl('a4')"
                target="waybillDownloadFrame"
                @click="closeReprintWaybillModal()"
                class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-left transition hover:border-blue-400 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-900/20 dark:hover:border-blue-500 dark:hover:bg-blue-900/20"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">A4 Size</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Print in a 2 x 2 A4 grid. Best for office printers and sheet printing.</p>
                    </div>
                    <div class="flex h-24 w-16 shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white shadow-sm dark:border-gray-600 dark:bg-gray-800">
                        <div class="grid h-[4.6rem] w-[3rem] grid-cols-2 gap-0.5 rounded-md border border-dashed border-gray-300 bg-gray-50 p-1 dark:border-gray-500 dark:bg-gray-700/50">
                            <div class="rounded-sm border border-gray-300 bg-white dark:border-gray-500 dark:bg-gray-800"></div>
                            <div class="rounded-sm border border-gray-300 bg-white dark:border-gray-500 dark:bg-gray-800"></div>
                            <div class="rounded-sm border border-gray-300 bg-white dark:border-gray-500 dark:bg-gray-800"></div>
                            <div class="rounded-sm border border-gray-300 bg-white dark:border-gray-500 dark:bg-gray-800"></div>
                        </div>
                    </div>
                </div>
            </a>

            <a
                x-bind:href="reprintWaybillUrl('four_by_six')"
                target="waybillDownloadFrame"
                @click="closeReprintWaybillModal()"
                class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-left transition hover:border-blue-400 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-900/20 dark:hover:border-blue-500 dark:hover:bg-blue-900/20"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">4 x 6 Size</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Print one industrial courier waybill per 4 x 6 label page.</p>
                    </div>
                    <div class="flex h-16 w-24 items-center justify-center rounded-lg border border-gray-300 bg-white text-[10px] font-semibold text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        4 x 6
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<iframe name="waybillDownloadFrame" class="hidden"></iframe>
