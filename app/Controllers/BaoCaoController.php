<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\NangSuatService;

// Phạm vi (đăng nhập / LINE) kiểm ở middleware nhóm `report`; vai trò + CSRF khai trong route.
class BaoCaoController
{
    public function index(Request $request)
    {
        $session = Auth::getSession();
        $filters = [
            'ngay_tu' => $request->query('ngay_tu'),
            'ngay_den' => $request->query('ngay_den'),
            'ca_id' => $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : null,
            'ma_hang_id' => $request->hasQuery('ma_hang_id') ? intval($request->query('ma_hang_id')) : null,
            'include_completed' => $request->hasQuery('include_completed') && $request->query('include_completed') === '1',
        ];
        $list = (new NangSuatService())->getBaoCaoList($session['line_id'], $filters);

        return new JsonResponse(['success' => true, 'data' => $list]);
    }

    public function store(Request $request)
    {
        if (!Auth::canCreateReport()) {
            return JsonResponse::error('Không có quyền tạo báo cáo', 403);
        }
        $session = Auth::getSession();
        $input = $request->jsonBody();
        if (Auth::canCreateReportForAnyLine() && isset($input['line_id']) && intval($input['line_id']) > 0) {
            $input['line_id'] = intval($input['line_id']);
        } else {
            $input['line_id'] = $session['line_id'];
        }

        return new JsonResponse((new NangSuatService())->createBaoCao($input, $session['ma_nv']));
    }

    public function show(Request $request)
    {
        $baoCao = (new NangSuatService())->getBaoCao($this->id($request));
        if (!$baoCao) {
            return JsonResponse::error('Báo cáo không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $baoCao]);
    }

    public function routing(Request $request)
    {
        $service = new NangSuatService();
        $baoCao = $service->getBaoCao($this->id($request));
        if (!$baoCao) {
            return JsonResponse::error('Báo cáo không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $service->getRouting($baoCao['ma_hang_id'], $baoCao['line_id'])]);
    }

    public function updateEntries(Request $request)
    {
        $input = $request->jsonBody();
        $entries = $input['entries'] ?? [];
        $version = $input['version'] ?? 1;

        return new JsonResponse((new NangSuatService())->updateEntries($this->id($request), $entries, $version, $this->maNv()));
    }

    public function updateHeader(Request $request)
    {
        $input = $request->jsonBody();
        $version = $input['version'] ?? 1;

        return new JsonResponse((new NangSuatService())->updateHeader($this->id($request), $input, $version));
    }

    public function submit(Request $request)
    {
        return new JsonResponse((new NangSuatService())->submitBaoCao($this->id($request), $this->maNv()));
    }

    public function approve(Request $request)
    {
        return new JsonResponse((new NangSuatService())->approveBaoCao($this->id($request), $this->maNv()));
    }

    public function unlock(Request $request)
    {
        return new JsonResponse((new NangSuatService())->unlockBaoCao($this->id($request), $this->maNv()));
    }

    public function complete(Request $request)
    {
        return new JsonResponse((new NangSuatService())->completeBaoCao($this->id($request), $this->maNv()));
    }

    public function reopen(Request $request)
    {
        return new JsonResponse((new NangSuatService())->reopenBaoCao($this->id($request), $this->maNv()));
    }

    public function destroy(Request $request)
    {
        return new JsonResponse((new NangSuatService())->deleteBaoCao($this->id($request)));
    }

    private function id(Request $request)
    {
        return intval($request->param('id'));
    }

    private function maNv()
    {
        return Auth::getSession()['ma_nv'];
    }
}
