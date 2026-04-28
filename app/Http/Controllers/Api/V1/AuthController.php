<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\UserVerification;
use Carbon\Carbon;
use App\Models\UserDevice;

class AuthController extends Controller
{
   // ---------------------------
    // USER REGISTRATION
    // ---------------------------
    public function register(Request $req)
    {
        try {
            $req->validate([
                'name' => 'required|string|max:150',
                'email' => 'nullable|email|unique:users,email',
                'phone' => 'required|string|max:30|unique:users,phone',
                'password' => 'required|min:6',
                'device_name' => 'required',
                'device_os' => 'required',
                'device_model' => 'required',
                'fcm_token' => 'required',
                'app_version' => 'required'
            ]);

            $user = User::create([
                'name'  => $req->name,
                'email' => $req->email,
                'phone' => $req->phone,
                'password' => Hash::make($req->password),
                'role' => 'user',
                'status' => 'active',
            ]);

            // OTP Generation (not stored in DB)
            $otp = rand(100000, 999999);
            // Save OTP
            UserVerification::create([
                'user_id' => $user->id,
                'otp_code' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            $token = $user->createToken('user_token')->plainTextToken;

              UserDevice::updateOrCreate(
                [
                     'device_name'  => $req->device_name, // unique per device
                ],
                [
                    'user_id' => $user->id,
                    'fcm_token' => $req->fcm_token,
                    
                    'device_os'    => $req->device_os,
                    'device_model' => $req->device_model,
                    'app_version'  => $req->app_version,
                    'last_login_at' => Carbon::now()
                ]
            );



            return response()->json([
                'status' => true,
                'message' => 'User registered successfully. OTP sent.',
                'data' => [
                    'otp' => $otp,     // remove in production
                    'token' => $token,
                    'user' => $user,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    // ---------------------------
    // USER LOGIN (email or phone)
    // ---------------------------
    public function login(Request $req)
    {
        try {
            $req->validate([
                'username' => 'required',
                'password' => 'required',
                'device_name' => 'required',
                'device_os' => 'required',
                'device_model' => 'required',
                'fcm_token' => 'required',
                'app_version' => 'required'
            ]);

            $user = User::where('email', $req->username)
                ->orWhere('phone', $req->username)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'error' => 'User not found'
                ]);
            }

            if ($user->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'error' => 'Your account is not active'
                ]);
            }

            if (!Hash::check($req->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid credentials'
                ]);
            }

            $token = $user->createToken('user_token')->plainTextToken;

            // ------------------------------
            // SAVE USER DEVICE INFORMATION
            // ------------------------------

            UserDevice::updateOrCreate(
                [
                     'device_name'  => $req->device_name, // unique per device
                ],
                [
                    'user_id' => $user->id,
                    'fcm_token' => $req->fcm_token,
                    
                    'device_os'    => $req->device_os,
                    'device_model' => $req->device_model,
                    'app_version'  => $req->app_version,
                    'last_login_at' => Carbon::now()
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'user'  => $user
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    // ---------------------------
    // VERIFY OTP
    // ---------------------------
    public function verifyOtp(Request $req)
    {
        try {
            $req->validate([
                'user_id' => 'required',
                'otp' => 'required'
            ]);

            $verification = UserVerification::where('user_id', $req->user_id)
                ->where('otp_code', $req->otp)
                ->first();

            if (!$verification) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid OTP'
                ]);
            }

            if (Carbon::now()->gt($verification->expires_at)) {
                return response()->json([
                    'status' => false,
                    'error' => 'OTP expired'
                ]);
            }

            // Mark as verified
            $verification->verified_at = Carbon::now();
            $verification->save();

            // Update user phone_verified time
            $user = User::find($req->user_id);
            $user->phone_verified_at = Carbon::now();
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    // ---------------------------
    // FORGOT PASSWORD
    // ---------------------------
    public function forgotPassword(Request $req)
    {
        try {
            $req->validate([
                'phone' => 'required'
            ]);

            $user = User::where('phone', $req->phone)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'error' => 'Phone number not found'
                ]);
            }

            $otp = rand(100000, 999999);

            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'otp' => $otp,  // remove in production
                    'user_id' => $user->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    // ---------------------------
    // RESET PASSWORD
    // ---------------------------
    public function resetPassword(Request $req)
    {
        try {
            $req->validate([
                'user_id'  => 'required',
                'password' => 'required|min:6'
            ]);

            $user = User::find($req->user_id);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'error' => 'User not found'
                ]);
            }

            $user->password = Hash::make($req->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password reset successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    // ---------------------------
    // PROFILE
    // ---------------------------
    public function profile(Request $req)
    {
        return response()->json([
            'status' => true,
            'data' => $req->user()
        ]);
    }


    // ---------------------------
    // LOGOUT
    // ---------------------------
    public function logout(Request $req)
    {
        $req->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }

}
