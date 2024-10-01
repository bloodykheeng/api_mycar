<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ResetCodePassword;

class CodeCheckController extends Controller
{
    public function __invoke(Request $request)
    {
    
        // Validate the incoming request data
        $data = $request->validate([
            'code' => 'required|string|exists:reset_code_passwords',
            'email' => 'required|email|exists:users',
        ]);

        try {
        
        // Find the password reset record based on the provided code
        //$passwordReset = ResetCodePassword::firstWhere('code', $request->code);
        $passwordReset = ResetCodePassword::where('code', $request->code)
            ->where('email', $request->email)
            ->first();

        // Check if the reset code exists
        if (!$passwordReset) {
            return response(['message' => 'Invalid reset code'], 422);
        }

        // Check if the reset code has expired (one-hour validity)
        if ($passwordReset->created_at->addMinutes(5) < now()) {
            // If expired, delete the reset code and return an error response
            $passwordReset->delete();
            return response(['message' => trans('password code has expired')], 422);
        }

        // Return a success response if the code is valid and not expired
        return response(['message' => 'Reset code is valid', 'userid' => $passwordReset->user_id], 200);
        } catch (\Exception $e) {
            // Log the exception
            // \Log::error($e);

            // Return a generic error response
            return response(['message' => 'An error occurred'], 500);
        }
    }


    public function resetPassword(Request $request, $id)
    {
        // Validate the incoming request data
        $data = $request->validate([
            'password' => [
                'required',
                'string',
                'min:6', // Minimum length of 6 characters
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'password_confirmation' => [
                'required',
                'string',
                'min:6', // Minimum length of 6 characters
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
        ]);

        try {
            // Find the user based on the provided email
            $user = User::find($id);

            // Update the user's password with the new password provided in the request
            $user->update(['password' => bcrypt($data['password'])]);

            // Return a success response
            return response(['message' => 'Password has been successfully updated'], 200);
        } catch (\Exception $e) {
            // Log the exception
            // \Log::error($e);

            // Return a generic error response
            return response(['message' => 'An error occurred'], 500);
        }
    }


}

