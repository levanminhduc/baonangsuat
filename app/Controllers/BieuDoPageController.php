<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\HtmlResponse;
use App\Http\RedirectResponse;
use App\Http\Request;
use App\Support\View;

class BieuDoPageController
{
    public function show(Request $request)
    {
        $isAdmin = Auth::checkRole(['admin']);
        if (!$isAdmin && !Auth::hasLine()) {
            return new RedirectResponse(Auth::getDefaultPage());
        }

        return new HtmlResponse(View::render('bieu-do', ['session' => Auth::getSession(), 'isAdmin' => $isAdmin]));
    }
}
