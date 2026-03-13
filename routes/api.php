<?php



use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');//////////
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'restetPassword']);
/*-----*/
Route::get('/profile/{id}', [ProfileController::class, 'show']);
Route::post('/profile', [ProfileController::class, 'store']);
Route::put('/profile/{id}', [ProfileController::class, 'update']);
Route::delete('/profile/{id}', [ProfileController::class, 'destroy']);

