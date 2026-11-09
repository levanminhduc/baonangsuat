<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\HtmlResponse;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Support\View;

class NoLinePageController
{
    public function show(Request $request)
    {
        if (Auth::checkRole(['admin'])) {
            return new RedirectResponse('admin.php');
        }

        if (Auth::hasLine() || Auth::canCreateReportForAnyLine()) {
            return new RedirectResponse('nhap-nang-suat.php');
        }

        if (count(Auth::allowedLines()) > 0) {
            return new RedirectResponse('index.php');
        }

        return new HtmlResponse(View::render('no-line', ['session' => Auth::getSession()]));
    }
}
