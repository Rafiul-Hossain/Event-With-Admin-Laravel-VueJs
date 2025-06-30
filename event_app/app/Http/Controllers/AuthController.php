<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Mail\RegistrationConfirmationMail;
use App\Mail\OTPEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Exception;

class AuthController extends Controller
{
    public function memberRegistration(Request $request){
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'phone' => 'required|string|max:11',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg'
        ]);
        
        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validation->errors()
            ], 400);
        }
        
        $profile_image = null;
        if($request->hasFile('profile_image')){
            $image = $request->file('profile_image');
            $image_name = 'profile_image_'.time().'.'.$image->getClientOriginalExtension();
            $image->storeAs('profile_image', $image_name);
            $profile_image = 'storage/profile_image/'.$image_name;
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'user',
            'profile_image' => $profile_image,
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        // Send confirmation email
        Mail::to($user->email)->send(new RegistrationConfirmationMail($user));

        return response()->json([
            'status' => true,
            'message' => 'Registration Success',
            'data' => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    public function login(Request $request) {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validation->fails()){
            return response()->json([
                'status'=> false,
                'message'=> 'validation error',
                'errors'=> $validation->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'status'=> false,
                'message'=> 'Credentials do not match',
            ], 400);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'status'=> true,
            'message'=> 'Login successful',
            'data'=> new UserResource($user),
            'token'=> $token
        ], 200);
    }

    public function user(Request $request, $id) {
        $user = User::find($id);
        return response()->json([
            'status'=> true,
            'message'=> 'User fetched successfully',
            'data'=> new UserResource($user)
        ], 200);
    }

    // Solution 1: Using Cache instead of Session
    public function sendOTPCode(Request $request){
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        
        if($validation->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validation->errors()
            ], 400);
        }
        
        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();
        
        if($count == 1){
            try {
                Mail::to($email)->send(new OTPEmail($otp));
                User::where('email', '=', $email)->update(['otp' => $otp]);
                
                // Store email in cache for 10 minutes instead of session
                Cache::put('otp_email_' . $email, $email, 600);
                
                return response()->json([
                    'status' => true,
                    'message' => 'OTP has been sent to your email address',
                    'email' => $email // Return email so frontend can use it
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send OTP'
                ], 500);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Email address not found'
            ], 404);
        }
    }

    public function verifyOtp(Request $request){
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:4',
        ]);
        
        if($validation->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validation->errors()
            ], 400);
        }

        $email = $request->input('email');
        $otp = $request->input('otp');
        
        // Check if email exists in cache
        if(!Cache::has('otp_email_' . $email)){
            return response()->json([
                'status' => false,
                'message' => 'OTP expired or invalid email'
            ], 400);
        }
        
        $user = User::where('email', $email)->where('otp', $otp)->first();
        
        if($user){
            // Clear OTP and mark as verified
            User::where('email', $email)->update(['otp' => '0']);
            
            // Store verification status in cache for 10 minutes
            Cache::put('otp_verified_' . $email, true, 600);
            
            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully',
                'email' => $email
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ], 400);
        }
    }

    public function resetPassword(Request $request){
        try{
            $validation = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',
            ]);
            
            if($validation->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validation->errors()
                ], 400);
            }
            
            $email = $request->input('email');
            $password = $request->input('password');
            
            // Check if OTP was verified
            if(!Cache::has('otp_verified_' . $email)){
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify OTP first'
                ], 400);
            }
            
            $user = User::where('email', $email)->first();
            if(!$user){
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Update password
            User::where('email', $email)->update([
                'password' => Hash::make($password)
            ]);
            
            // Clear verification cache
            Cache::forget('otp_verified_' . $email);
            Cache::forget('otp_email_' . $email);
            
            return response()->json([
                'status' => true,
                'message' => 'Password reset successful',
            ], 200);

        } catch (Exception $exception){
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'=> true,
            'message'=> 'Logout successful',
        ], 200);
    }
}