<div id="po-subartTab" class="admin-tab-content">
    <div class="admin-panel">
        <div class="panel-header">
            <h2>SL Subart theo PO</h2>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label for="poSubartMaHangSelect">Mã hàng:</label>
                <select id="poSubartMaHangSelect" class="custom-select form-control">
                    <option value="">-- Chọn mã hàng --</option>
                </select>
            </div>
            <div class="form-group">
                <label for="poSubartPoSelect">PO:</label>
                <select id="poSubartPoSelect" class="custom-select form-control" disabled>
                    <option value="">-- Chọn PO --</option>
                </select>
            </div>
        </div>
        <div id="poSubartContent"></div>
    </div>
</div>
