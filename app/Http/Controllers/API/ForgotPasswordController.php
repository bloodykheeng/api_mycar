<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCodeResetPassword;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        // Delete all old code that user send before.
        ResetCodePassword::where('email', $request->email)->delete();

        // Generate random code
        $data['code'] = mt_rand(100000, 999999);

        $user = User::firstWhere('email', $request->email);
        $data['user_id'] = $user->id;
        // Create a new code
        $codeData = ResetCodePassword::create($data);

        try {
            // Send email to user
            Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));
            return response(['message' => 'A password reset code has been sent to your email', 'email' => $request->email], 200);
        } catch (\Exception $e) {
            // \Log::error('Mail sending failed: ' . $e->getMessage());
            return response(['message' => 'Failed to send email'], 500);
        }
    }
}
