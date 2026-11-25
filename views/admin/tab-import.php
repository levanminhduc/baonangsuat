<div id="importTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>Import Công Đoạn & Mã Hàng từ Excel</h2>
        </div>
        <div class="p-6">
            <div id="importUploadZone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 md:p-10 text-center hover:border-primary hover:bg-gray-50 transition-all cursor-pointer bg-white group">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div class="text-gray-600">
                        <span class="font-medium block sm:inline">Kéo thả file Excel vào đây</span>
                        <span class="text-gray-400 hidden sm:inline"> hoặc </span>
                        <span class="text-primary font-medium hover:underline block sm:inline mt-1 sm:mt-0">Chọn file</span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-400">Hỗ trợ: .xlsx, .xls (tối đa 10MB)</p>
                </div>
                <input type="file" id="importFileInput" accept=".xlsx,.xls" class="hidden">
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Chưa có file? <a href="assets/templates/mau-import-cong-doan.xlsx" download class="text-primary font-medium hover:underline">Tải file mẫu</a>
                (mã hàng ở ô C1 dạng "MH: XXXX", công đoạn từ ô C4 trở xuống, mỗi sheet một mã hàng).
            </p>

            <div id="importSelectedFile" class="hidden mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-8 h-8 rounded bg-white flex items-center justify-center border border-gray-200 flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span id="importFileName" class="text-sm font-medium text-gray-700 truncate"></span>
                </div>
                <button type="button" id="importClearFile" class="p-1.5 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0" title="Xóa file">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="importPreviewSection" class="hidden mt-6">
                <div id="importStats" class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                </div>

                <div id="importErrors" class="hidden mb-6">
                    <h4 class="text-sm font-semibold text-danger mb-2">Sheets có lỗi:</h4>
                    <div id="importErrorsList" class="space-y-2"></div>
                </div>

                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Chi tiết các sheet:</h4>
                    <div id="importPreviewList" class="space-y-3 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" id="importCancelBtn" class="btn px-4 py-2 rounded-lg bg-gray-500 hover:bg-gray-700 text-white transition-colors font-medium">
                        Hủy
                    </button>
                    <button type="button" id="importConfirmBtn" class="btn btn-primary px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">
                        Xác nhận Import
                    </button>
                </div>
            </div>

            <div id="importResultSection" class="hidden mt-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800" id="importResultTitle">Kết quả Import</h3>
                <div id="importResultStats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                </div>
            </div>
        </div>
    </div>
</div>
