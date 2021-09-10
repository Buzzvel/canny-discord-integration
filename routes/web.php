<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/login', [LoginController::class, 'redirect'])->name('discord.login');
Route::get('/login/callback', [LoginController::class, 'callback']);
Route::get('/auth/is-logged-in', [LoginController::class, 'isLoggedIn'])->name('discord.logged-in');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/logged-in', function (\Illuminate\Http\Request $request){

    //dd(\App\Models\User::all());
    $user = auth()->user();
    $query = $request->query();


    if(!$user){
        return redirect()->route('login', $query);
    }
    $r = new \App\Services\Canny\CannyIOService();
    $ssoToken       = $r->generateToken($user->id, $user->email, $user->name, null);
    $redirectUrl    = $request->query('redirect');
    $companyID      = $request->query('companyID');
    //

    $cannyUrl = "https://canny.io/api/redirects/sso?companyID=".$companyID."&ssoToken=".$ssoToken."&redirect=".$redirectUrl;


    return redirect($cannyUrl);

    if($user){
    }
    dd($query);

});

Route::get('/generate-token', function (){

    $r = new \App\Services\Canny\CannyIOService();

    $user = auth()->user();

    $d = $r->generateToken($user->id, $user->email, $user->name, null);

    dd($d);
});
