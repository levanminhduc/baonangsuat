<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Middleware\RequireLine;
use App\Http\Middleware\RequireLogin;
use App\Http\Request;
use App\Services\NangSuatService;

class ContextController
{
    public function show(Request $request)
    {
        // Quyền có điều kiện nên kiểm ở đây thay vì khai middleware trong route:
        // admin hoặc người được tạo báo cáo cho mọi LINE chỉ cần đăng nhập, còn lại phải có LINE.
        $isAdmin = Auth::checkRole(['admin']);
        $canCreateAnyLine = Auth::canCreateReportForAnyLine();
        $guard = ($isAdmin || $canCreateAnyLine) ? new RequireLogin() : new RequireLine();
        $denied = $guard->check($request, []);
        if ($denied !== null) {
            return $denied;
        }

        $session = Auth::getSession();
        $service = new NangSuatService();

        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : null;

        if ($session['line_id']) {
            $context = $service->getContext($session['line_id'], $ca_id);
        } else {
            $context = [];
        }

        $context['session'] = $session;
        $context['can_view_history'] = Auth::canViewHistory();
        $context['can_create_report'] = Auth::canCreateReport();
        $context['can_create_report_any_line'] = $canCreateAnyLine;

        return new JsonResponse(['success' => true, 'data' => $context]);
    }
}
