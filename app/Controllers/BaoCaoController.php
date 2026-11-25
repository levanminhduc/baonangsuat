<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\BaoCaoBulkService;
use App\Services\BaoCaoService;
use App\Services\BaoCaoWorkflowService;
use App\Services\DanhMucLookup;

// Phạm vi (đăng nhập / LINE) kiểm ở middleware nhóm `report`; vai trò + CSRF khai trong route.
class BaoCaoController
{
    public function __construct(
        private BaoCaoService $baoCao,
        private BaoCaoWorkflowService $workflow,
        private BaoCaoBulkService $bulk,
        private DanhMucLookup $danhMuc,
    ) {
    }

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
        $list = $this->baoCao->getBaoCaoList($session['line_id'], $filters);

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

        return $this->withStatus($this->baoCao->createBaoCao($input, $session['ma_nv']));
    }

    public function show(Request $request)
    {
        $baoCao = $this->baoCao->getBaoCao($this->id($request));
        if (!$baoCao) {
            return JsonResponse::error('Báo cáo không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $baoCao]);
    }

    public function routing(Request $request)
    {
        $baoCao = $this->baoCao->getBaoCao($this->id($request));
        if (!$baoCao) {
            return JsonResponse::error('Báo cáo không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $this->danhMuc->getRouting($baoCao['ma_hang_id'], $baoCao['line_id'])]);
    }

    public function updateEntries(Request $request)
    {
        $input = $request->jsonBody();
        $entries = $input['entries'] ?? [];
        $version = $input['version'] ?? 1;

        return new JsonResponse($this->baoCao->updateEntries($this->id($request), $entries, $version, $this->maNv()));
    }

    public function updateHeader(Request $request)
    {
        $input = $request->jsonBody();
        $version = $input['version'] ?? 1;

        return $this->withStatus($this->baoCao->updateHeader($this->id($request), $input, $version));
    }

    public function submit(Request $request)
    {
        return new JsonResponse($this->workflow->submitBaoCao($this->id($request), $this->maNv()));
    }

    public function approve(Request $request)
    {
        return new JsonResponse($this->workflow->approveBaoCao($this->id($request), $this->maNv()));
    }

    public function unlock(Request $request)
    {
        return new JsonResponse($this->workflow->unlockBaoCao($this->id($request), $this->maNv()));
    }

    public function complete(Request $request)
    {
        return new JsonResponse($this->workflow->completeBaoCao($this->id($request), $this->maNv()));
    }

    public function reopen(Request $request)
    {
        return new JsonResponse($this->workflow->reopenBaoCao($this->id($request), $this->maNv()));
    }

    public function destroy(Request $request)
    {
        return new JsonResponse($this->bulk->deleteBaoCao($this->id($request)));
    }

    // Service trả 'status' (422/503) chỉ khi cần mã HTTP khác 200; không đưa vào body.
    private function withStatus(array $result)
    {
        $status = $result['status'] ?? 200;
        unset($result['status']);

        return new JsonResponse($result, $status);
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
