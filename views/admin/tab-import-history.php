<div id="import-historyTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>Lịch sử Import Excel</h2>
        </div>
        
        <div class="p-6">
            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="form-group">
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Từ ngày</label>
                    <input type="date" id="historyDateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="form-group">
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Đến ngày</label>
                    <input type="date" id="historyDateTo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="form-group">
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Người import</label>
                    <input type="text" id="historyUserSearch" placeholder="Tên hoặc mã NV..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="form-group">
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Trạng thái</label>
                    <select id="historyStatusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                        <option value="">-- Tất cả --</option>
                        <option value="success">Thành công</option>
                        <option value="partial">Một phần</option>
                        <option value="failed">Thất bại</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-left border-collapse" id="importHistoryTable">
                    <thead class="bg-gray-50 text-gray-700 font-semibold text-sm uppercase">
                        <tr>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Người thực hiện</th>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3 text-center">MH Mới</th>
                            <th class="px-4 py-3 text-center">MH Cập nhật</th>
                            <th class="px-4 py-3 text-center">CĐ Mới</th>
                            <th class="px-4 py-3 text-center">Routing (+/-)</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Content loaded via JS -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="importHistoryPagination" class="mt-4"></div>
        </div>
    </div>
</div>
