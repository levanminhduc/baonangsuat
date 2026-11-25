<div id="bulk-createTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>Tạo Báo Cáo Hàng Loạt</h2>
        </div>
        <div class="p-6">
            <form id="bulkCreateForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group space-y-1">
                        <label for="bulkDate" class="block text-sm font-medium text-gray-700">Ngày</label>
                        <input type="date" id="bulkDate" name="ngay" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div class="form-group space-y-1">
                        <label for="bulkCa" class="block text-sm font-medium text-gray-700">Ca</label>
                        <select id="bulkCa" name="ca_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                            <option value="">-- Chọn Ca --</option>
                        </select>
                    </div>
                    <div class="form-group space-y-1">
                        <label for="bulkMaHang" class="block text-sm font-medium text-gray-700">Mã hàng</label>
                        <select id="bulkMaHang" name="ma_hang_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                            <option value="">-- Chọn Mã hàng --</option>
                        </select>
                    </div>
                    <div class="form-group space-y-1">
                        <label for="bulkCtns" class="block text-sm font-medium text-gray-700">Chỉ tiêu năng suất (CTNS)</label>
                        <input type="number" id="bulkCtns" name="ctns" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div class="form-group space-y-1">
                        <label for="bulkSoLaoDong" class="block text-sm font-medium text-gray-700">Số lao động (LĐ)</label>
                        <input type="number" id="bulkSoLaoDong" name="so_lao_dong" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>
                </div>

                <div class="form-group space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Chọn LINE áp dụng</label>
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 max-h-60 overflow-y-auto">
                        <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-200">
                            <input type="checkbox" id="bulkSelectAllLines" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <label for="bulkSelectAllLines" class="text-sm font-semibold text-gray-700 cursor-pointer">Chọn tất cả</label>
                        </div>
                        <div id="bulkLinesContainer" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <!-- Checkboxes render via JS -->
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="bulkSkipExisting" name="skip_existing" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="bulkSkipExisting" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Bỏ qua nếu đã tồn tại (không báo lỗi)</label>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" id="btnBulkCreate" class="btn btn-primary px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium flex items-center gap-2">
                        <span>Tạo báo cáo hàng loạt</span>
                    </button>
                </div>
            </form>

            <!-- Results Area -->
            <div id="bulkResultArea" class="hidden mt-6 p-4 rounded-lg border">
                <h3 class="text-lg font-semibold mb-2" id="bulkResultTitle">Kết quả</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-green-50 p-3 rounded border border-green-200">
                        <span class="text-green-700 font-medium">Đã tạo thành công:</span>
                        <span id="bulkCountCreated" class="font-bold text-green-800 text-lg ml-1">0</span>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                        <span class="text-yellow-700 font-medium">Đã bỏ qua (tồn tại):</span>
                        <span id="bulkCountSkipped" class="font-bold text-yellow-800 text-lg ml-1">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
