<?php

namespace App\Services\Canny;

use Firebase\JWT\JWT;

class CannyIOService
{
    private $cannyPrivateKey;

    public function __construct()
    {
        $this->cannyPrivateKey = "72a3f085-598e-54e7-f210-7282792444e7"; //config('settings.canny_io.private_key');
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
}
