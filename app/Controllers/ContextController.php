<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\DanhMucLookup;

class ContextController
{
    public function __construct(private DanhMucLookup $danhMuc)
    {
    }

    public function show(Request $request)
    {
        $session = Auth::getSession();

        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : null;

        if ($session['line_id']) {
            $context = $this->danhMuc->getContext($session['line_id'], $ca_id);
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
