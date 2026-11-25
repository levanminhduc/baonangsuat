<div id="moc-gioTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>Quản lý Mốc giờ theo LINE</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group filter-group">
                <label for="mocGioCaSelect">Chọn Ca:</label>
                <select id="mocGioCaSelect" class="custom-select form-control">
                    <option value="">-- Chọn ca --</option>
                </select>
            </div>
            <div class="form-group filter-group">
                <label for="mocGioLineSelect">Chọn LINE:</label>
                <select id="mocGioLineSelect" class="custom-select form-control">
                    <option value="">-- Tất cả (xem default) --</option>
                    <option value="default">Mốc giờ mặc định</option>
                </select>
            </div>
        </div>
        <div id="mocGioTableContainer" style="display: none;">
            <div class="panel-header">
                <h3 id="mocGioTitle">Mốc giờ: </h3>
                <div class="flex gap-2">
                    <button id="copyDefaultBtn" class="btn bg-green-600 hover:bg-green-700 text-white" style="display: none;">Copy từ Default</button>
                    <button id="addMocGioBtn" class="btn btn-primary">+ Thêm Mốc giờ</button>
                </div>
            </div>
            <div id="mocGioFallbackNotice" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 mb-4" style="display: none;">
                <p class="font-medium">LINE này đang sử dụng mốc giờ mặc định</p>
                <p class="text-sm">Nhấn "Copy từ Default" để tạo mốc giờ riêng cho LINE này.</p>
            </div>
            <table class="admin-table" id="mocGioTable">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Giờ</th>
                        <th>Phút lũy kế</th>
                        <th>Ca</th>
                        <th>LINE</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
