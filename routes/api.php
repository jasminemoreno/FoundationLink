<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\FoundationController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\FoundationVerificationController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DonorController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\FoundationPaymentAccountController;
use App\Http\Controllers\Api\CampaignUpdateController;
use App\Http\Controllers\Api\FoundationFollowController;
use App\Http\Controllers\Api\SuperAdminReportController;
use App\Http\Controllers\Api\SettingController;
/*

|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register/donor', [AuthController::class, 'registerDonor']);
Route::post('/foundation-admin/register', [AuthController::class, 'registerFoundation']);
Route::post('/foundations', [FoundationController::class, 'store']);
Route::post('/foundation-verification', [FoundationVerificationController::class, 'store']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:superadmin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);
        Route::get('/foundations', [FoundationController::class, 'index']);
        Route::patch('/foundations/{id}/suspend', [FoundationController::class, 'suspend']);
        Route::patch('/foundations/{id}/unsuspend', [FoundationController::class, 'unsuspend']);
        Route::post('/foundations/{id}/approve', [SuperAdminController::class, 'approveFoundation']);
        Route::post('/foundations/{id}/reject', [SuperAdminController::class, 'rejectFoundation']);
        Route::get('/admin/foundations/pending-count', [SuperAdminController::class, 'pendingApprovalsCount']);
        Route::get('/campaigns', [SuperAdminController::class, 'campaigns']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::patch('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{id}/status', [UserController::class, 'updateStatus']);
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::patch('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
        Route::patch('/payment-methods/{id}/toggle', [PaymentMethodController::class, 'toggle']);
        Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

        // Reports & Settings — no /admin prefix here, already inside prefix('admin')
        Route::get('/reports', [SuperAdminReportController::class, 'index']);
        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
        Route::post('/settings/clear-cache', function () {
            Artisan::call('cache:clear');
            return response()->json(['message' => 'Cache cleared.']);
        });
        Route::post('/settings/reset', function () {
            (new \Database\Seeders\SettingsSeeder)->run();
            return response()->json(['message' => 'Reset done.']);
        });

        Route::get('/notifications', [SuperAdminController::class, 'notifications']);
        Route::get('/notifications/count', [SuperAdminController::class, 'notificationsCount']);
        Route::post('/notifications/seen', [SuperAdminController::class, 'markNotificationsSeen']);
        Route::post('/notifications/{id}/read', [SuperAdminController::class, 'markOneRead']);
        Route::delete('/notifications/{id}', [SuperAdminController::class, 'deleteNotification']);
        Route::delete('/notifications', [SuperAdminController::class, 'clearAllNotifications']);
    });

    /*
    |--------------------------------------------------------------------------
    | FOUNDATION ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:foundation_admin')->prefix('foundation')->group(function () {
        Route::get('/dashboard', [FoundationController::class, 'dashboard']);
        Route::get('/dashboard/stats', [DonationController::class, 'dashboard']);
        Route::get('/campaigns', [CampaignController::class, 'index']);
        Route::post('/campaigns', [CampaignController::class, 'store']);
        Route::post('/campaigns/{id}', [CampaignController::class, 'update']);
        Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy']);
        Route::patch('/campaigns/{id}/pause', [CampaignController::class, 'pause']);
        Route::patch('/campaigns/{id}/resume', [CampaignController::class, 'resume']);
        Route::patch('/campaigns/{id}/complete', [CampaignController::class, 'complete']);
        Route::get('/campaigns/{id}/updates', [CampaignUpdateController::class, 'index']);
        Route::post('/campaigns/{id}/updates', [CampaignUpdateController::class, 'store']);
        Route::post('/campaigns/{id}/updates/{updateId}', [CampaignUpdateController::class, 'update']);
        Route::delete('/campaigns/{id}/updates/{updateId}', [CampaignUpdateController::class, 'destroy']);
        Route::get('/donations', [DonationController::class, 'index']);
        Route::patch('/donations/{id}/status', [DonationController::class, 'updateStatus']);
        Route::get('/donors', [DonationController::class, 'donors']);
        Route::get('/reports', [DonationController::class, 'reports']);
        Route::get('/settings', [FoundationController::class, 'settings']);
        Route::post('/settings', [FoundationController::class, 'updateSettings']);
        Route::get('/payment-accounts', [FoundationPaymentAccountController::class, 'index']);
        Route::post('/payment-accounts', [FoundationPaymentAccountController::class, 'save']);
        Route::delete('/payment-accounts/{paymentMethodId}', [FoundationPaymentAccountController::class, 'destroy']);
        Route::post('/resubmit', [FoundationController::class, 'resubmit']);
        Route::get('/campaigns/{id}/updates/{updateId}/reactors', [CampaignUpdateController::class, 'reactors']);
        Route::get('/profile', [FoundationController::class, 'profile']);
        Route::post('/profile', [FoundationController::class, 'updateProfile']);
        Route::post('/profile/photo', [FoundationController::class, 'uploadPhoto']);
        Route::get('/notifications', [FoundationController::class, 'notifications']);
        Route::get('/notifications/count', [FoundationController::class, 'notificationsCount']);
        Route::post('/notifications/seen', [FoundationController::class, 'markNotificationsSeen']);
        Route::post('/notifications/{id}/read', [FoundationController::class, 'markOneRead']);
        Route::delete('/notifications/{id}', [FoundationController::class, 'deleteNotification']);
        Route::delete('/notifications', [FoundationController::class, 'clearAllNotifications']);
    });


    /*
    |--------------------------------------------------------------------------
    | DONOR
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:donor')->prefix('donor')->group(function () {
        Route::get('/dashboard', [DonorController::class, 'dashboard']);
        Route::get('/foundations', [DonorController::class, 'foundations']);
        Route::get('/foundations/{id}', [DonorController::class, 'foundationDetail']);
        Route::get('/campaigns', [DonorController::class, 'campaigns']);
        Route::get('/campaigns/{id}', [DonorController::class, 'campaignDetail']);
        Route::get('/donor/categories', [DonorController::class, 'categories']);
        Route::get('/campaigns/{id}/updates', [CampaignUpdateController::class, 'donorIndex']);
        Route::post('/donate', [DonorController::class, 'donate']);
        Route::get('/donations', [DonorController::class, 'myDonations']);
        Route::get('/notifications', [DonorController::class, 'notifications']);
        Route::get('/notifications/count', [DonorController::class, 'notificationsCount']);
        Route::post('/notifications/seen', [DonorController::class, 'markNotificationsSeen']);
        Route::get('/profile', [DonorController::class, 'profile']);
        Route::post('/profile', [DonorController::class, 'updateProfile']);
        Route::post('/profile/photo', [DonorController::class, 'uploadPhoto']);
        Route::get('/payment-methods', [PaymentMethodController::class, 'active']);
        Route::post('/foundations/{id}/follow', [FoundationFollowController::class, 'follow']);
        Route::delete('/foundations/{id}/follow', [FoundationFollowController::class, 'unfollow']);
        Route::post('/foundations/{id}/like', [FoundationFollowController::class, 'like']);
        Route::delete('/foundations/{id}/like', [FoundationFollowController::class, 'unlike']);
        Route::get('/followed-foundations', [FoundationFollowController::class, 'myFollowed']);
        Route::get('/feed', [FoundationFollowController::class, 'feed']);
        Route::get('/foundations/{id}/follow-status', [FoundationFollowController::class, 'checkStatus']);
        Route::post('/campaigns/{campaignId}/updates/{updateId}/react', [CampaignUpdateController::class, 'react']);
        Route::post('/notifications/{id}/read', [DonorController::class, 'markOneRead']);
        Route::delete('/notifications/{id}', [DonorController::class, 'deleteNotification']);
        Route::delete('/notifications', [DonorController::class, 'clearAllNotifications']);

    });
});