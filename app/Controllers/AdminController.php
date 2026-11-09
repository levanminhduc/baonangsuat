<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\AdminService;
use App\Services\NangSuatService;

// `/api/admin/...`: chỉ admin (middleware nhóm `role:admin`); thao tác ghi khai `csrf` trong route.
class AdminController
{
    // --- lines ---

    public function lines(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getLineList()]);
    }

    public function line(Request $request)
    {
        $line = (new AdminService())->getLine($this->id($request));
        if (!$line) {
            return JsonResponse::error('LINE không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $line]);
    }

    public function createLine(Request $request)
    {
        $input = $request->jsonBody();
        $ma_line = $input['ma_line'] ?? '';
        $ten_line = $input['ten_line'] ?? '';

        return new JsonResponse((new AdminService())->createLine($ma_line, $ten_line));
    }

    public function updateLine(Request $request)
    {
        $input = $request->jsonBody();
        $ma_line = $input['ma_line'] ?? '';
        $ten_line = $input['ten_line'] ?? '';
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;

        return new JsonResponse((new AdminService())->updateLine($this->id($request), $ma_line, $ten_line, $is_active));
    }

    public function deleteLine(Request $request)
    {
        return new JsonResponse((new AdminService())->deleteLine($this->id($request)));
    }

    // --- users / user-lines ---

    public function users(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getUsersWithInfo()]);
    }

    public function userLines(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getUserLineListWithInfo()]);
    }

    public function addUserLine(Request $request)
    {
        $input = $request->jsonBody();
        $ma_nv = $input['ma_nv'] ?? '';
        $line_id = intval($input['line_id'] ?? 0);

        return new JsonResponse((new AdminService())->addUserLine($ma_nv, $line_id));
    }

    public function removeUserLine(Request $request)
    {
        $input = $request->jsonBody();
        $ma_nv = $input['ma_nv'] ?? '';
        $line_id = intval($input['line_id'] ?? 0);

        return new JsonResponse((new AdminService())->removeUserLine($ma_nv, $line_id));
    }

    // --- ma-hang ---

    public function maHangList(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getMaHangList()]);
    }

    public function maHang(Request $request)
    {
        $maHang = (new AdminService())->getMaHang($this->id($request));
        if (!$maHang) {
            return JsonResponse::error('Mã hàng không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $maHang]);
    }

    public function createMaHang(Request $request)
    {
        $input = $request->jsonBody();
        $ma_hang = $input['ma_hang'] ?? '';
        $ten_hang = $input['ten_hang'] ?? '';

        return new JsonResponse((new AdminService())->createMaHang($ma_hang, $ten_hang));
    }

    public function updateMaHang(Request $request)
    {
        $input = $request->jsonBody();
        $ma_hang = $input['ma_hang'] ?? '';
        $ten_hang = $input['ten_hang'] ?? '';
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;

        return new JsonResponse((new AdminService())->updateMaHang($this->id($request), $ma_hang, $ten_hang, $is_active));
    }

    public function deleteMaHang(Request $request)
    {
        return new JsonResponse((new AdminService())->deleteMaHang($this->id($request)));
    }

    // --- cong-doan ---

    public function congDoanList(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getCongDoanList()]);
    }

    public function congDoan(Request $request)
    {
        $congDoan = (new AdminService())->getCongDoan($this->id($request));
        if (!$congDoan) {
            return JsonResponse::error('Công đoạn không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $congDoan]);
    }

    public function createCongDoan(Request $request)
    {
        $input = $request->jsonBody();
        $ma_cong_doan = $input['ma_cong_doan'] ?? '';
        $ten_cong_doan = $input['ten_cong_doan'] ?? '';
        $la_cong_doan_thanh_pham = isset($input['la_cong_doan_thanh_pham']) ? intval($input['la_cong_doan_thanh_pham']) : 0;

        return new JsonResponse((new AdminService())->createCongDoan($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham));
    }

    public function updateCongDoan(Request $request)
    {
        $input = $request->jsonBody();
        $ma_cong_doan = $input['ma_cong_doan'] ?? '';
        $ten_cong_doan = $input['ten_cong_doan'] ?? '';
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
        $la_cong_doan_thanh_pham = isset($input['la_cong_doan_thanh_pham']) ? intval($input['la_cong_doan_thanh_pham']) : 0;

        return new JsonResponse((new AdminService())->updateCongDoan($this->id($request), $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham));
    }

    public function deleteCongDoan(Request $request)
    {
        return new JsonResponse((new AdminService())->deleteCongDoan($this->id($request)));
    }

    // --- routing ---

    public function routingList(Request $request)
    {
        $ma_hang_id = $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : 0;

        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getRoutingList($ma_hang_id)]);
    }

    public function routing(Request $request)
    {
        $routing = (new AdminService())->getRouting($this->id($request));
        if (!$routing) {
            return JsonResponse::error('Routing không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $routing]);
    }

    public function addRouting(Request $request)
    {
        $input = $request->jsonBody();
        $ma_hang_id = intval($input['ma_hang_id'] ?? 0);
        $cong_doan_id = intval($input['cong_doan_id'] ?? 0);
        $thu_tu = intval($input['thu_tu'] ?? 1);
        $bat_buoc = isset($input['bat_buoc']) ? intval($input['bat_buoc']) : 1;
        $la_cong_doan_tinh_luy_ke = isset($input['la_cong_doan_tinh_luy_ke']) ? intval($input['la_cong_doan_tinh_luy_ke']) : 0;
        $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
        $ghi_chu = $input['ghi_chu'] ?? '';

        return new JsonResponse((new AdminService())->addRouting($ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu));
    }

    public function updateRouting(Request $request)
    {
        $input = $request->jsonBody();
        $thu_tu = intval($input['thu_tu'] ?? 1);
        $bat_buoc = isset($input['bat_buoc']) ? intval($input['bat_buoc']) : 1;
        $la_cong_doan_tinh_luy_ke = isset($input['la_cong_doan_tinh_luy_ke']) ? intval($input['la_cong_doan_tinh_luy_ke']) : 0;
        $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
        $ghi_chu = $input['ghi_chu'] ?? '';

        return new JsonResponse((new AdminService())->updateRouting($this->id($request), $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu));
    }

    public function removeRouting(Request $request)
    {
        return new JsonResponse((new AdminService())->removeRouting($this->id($request)));
    }

    // --- moc-gio ---

    public function mocGioCaList(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getCaListForMocGio()]);
    }

    public function mocGioList(Request $request)
    {
        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : null;
        $line_id = $request->query('line_id');

        return new JsonResponse(['success' => true, 'data' => (new AdminService())->getMocGioList($ca_id, $line_id)]);
    }

    public function mocGio(Request $request)
    {
        $mocGio = (new AdminService())->getMocGio($this->id($request));
        if (!$mocGio) {
            return JsonResponse::error('Mốc giờ không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $mocGio]);
    }

    public function copyMocGioDefault(Request $request)
    {
        $input = $request->jsonBody();
        $ca_id = intval($input['ca_id'] ?? 0);
        $line_id = intval($input['line_id'] ?? 0);

        return new JsonResponse((new AdminService())->copyMocGioDefaultToLine($ca_id, $line_id));
    }

    public function createMocGio(Request $request)
    {
        $input = $request->jsonBody();
        $ca_id = intval($input['ca_id'] ?? 0);
        $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
        $gio = $input['gio'] ?? '';
        $thu_tu = intval($input['thu_tu'] ?? 1);
        $so_phut_hieu_dung_luy_ke = intval($input['so_phut_hieu_dung_luy_ke'] ?? 0);

        return new JsonResponse((new AdminService())->createMocGio($ca_id, $line_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke));
    }

    public function updateMocGio(Request $request)
    {
        $input = $request->jsonBody();
        $gio = $input['gio'] ?? '';
        $thu_tu = intval($input['thu_tu'] ?? 1);
        $so_phut_hieu_dung_luy_ke = intval($input['so_phut_hieu_dung_luy_ke'] ?? 0);
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;

        return new JsonResponse((new AdminService())->updateMocGio($this->id($request), $gio, $thu_tu, $so_phut_hieu_dung_luy_ke, $is_active));
    }

    public function deleteMocGio(Request $request)
    {
        return new JsonResponse((new AdminService())->deleteMocGio($this->id($request)));
    }

    // --- bao-cao ---

    public function bulkCreateBaoCao(Request $request)
    {
        $input = $request->jsonBody();
        $session = Auth::getSession();
        $items = $input['items'] ?? [];
        $skipExisting = isset($input['skip_existing']) ? (bool) $input['skip_existing'] : true;

        if (empty($items)) {
            return JsonResponse::error('Danh sách items không được rỗng', 400);
        }

        foreach ($items as $index => $item) {
            if (empty($item['line_id']) || empty($item['ma_hang_id']) || empty($item['ngay']) || empty($item['ca_id'])) {
                return JsonResponse::error("Item $index thiếu thông tin bắt buộc (line_id, ma_hang_id, ngay, ca_id)", 400);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['ngay'])) {
                return JsonResponse::error("Item $index có định dạng ngày không hợp lệ (yêu cầu YYYY-MM-DD)", 400);
            }
        }

        return new JsonResponse((new NangSuatService())->bulkCreateBaoCao($items, $session['ma_nv'], $skipExisting));
    }

    // `/api/admin/<tài nguyên không có>`: router cũ trả message riêng.
    public function unknownResource(Request $request)
    {
        return JsonResponse::error('Resource không hợp lệ', 404);
    }

    private function id(Request $request)
    {
        return intval($request->param('id'));
    }
}
