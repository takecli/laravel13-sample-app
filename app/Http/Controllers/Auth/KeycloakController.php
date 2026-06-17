<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Reponses\ApiResponse;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Socialite;

class KeycloakController extends Controller
{
    /**
     * リダイレクト
     *
     * @param  Request  $request
     * @param  int  $version
     * @return JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect(Request $request, int $version): RedirectResponse|JsonResponse
    {
        try {
            return Socialite::driver('keycloak')->redirect();
        } catch (Exception $e) {
            Log::error(__('messages.error', ['Redirect', 'resource' => 'Auth']));
            report($e);

            return ApiResponse::serverError();
        }
    }

    /**
     * コールバック
     *
     * @param  Request  $request
     * @param  int  $version
     * @return RedirectResponse
     */
    public function callback(Request $request, int $version): RedirectResponse
    {
        try {
            $keycloakUser = Socialite::driver('keycloak')->stateless()->user();
            if ($keycloakUser) {
                $user = User::firstOrCreate([
                    'keycloak_id' => $keycloakUser->getId(),
                ], [
                    'name' => $keycloakUser->getName(),
                    'email' => $keycloakUser->getEmail(),
                ]);

                if ($user) {
                    Auth::login($user);
                }
            }

            return redirect('/dashbord');
        } catch (Exception $e) {
            Log::error(__('messages.error', ['Callback', 'resource' => 'Auth']));
            report($e);

            return redirect('/error');
        }
    }
}
