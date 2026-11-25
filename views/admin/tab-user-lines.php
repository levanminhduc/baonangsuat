<div id="user-linesTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>Danh sách User-LINE Mapping</h2>
            <button id="addUserLineBtn" class="btn btn-primary">+ Thêm Mapping</button>
        </div>
        <div class="form-group filter-group">
            <label for="userLineFilterLine">Lọc theo LINE:</label>
            <select id="userLineFilterLine" class="custom-select form-control filter-select-line">
                <option value="">-- Tất cả LINE --</option>
            </select>
        </div>
        <table class="admin-table" id="userLinesTable">
            <thead>
                <tr>
                    <th>Mã NV</th>
                    <th>Họ tên</th>
                    <th>Mã LINE</th>
                    <th>Tên LINE</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
