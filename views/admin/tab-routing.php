<div id="routingTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>Quản lý Routing</h2>
        </div>
        <div class="form-group filter-group">
            <label for="routingMaHangSelect">Chọn Mã hàng:</label>
            <select id="routingMaHangSelect" class="custom-select form-control filter-select-routing">
                <option value="">-- Chọn mã hàng --</option>
            </select>
        </div>
        <div id="routingTableContainer" style="display: none;">
            <div class="panel-header">
                <h3 id="routingTitle">Routing cho: </h3>
                <button id="addRoutingBtn" class="btn btn-primary">+ Thêm Công đoạn</button>
            </div>
            <table class="admin-table" id="routingTable">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Công đoạn</th>
                        <th>LINE</th>
                        <th>Tính lũy kế</th>
                        <th>Bắt buộc</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
