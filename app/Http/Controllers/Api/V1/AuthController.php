<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @group Authentication
 * Manage API tokens.
 */
class AuthController extends ApiController
{
    public function __construct(protected AuditService $audit) {}

    /**
     * Login
     * Authenticate with email + password and receive a Sanctum API token.
     *
     * @unauthenticated
     * @response 200 {"success":true,"message":"Authenticated.","data":{"user":{...},"token":"1|abc..."}}
     * @response 401 {"success":false,"message":"Invalid credentials."}
     * @response 429 {"success":false,"message":"Too many login attempts. Please try again later."}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->error('Too many login attempts. Please try again in '
                . RateLimiter::availableIn($throttleKey) . ' seconds.', 429);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($throttleKey, 60);

            $this->audit->log(
                action:        'login_failed',
                auditableType: 'User',
                auditableId:   0,
                afterData:     ['email' => $request->email, 'ip' => $request->ip()]
            );

            return $this->unauthorized('Invalid credentials.');
        }

        RateLimiter::clear($throttleKey);

        $user      = Auth::user();
        $deviceName = $request->input('device_name', 'api-client-' . $request->ip());
        $token     = $user->createToken($deviceName)->plainTextToken;

        $this->audit->log(
            action:        'login',
            auditableType: 'User',
            auditableId:   $user->id,
            afterData:     ['ip' => $request->ip(), 'device' => $deviceName]
        );

        return $this->ok([
            'user'  => new UserResource($user->load('roles')),
            'token' => $token,
        ], 'Authenticated.');
    }

    /**
     * Logout
     * Revoke the current API token.
     *
     * @authenticated
     * @response 200 {"success":true,"message":"Logged out successfully.","data":null}
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $this->audit->log(
            action:        'logout',
            auditableType: 'User',
            auditableId:   $user->id,
            afterData:     ['ip' => $request->ip()]
        );

        return $this->ok(null, 'Logged out successfully.');
    }

    /**
     * Me
     * Return the authenticated user's profile.
     *
     * @authenticated
     * @response 200 {"success":true,"data":{...UserResource...}}
     */
    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()->load('roles')));
    }

    /**
     * Refresh token
     * Revoke current token and issue a fresh one.
     *
     * @authenticated
     * @response 200 {"success":true,"data":{"token":"2|xyz..."}}
     */
    public function refresh(Request $request): JsonResponse
    {
        $user  = $request->user();
        $old   = $user->currentAccessToken();
        $token = $user->createToken($old->name)->plainTextToken;
        $old->delete();

        return $this->ok(['token' => $token], 'Token refreshed.');
    }
}
