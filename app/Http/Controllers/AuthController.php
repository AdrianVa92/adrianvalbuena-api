<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    private function _authenticate($data = [])
    {
        try {
            $user = User::where(function ($query) use ($data) {
                $query->where('username', $data['username'])
                    ->orWhere('email', $data['username']);
            })->where('status', 'active');

            $user = $user->first();

            if (!empty($user) &&  Hash::check($data['password'], $user->password)) {
                return $user;
            }
        } catch (Exception $e) {
            report($e);
        }

        return null;
    }

    /**
     * Create a new token.
     *
     * @param  \App\User   $user
     * @return string
     */
    protected function _generateToken(User $user, $remember = false) {
        $remember_duration = 'day';
            if ($remember) {
                $remember_duration = 'month';
            }

        $payload = [
            'jti' => md5($user->id . Carbon::now()->valueOf()),
            'iss' => "lumen-jwt", // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => (new \DateTime())->getTimeStamp(), // Time when JWT was issued.
            'exp' => (new \DateTime("+1 {$remember_duration}"))->getTimeStamp() // Expiration time
        ];

        $secret = env('JWT_SECRET') . '-Cr@veAdr1@n';
        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, $secret);
    }

    public function refresh(Request $request)
    {

        try {
            $response_code = 500;
            $response = [
                'status' => "error",
                'message' => ""
            ];

            $user = $request->auth;
            $user->load('groups');

            if (!empty($user)) {
                unset($user['jti']);
                $response['message'] = "Authorization refreshed.";
                $response['status'] = "success";

                $response['token'] = $request->bearerToken();
                $response['auth'] = $user;

                return response()->json($response, 200);
            }

            $response_code = 401;
            $response['message'] = "Unauthorized access.";
        } catch (\Exception $e) {
            report($e);
            $response['message'] = $e->getMessage();
        }

        return response()->json($response, $response_code);
    }

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @param  \App\User   $user
     * @return mixed
     */
    public function login(Request $request)
    {
        try {
            $response_code = 500;
            $response = [
                'status' => "error",
                'message' => ""
            ];

            $this->validate($request, [
                'username' => 'required',
                'password' => 'required|min:8|max:60'
            ]);

            $request_data = $request->only([
                'username',
                'password',
                'remember'
            ]);

            $user = $this->_authenticate($request_data);

            if (!empty($user) && $user) {
                $remember = !empty($request_data['remember']) ? $request_data['remember'] : false;

                $get_user = User::where('username', $request_data['username']);
                $get_user = $get_user->first();

                    if (!empty($get_user)) {
                        if ($get_user->login_attempt_expired_at == '') {

                            $token = $this->_generateToken($user, $remember);
                            $response['status'] = "success";
                            $response['message'] = "User authenticated successfully.";
                            $response['token'] = $token;
                            return response()->json($response, 200);

                        } else {
                            $date_time_now = Carbon::now();
                            $login_attempt_expired = Carbon::createFromFormat(
                                'Y-m-d H:i:s',
                                $get_user->login_attempt_expired_at
                            );
                            $diff_in_minutes = $login_attempt_expired
                                ->diffInMinutes($date_time_now);

                            if (
                                $diff_in_minutes == 0
                                || $date_time_now >= $login_attempt_expired
                            ) {
                                DB::table('users')
                                    ->where('username', $request_data['username'])
                                    ->update(['login_attempt_expired_at' => null,
                                    'login_attempt_count' => null]);

                                $token = $this->_generateToken($user, $remember);
                                $response['status'] = "success";
                                $response['message'] = "User authenticated
                                    successfully.";
                                $response['token'] = $token;
                                return response()->json($response, 200);

                            } else {
                                $response_code = 401;
                                $response['message'] = "Unauthorized access.
                                    Your Account still locked. Please wait for "
                                    . $diff_in_minutes . " minutes to login again.";
                            }
                        }
                    }

            } else {
                $failed_username = $request_data['username'];
                $get_user = User::where('username', $failed_username);
                $get_user = $get_user->first();

                    if (!empty($get_user)) {

                        $count_attempt = $get_user->login_attempt_count + 1;

                        if ($get_user->login_attempt_count >= 4) {
                            if ($get_user->login_attempt_expired_at == ''){
                                DB::table('users')
                                    ->where('username', $failed_username)
                                    ->update(['login_attempt_expired_at' => date(
                                        "Y-m-d H:i:s", strtotime("+1 hours")
                                    ), 'login_attempt_count' => $count_attempt]);

                                $response_code = 401;
                                $response['message'] = "Unauthorized access.
                                    Your Account was locked.
                                    Please wait for 1 hour to login again.";
                            } else {
                                $date_time_now = Carbon::now();
                                $login_attempt_expired = Carbon::createFromFormat(
                                    'Y-m-d H:i:s',
                                    $get_user->login_attempt_expired_at
                                );
                                $diff_in_minutes = $login_attempt_expired
                                    ->diffInMinutes($date_time_now);

                                if ($diff_in_minutes == 0
                                    || $date_time_now >= $login_attempt_expired
                                ) {
                                    DB::table('users')
                                    ->where('username', $failed_username)
                                    ->update(['login_attempt_expired_at' => date(
                                        "Y-m-d H:i:s", strtotime("+1 hours")
                                    )]);

                                    $response_code = 401;
                                    $response['message'] = "Unauthorized access.
                                        Your Account was locked again for 1 hour.";
                                } else {
                                    $login_expired = Carbon::createFromFormat(
                                        'Y-m-d H:i:s', $get_user->login_attempt_expired_at
                                    );

                                    $diff_in_minutes = $login_expired
                                        ->diffInMinutes($date_time_now);
                                    $response_code = 401;
                                    $response['message'] = "Unauthorized access.
                                        Your Account still locked.
                                        Please wait for " . $diff_in_minutes
                                        . " minutes to login again.";
                                }
                            }
                        } else {
                            DB::table('users')
                                ->where('username', $failed_username)
                                ->update(['login_attempt_count' => $count_attempt]);

                                $response_code = 401;

                            if($get_user->login_attempt_count == 3) {
                                $response['message'] = "Unauthorized access.
                                    You only have 1 login attempt left.";
                            } else {
                                $response['message'] = "Unauthorized access.";
                            }
                        }
                    }

            }

        } catch (ValidationException $e) {
            info($e);
            $errors = $e->errors();
            $response_code = 400;
            $response['message'] = "Input Error: " . (string) reset($errors)[0];
        } catch (Exception $e) {
            report($e);
            $response['message'] = $e->getMessage();
        }

        return response()->json($response, $response_code);
    }
}
