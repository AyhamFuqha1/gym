<?php



use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\ExercisesController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GeneralExercisesController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserInjuriesController;
use App\Models\UserInjuries;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\GeneralNutritionController;
use App\Http\Controllers\FoodController;

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
/*-----*/
Route::get('/members',[MembersController::class,'index']);
Route::post('/members',[MembersController::class,'store']);
Route::get('/members/{id}',[MembersController::class,'show']);
//Route::get('/members',[MembersController::class,'store']);
//**--------------------- */
Route::get('/generalExercise',[GeneralExercisesController::class,'index']);
Route::post('/generalExercise',[GeneralExercisesController::class,'store']);
Route::get('/generalExercise/{id}',[GeneralExercisesController::class,'show']);
Route::get('/generalExercise/{id}/exercises', [ExercisesController::class, 'getByGeneralExerciseId']);
Route::put('/generalExercise/{id}', [GeneralExercisesController::class, 'update']);
Route::delete('/generalExercise/{id}',[GeneralExercisesController::class,'destroy']);
//**---------------------------- */
Route::post('/exercises', [ExercisesController::class, 'store']);
Route::get('/exercises/{id}',[ExercisesController::class,'show']);
Route::put('/exercises/{id}',[ExercisesController::class,'update']);
Route::delete('/exercises/{id}',[ExercisesController::class,'destroy']);
//**---------------------------- */
Route::get('/userInjuries',[UserInjuriesController::class,'index']);
Route::post('/userInjuries',[UserInjuriesController::class,'store']);
Route::get('/userInjuries/dashboard',[UserInjuriesController::class,'dashboard']);
Route::get('/userInjuries/{id}',[UserInjuriesController::class,'show']);
Route::put('/userInjuries/{id}',[UserInjuriesController::class,'update']);
Route::delete('/userInjuries/{id}',[UserInjuriesController::class,'destroy']);
/**----------------------------- */
Route::get('/feedback/dashboard',[FeedbackController::class,'dashboard']);

// News & Offers endpoints
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/stats', [NewsController::class, 'stats']);
Route::post('/news', [NewsController::class, 'store']);
Route::delete('/news/{id}', [NewsController::class, 'destroy']);
// Plans
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/plans', [PlanController::class, 'store']);
Route::get('/plans/{id}', [PlanController::class, 'show']);
Route::put('/plans/{id}', [PlanController::class, 'update']);
Route::delete('/plans/{id}', [PlanController::class, 'destroy']);

// General Nutrition
Route::middleware(['auth:sanctum', 'check.sub'])->group(function () {
    Route::get('/generalNutrition', [GeneralNutritionController::class, 'index']);
});
Route::post('/generalNutrition', [GeneralNutritionController::class, 'store']);
Route::get('/generalNutrition/{id}', [GeneralNutritionController::class, 'show']);
Route::put('/generalNutrition/{id}', [GeneralNutritionController::class, 'update']);
Route::delete('/generalNutrition/{id}', [GeneralNutritionController::class, 'destroy']);

// Foods
Route::apiResource('foods', FoodController::class);
Route::get('/general-nutrition', [GeneralNutritionController::class, 'index']);

// Dashboard
Route::middleware('auth:sanctum')->get('/dashboard', [DashBoardController::class, 'dashboard']);
