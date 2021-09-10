<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Canny\CannyIOService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Redirect the user to the Provider authentication page.
     *
     * @return JsonResponse
     */
    public function redirect(Request  $request)
    {
        // Here we will detect if we receive redirect from canny.io
        // and we will override discord callback to get query string
        $redirect = $request->query('redirect');

        $state = null;
        $user = null;

        if($redirect){
            $queryString = $request->query();
            if(auth()->check()){
                $user = \auth()->user();

                $cannyService = new CannyIOService();
                $ssoToken       = $cannyService->generateToken($user->id, $user->email, $user->name, $user->avatar_url);
                $redirectUrl    = $queryString->redirect;
                $companyID      = $queryString->companyID;

                $cannyUrl = "https://canny.io/api/redirects/sso?companyID=".$companyID."&ssoToken=".$ssoToken."&redirect=".$redirectUrl;
                return redirect($cannyUrl);
            }
            $state = base64_encode(json_encode($queryString));
        }
        if(auth()->check()) {
            return  redirect('/dashboard');
        }


            $url =  Socialite::driver('discord')
            ->with(['state' =>$state])
            ->setScopes(['identify', 'email', 'guilds.join'])
            ->stateless()->redirect()->getTargetUrl();

        return redirect($url);

    }

    /**
     * Obtain the user information from Provider.
     * @param  UserRepository  $userRepository
     * @return JsonResponse
     */
    public function callback(Request $request)
    {
        try {
            $userSocialite = Socialite::driver("discord")->stateless()->user();

        } catch (ClientException $exception) {

            return  redirect('/')->with(['status' => "Problem login with Discord. Please try again."]);
        }

        $user = User::updateOrCreate(
            [
                'provider_identifier' => $userSocialite->getId()
            ],
            [
                'email' => $userSocialite->getEmail(),
                'uuid' => Str::uuid(),
                'name' => $userSocialite->getName(),
                'avatar_url' => $userSocialite->getAvatar(),
                'email_verified_at' => null,
                'provider_type' => 'discord',
                'provider_nickname' => $userSocialite->getNickname(),
                'locale' => "null",
            ]

        );
       Auth::login($user);

        // Here we will receive the query string that we need to get token
        $state = $request->get('state');
        $queryString = null;
        if($state){
            $queryString = json_decode(base64_decode($state));
            $cannyService = new CannyIOService();
            $ssoToken       = $cannyService->generateToken($user->id, $user->email, $user->name, $user->avatar_url);
            $redirectUrl    = $queryString->redirect;
            $companyID      = $queryString->companyID;

            $cannyUrl = $cannyService->redirectURI($companyID, $ssoToken, $redirectUrl);
            return redirect($cannyUrl);
        }

        return redirect('/dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }



}
