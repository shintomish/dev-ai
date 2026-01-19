<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with('status', 'パスワードリセット用のリンクをメールで送信しました。');
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return back()
            ->withErrors(['email' => 'このメールアドレスは登録されていません。']);
    }
}
