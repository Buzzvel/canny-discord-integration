<?php

namespace App\Services\Canny;

use Firebase\JWT\JWT;

class CannyIOService
{
    private $cannyPrivateKey;

    public function __construct()
    {
        $this->cannyPrivateKey = config('canny.private_key');
    }

    public function generateToken($userId,$userEmail, $userName, $avatarUrl = null ){

        $userData = [
            'avatarURL' => $avatarUrl, // optional, but preferred
            'email' => $userEmail,
            'id' => $userId,
            'name' => $userName,
        ];

       return JWT::encode($userData, $this->cannyPrivateKey, "HS256");
    }

    public function redirectURI($companyID, $ssoToken, $redirectUrl): string
    {
        return  "https://canny.io/api/redirects/sso?companyID=".$companyID."&ssoToken=".$ssoToken."&redirect=".$redirectUrl;
    }
}
