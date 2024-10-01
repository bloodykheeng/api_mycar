<?php

use App\Http\Controllers\API\CarBrandController;
use App\Http\Controllers\API\CarCartController;
use App\Http\Controllers\API\CarController;
use App\Http\Controllers\API\CarInspectionFieldCategoryController;
use App\Http\Controllers\API\CarInspectionReportController;
use App\Http\Controllers\API\CarInspectorController;
use App\Http\Controllers\API\CarReviewController;
use App\Http\Controllers\API\CarTypeController;
use App\Http\Controllers\API\CarWashFeeController;
use App\Http\Controllers\API\CarWashOrderController;
use App\Http\Controllers\API\CodeCheckController;
use App\Http\Controllers\API\DashboardSliderPhotoController;
use App\Http\Controllers\API\EventNotificationController;
use App\Http\Controllers\API\EventSubscriberController;
use App\Http\Controllers\API\FaqController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\GarageController;
use App\Http\Controllers\API\GarageReviewController;
use App\Http\Controllers\API\InspectionFieldController;
use App\Http\Controllers\API\MotorThirdPartyController;
use App\Http\Controllers\API\OfficeController;
use App\Http\Controllers\API\OfficeRentController;
use App\Http\Controllers\API\ParkingController;
use App\Http\Controllers\API\ParkingFeeController;
use App\Http\Controllers\API\SparePartCartController;
use App\Http\Controllers\API\SparePartController;
use App\Http\Controllers\API\SparePartReviewController;
use App\Http\Controllers\API\SparePartsTransactionController;
use App\Http\Controllers\API\SparePartTypeController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserPermissionsController;
use App\Http\Controllers\API\UserReviewController;
use App\Http\Controllers\API\UserRolesController;
use App\Http\Controllers\API\VendorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);

//check if user is still logged in
// Route::get('/user', [AuthController::class, 'checkLoginStatus']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'checkLoginStatus']);

// Add the route for getBySlug method in routes file
Route::get('user-by-slug/{slug}', [UserController::class, 'getBySlug']);

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/third-party-login-auth', [AuthController::class, 'thirdPartyLoginAuthentication'])->name('thirdPartyLoginAuthentication');
Route::post('/third-party-register-auth', [AuthController::class, 'thirdPartyRegisterAuthentication'])->name('thirdPartyRegisterAuthentication');

Route::post('forgot-password', [PasswordResetController::class, 'forgetPassword']);
Route::get('/reset-password', [PasswordResetController::class, 'handleresetPasswordLoad']);
Route::post('/reset-password', [PasswordResetController::class, 'handlestoringNewPassword']);

//forgot password/reset (Application)
Route::post('password/email', [ForgotPasswordController::class, '__invoke']);
Route::post('password/code/check', [CodeCheckController::class, '__invoke']);
Route::post('password/reset/{id}', [CodeCheckController::class, 'resetPassword']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

//========================= puclic routes ===================
// Public routes for viewing dashboard slider photos
Route::resource('dashboard-slider-photos', DashboardSliderPhotoController::class)->only(['index', 'show']);
// car type
Route::resource('car-types', CarTypeController::class)->only(['index', 'show']);

// car routes
Route::apiResource('cars', CarController::class)->only(['index', 'show']);
Route::get('/car/{slug}', [CarController::class, 'getBySlug']);

// ======================  Spare Part Types =====================
Route::resource('spare-part-types', SparePartTypeController::class)->only(['index', 'show']);
Route::resource('spare-parts', SparePartController::class)->only(['index', 'show']);
Route::get('/spare-part/{slug}', [SparePartController::class, 'getBySlug']);

Route::get('/spare-part-reviews/{id}', [SparePartReviewController::class, 'get_spare_part_reviews']);
Route::resource('spare-part-reviews', SparePartReviewController::class)->only(['index', 'show']);

// ==============================Garage======================================
Route::resource('garages', GarageController::class)->only(['index', 'show']);

Route::get('garage-by-slug/{slug}', [GarageController::class, 'getBySlug']);

// =======================  Garage Reviews ======================================
Route::resource('garage-reviews', GarageReviewController::class)->only(['index', 'show']);
Route::get('/garage-reviews/{id}', [GarageReviewController::class, 'get_garage_reviews']);

Route::resource('motor-third-parties', MotorThirdPartyController::class)->only(['index', 'show']);

Route::apiResource('vendors', VendorController::class)->only(['index', 'show']);

//================== car inspectors users ==========================
Route::get('inspector-users', [UserController::class, 'getCarInspectors']);

//=============== spare parts transactions ========================
Route::apiResource('spare-parts-transactions', SparePartsTransactionController::class)->only(['store']);

//======================== events subscribing =====================
Route::apiResource('event-subscribers', EventSubscriberController::class)->only(['store']);

//======================== spare part review =====================
Route::resource('spare-reviews', SparePartReviewController::class)->only(['index', 'show']);

//========================== user Reviews ================================
Route::resource('user-reviews', UserReviewController::class)->only(['index', 'show']);
Route::get('user-reviews/user/{id}', [UserReviewController::class, 'get_user_reviews']);

//=========================== Car Reviews ===========================
Route::resource('car-reviews', CarReviewController::class)->only(['index', 'show']);
Route::get('car-reviews/car/{id}', [CarReviewController::class, 'get_car_reviews']);

// ======================== faqs ===================================

Route::apiResource('faqs', FaqController::class)->only(['index', 'show']);

//=============================== private routes ==================================
Route::group(
    ['middleware' => ['auth:sanctum']],
    function () {
        // Vendor routes
        Route::apiResource('vendors', VendorController::class)->except(['index', 'show']);

        // car type
        Route::resource('car-types', CarTypeController::class)->except(['index', 'show']);

        // carBrand routes
        Route::apiResource('car-brands', CarBrandController::class);

        // =================================  car routes ===============================
        Route::apiResource('cars', CarController::class)->except(['index', 'show']);

        //=========================== Car Reviews ===========================
        Route::resource('car-reviews', CarReviewController::class)->except(['index', 'show']);

        // ======================  Spare Part Types =====================
        Route::resource('spare-part-types', SparePartTypeController::class)->except(['index', 'show']);

        // ====================== Spare Parts ===========================
        Route::resource('spare-parts', SparePartController::class)->except(['index', 'show']);

        Route::resource('spare-reviews', SparePartReviewController::class)->except(['index', 'show']);

        // ====================== Motor Third Party =====================
        Route::resource('motor-third-parties', MotorThirdPartyController::class)->except(['index', 'show']);

        // ====================== Motor Third Party =====================
        Route::resource('garages', GarageController::class)->except(['index', 'show']);

        // ====================== Garage Review =========================
        Route::resource('garage-reviews', GarageReviewController::class)->except(['index', 'show']);

        // ====================== Office Fees ===========================
        Route::resource('office-fees', OfficeController::class);
        Route::resource('office-rents', OfficeRentController::class);

        //================ Dashboard Slider ======================
        Route::resource('dashboard-slider-photos', DashboardSliderPhotoController::class)
            ->except(['index', 'show']);

        //======================= Shopping Carts ========================
        Route::apiResource('car-carts', CarCartController::class);
        Route::post('sync-car-carts', [CarCartController::class, 'syncCarCarts']);

        Route::apiResource('spare-part-carts', SparePartCartController::class);
        Route::post('sync-spare-carts', [SparePartCartController::class, 'syncSparePartCarts']);

        //=============== spare parts transactions ========================
        Route::apiResource('spare-parts-transactions', SparePartsTransactionController::class)->except(['store']);

        // bulk delet spart part cart items
        Route::post('spare-cart-bulk-delete', [SparePartCartController::class, 'bulkDelete']);

        //=============== spare parts transactions ========================
        Route::apiResource('spare-parts-transactions', SparePartsTransactionController::class);
        Route::get('my-transactions', [SparePartsTransactionController::class, 'get_spare_part_transactions']);

        // ParkingFee routes
        Route::apiResource('parking-fees', ParkingFeeController::class);

        //=================== parking =========================
        Route::resource('parking', ParkingController::class);

        // ====================== Car Wash Fees ===========================
        Route::resource('car-wash-fees', CarWashFeeController::class);

        // ====================== Car Wash Orders ===========================
        Route::resource('car-wash-orders', CarWashOrderController::class);

        //=========================== Car Inspection =======================
        Route::resource('inspection-field-categories', CarInspectionFieldCategoryController::class);
        Route::apiResource('inspection-fields', InspectionFieldController::class);

        Route::apiResource('car-inspection-reports', CarInspectionReportController::class);

        Route::apiResource('car-inspectors', CarInspectorController::class);

        Route::get('categorized-inspection-fields', [CarInspectionFieldCategoryController::class, 'getCategoryWithFields']);

        Route::post('update-car-report-status', [CarInspectionReportController::class, 'updateCarInspectionReportStatus']);

        //======================== events subscribing =====================
        Route::apiResource('event-subscribers', EventSubscriberController::class)->except(['store']);
        Route::apiResource('event-notifications', EventNotificationController::class);

        //================================= faqs ================================

        Route::apiResource('faqs', FaqController::class)->except(['index', 'show']);

        //======================== User Management =================================
        Route::Resource('users', UserController::class);

        Route::post('/profile-photo', [UserController::class, 'update_profile_photo']);
        Route::post('/profile-update/{id}', [UserController::class, 'profile_update']);

        //============ update user status =====================
        Route::post('update-user-status', [UserController::class, 'updateUserStatus']);

        //========================== user Reviews ================================
        Route::resource('user-reviews', UserReviewController::class)->except(['index', 'show']);

        //Roles AND Permisions
        Route::get('/roles', [UserRolesController::class, 'getAssignableRoles']);

        Route::Resource('users-roles', UserRolesController::class);
        Route::Post('users-roles-addPermissionsToRole', [UserRolesController::class, 'addPermissionsToRole']);
        Route::Post('users-roles-deletePermissionFromRole', [UserRolesController::class, 'deletePermissionFromRole']);

        Route::Resource('users-permissions', UserPermissionsController::class);
        Route::get('users-permissions-permissionNotInCurrentRole/{id}', [UserPermissionsController::class, 'permissionNotInCurrentRole']);
    }
);