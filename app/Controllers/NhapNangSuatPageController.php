<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\HtmlResponse;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Support\View;

class NhapNangSuatPageController
{
    public function show(Request $request)
    {
        if (!Auth::checkRole(['admin']) && !Auth::canCreateReportForAnyLine() && !Auth::hasLine()) {
            return new RedirectResponse(Auth::getDefaultPage());
        }

        return new HtmlResponse(View::render('nhap-nang-suat', ['session' => Auth::getSession()]));
    }
}
