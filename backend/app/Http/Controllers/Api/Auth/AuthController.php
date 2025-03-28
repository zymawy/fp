<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends BaseController
{
    /**
     * Register a new user
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->response->array([
            'success' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ])->setStatusCode(201);
    }

    /**
     * Log in a user and return a JWT token
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->response->errorUnauthorized('Invalid credentials');
        }

        $user = Auth::user();

        return $this->response->array([
            'success' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ]);
    }

    /**
     * Log out the authenticated user
     *
     * @return \Dingo\Api\Http\Response
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->response->array(['success' => true, 'message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            throw new HttpException(500, 'Failed to logout, please try again.');
        }
    }

    /**
     * Refresh the token
     *
     * @return \Dingo\Api\Http\Response
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            
            return $this->response->array([
                'success' => true,
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ]
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, 'Failed to refresh token, please login again.');
        }
    }

    /**
     * Send reset password link
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->response->array(['success' => true, 'message' => 'Password reset link sent to your email.']);
        }

        throw new HttpException(500, 'Failed to send reset link.');
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->response->array(['success' => true, 'message' => 'Password has been reset successfully.']);
        }

        throw new HttpException(500, 'Failed to reset password.');
    }
} 