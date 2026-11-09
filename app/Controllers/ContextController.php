<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\NangSuatService;

class ContextController
{
    public function show(Request $request)
    {
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
        $context['can_create_report_any_line'] = Auth::canCreateReportForAnyLine();

        return new JsonResponse(['success' => true, 'data' => $context]);
    }
}
