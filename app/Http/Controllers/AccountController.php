<?php

namespace App\Http\Controllers;

class AccountController extends Controller
{
    public function index()
    {
        return view('auth.account');
    }
}
