<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Canny\CannyIOService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Redirect the user to the Provider authentication page.
     *
     */
    public function redirect(Request  $request)
    {
        // Here we will detect if we receive redirect from canny.io
        // and we will override discord callback to get query string
        $redirect = $request->query('redirect');

        $state = null;

        if($redirect){
            $queryString = $request->query();
            if(auth()->check()){
                $user = auth()->user();
                return redirect($this->redirectToCannyUrl($user, (object) $queryString));
            }
            $queryString = json_encode($queryString); // Convert to json to be able to encode
            $state       = base64_encode($queryString); // Encode to protect the content
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

        if($state){

            $queryString = base64_decode($state); // Decodify the state (queryString)
            $queryString = json_decode($queryString); // Query String Object

            return redirect($this->redirectToCannyUrl($user, $queryString));
        }

        return redirect('/dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    private function redirectToCannyUrl($user, $queryString): string
    {
        $cannyService   = new CannyIOService();
        $ssoToken       = $cannyService->generateToken($user->id, $user->email, $user->name, $user->avatar_url);
        $redirectUrl    = $queryString->redirect;
        $companyID      = $queryString->companyID;

        $url =   $cannyService->redirectURI($companyID, $ssoToken, $redirectUrl);

        Log::info("------------------------------------------");
        Log::info("------------------------------------------");
        Log::info("User");
        Log::info(json_encode($user));
        Log::info("------------------------------------------");
        Log::info("Query String");
        Log::info(json_encode($queryString));
        Log::info("------------------------------------------");
        Log::info("SSO Token");
        Log::info($ssoToken);
        Log::info("------------------------------------------");
        Log::info("URL");
        Log::info($url);
        Log::info("------------------------------------------");
        Log::info("------------------------------------------");
        Log::info("");

    }



}
