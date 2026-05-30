<?php

use App\Http\Controllers\AdminPlanManagementController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\AIRequestModifcationController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\CoachesController;
use App\Http\Controllers\CoachSessionController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\ExercisesController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GeneralExercisesController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\ModificationRequestController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NutritionFoodItemsController;
use App\Http\Controllers\NutritionVersionsController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramVersionController;
use App\Http\Controllers\PushTokensController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserGoalController;
use App\Http\Controllers\UserInjuriesController;
use App\Http\Controllers\UserNutritionPlansController;
use App\Http\Controllers\UserProgramController;
use App\Http\Controllers\GeneralNutritionController;
use App\Http\Controllers\FoodController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'restetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

/* Profile */
Route::get('/profile/user/{userId}', [ProfileController::class, 'showByUserId']);
Route::post('/profile', [ProfileController::class, 'store']);
Route::put('/profile/user/{userId}', [ProfileController::class, 'updateByUserId']);
Route::delete('/profile/user/{userId}', [ProfileController::class, 'destroyByUserId']);

/* Members */
Route::get('/members', [MembersController::class, 'index']);
Route::get('/members/{id}', [MembersController::class, 'show']);
Route::get('/members/overView/{id}', [MembersController::class, 'overview']);
Route::get('/members/nutrition/{id}', [MembersController::class, 'nutrition']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/members', [MembersController::class, 'store']);
    Route::post('/members/ReNewSubscription', [MembersController::class, 'reNewSubscription']);
    Route::post('/members/subscription/freeze/{id}', [MembersController::class, 'freezeSubscription']);
    Route::post('/members/subscription/resume/{id}', [MembersController::class, 'resumeSubscription']);
});

/* Coaches */
Route::middleware('auth:sanctum')->get('/coaches', [CoachesController::class, 'index']);

/* General Exercises */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/generalExercise', [GeneralExercisesController::class, 'index']);
});
Route::post('/generalExercise', [GeneralExercisesController::class, 'store']);
Route::get('/generalExercise/{id}', [GeneralExercisesController::class, 'show']);
Route::get('/generalExercise/{id}/exercises', [ExercisesController::class, 'getByGeneralExerciseId']);
Route::put('/generalExercise/{id}', [GeneralExercisesController::class, 'update']);
Route::delete('/generalExercise/{id}', [GeneralExercisesController::class, 'destroy']);

/* Exercises */
Route::post('/exercises', [ExercisesController::class, 'store']);
Route::get('/exercises/{id}', [ExercisesController::class, 'show']);
Route::put('/exercises/{id}', [ExercisesController::class, 'update']);
Route::delete('/exercises/{id}', [ExercisesController::class, 'destroy']);

/* User Injuries */
Route::get('/userInjuries', [UserInjuriesController::class, 'index']);
Route::post('/userInjuries', [UserInjuriesController::class, 'store']);
Route::get('/userInjuries/dashboard', [UserInjuriesController::class, 'dashboard']);
Route::get('/userInjuries/{id}', [UserInjuriesController::class, 'show']);
Route::put('/userInjuries/{id}', [UserInjuriesController::class, 'update']);
Route::delete('/userInjuries/{id}', [UserInjuriesController::class, 'destroy']);

/* Feedback */
Route::get('/feedback/dashboard', [FeedbackController::class, 'dashboard']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::get('/my-feedback', [FeedbackController::class, 'myFeedback']);
    Route::get('/feedback/{id}', [FeedbackController::class, 'show'])->whereNumber('id');
    Route::put('/feedback/{id}', [FeedbackController::class, 'update'])->whereNumber('id');
    Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy'])->whereNumber('id');
});

/* News */
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/stats', [NewsController::class, 'stats']);
Route::get('/news/public', [NewsController::class, 'publicNews']);
Route::get('/news/{id}', [NewsController::class, 'show']);
Route::post('/news', [NewsController::class, 'store']);
Route::put('/news/{id}', [NewsController::class, 'update']);
Route::delete('/news/{id}', [NewsController::class, 'destroy']);

/* Plans */
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/plans', [PlanController::class, 'store']);
Route::get('/plans/{id}', [PlanController::class, 'show']);
Route::put('/plans/{id}', [PlanController::class, 'update']);
Route::delete('/plans/{id}', [PlanController::class, 'destroy']);

/* General Nutrition */
Route::middleware(['auth:sanctum', 'check.sub'])->group(function () {
    Route::get('/generalNutrition', [GeneralNutritionController::class, 'index']);
});
Route::post('/generalNutrition', [GeneralNutritionController::class, 'store']);
Route::get('/generalNutrition/{id}', [GeneralNutritionController::class, 'show']);
Route::put('/generalNutrition/{id}', [GeneralNutritionController::class, 'update']);
Route::delete('/generalNutrition/{id}', [GeneralNutritionController::class, 'destroy']);

/* Foods */
Route::apiResource('foods', FoodController::class);
Route::get('/general-nutrition', [GeneralNutritionController::class, 'index']);

/* Dashboard + User Goals */
Route::middleware('auth:sanctum')->get('/dashboard', [DashBoardController::class, 'dashboard']);
Route::middleware('auth:sanctum')->apiResource('user-goals', UserGoalController::class);

/* AI + extra APIs from Ayham */
Route::post('/ai/generate', [AIController::class, 'generateProgram']);////////////////////////
Route::get('/subscriptionForAdmin', [SubscriptionController::class, 'show']);
Route::post('/sync-all', [AIController::class, 'syncAll']);
Route::post('/search-exercises', [AIController::class, 'searchExercises']);
Route::post('/search-foods', [AIController::class, 'searchFoods']);
Route::post('/generate-training-plan', [AIController::class, 'generateTrainingPlan']);
Route::post('/modify-training-plan', [AIController::class, 'modifyTrainingPlan']);
Route::post('/generate-nutrition-plan', [AIController::class, 'generateNutritionPlan']);
Route::post('/modify-nutrition-plan', [AIController::class, 'modifyNutritionPlan']);
Route::post('/analyze-progress', [AIController::class, 'analyzeProgress']);

/* User Programs */
Route::middleware('auth:sanctum')->apiResource('user-programs', UserProgramController::class);

/* Program Versions */
Route::middleware('auth:sanctum')->apiResource('program-versions', ProgramVersionController::class);

/* User Nutrition Plans */
Route::middleware('auth:sanctum')->apiResource('user-nutrition-plans', UserNutritionPlansController::class);

/* Nutrition Versions */
Route::middleware('auth:sanctum')->apiResource('nutrition-versions', NutritionVersionsController::class);

/* Nutrition Food Items */
Route::middleware('auth:sanctum')->apiResource('nutrition-food-items', NutritionFoodItemsController::class);


/* Pending Plans */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/PendingTrainingPlans', [AdminPlanManagementController::class, 'getPendingTrainingPlans']);
    Route::post('/PendingTrainingPlans', [AdminPlanManagementController::class, 'saveEditedTrainingPlan']);
    Route::get('/PendingNutritionPlans', [AdminPlanManagementController::class, 'getPendingNutritionPlans']);
    Route::post('/PendingNutritionPlans', [AdminPlanManagementController::class, 'saveEditedNutritionPlan']);
});
/* AI Request Modifications */
Route::get('/modification-requests/training', [AIRequestModifcationController::class, 'getTrainingModificationRequests']);
Route::get('/modification-requests/nutrition', [AIRequestModifcationController::class, 'getNutritionModificationRequests']);
Route::post('/modification-requests/training/{id}', [AIRequestModifcationController::class, 'approveTraining']);
Route::post('/modification-requests/nutrition/{id}', [AIRequestModifcationController::class, 'approveNutrition']);

Route::middleware('auth:sanctum')->apiResource('modification-requests', ModificationRequestController::class);


Route::post('/modification-requests/training/{id}/approve-final', [AIRequestModifcationController::class, 'approveFinalTraining']);

/* Coach Sessions */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/save-token', [PushTokensController::class, 'store']);

    Route::post('/coach/session', [CoachSessionController::class, 'store']);
    Route::get('/coach/session/{id}', [CoachSessionController::class, 'show']);
    Route::put('/coach/session/{id}', [CoachSessionController::class, 'update']);
    Route::post('/coach/session/{id}/cancel', [CoachSessionController::class, 'cancelCoachSession']);
    Route::delete('/coach/session/{id}', [CoachSessionController::class, 'destroy']);

    Route::get('/sessions', [CoachSessionController::class, 'showSessions']);
    Route::get('/my-sessions', [CoachSessionController::class, 'getMySessions']);
    Route::post('/sessions/{id}/book', [CoachSessionController::class, 'bookSession']);
    Route::delete('/sessions/{id}/cancel', [CoachSessionController::class, 'cancelSession']);

    Route::get('/admin/sessions', [CoachSessionController::class, 'getAllSessionsForAdmin']);
    Route::get('/admin/sessions/{sessionId}', [CoachSessionController::class, 'getSessionDetailsForAdmin']);
    Route::post('/admin/sessions/{id}/cancel', [CoachSessionController::class, 'adminCancelSession']);
    Route::post('/admin/sessions/{id}/restore', [CoachSessionController::class, 'adminRestoreSession']);
});
