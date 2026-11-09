<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\HtmlResponse;
use App\Http\Request;
use App\Support\View;

class HuongDanPageController
{
    public function show(Request $request)
    {
        return new HtmlResponse(View::render('huong-dan', ['session' => Auth::getSession()]));
    }
}
