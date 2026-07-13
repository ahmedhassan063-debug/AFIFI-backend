<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\ContactMessageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\InventoryMovementController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ReturnRequestController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->middleware('throttle:auth-public')->name('auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::prefix('catalog')->name('catalog.')->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('product-variants', [ProductVariantController::class, 'index'])->name('product-variants.index');
    Route::get('product-variants/{productVariant}', [ProductVariantController::class, 'show'])->name('product-variants.show');
});

Route::prefix('cms')->name('cms.')->group(function (): void {
    Route::get('homepage', [CmsController::class, 'homepage'])->name('homepage');
    Route::get('banners', [CmsController::class, 'banners'])->name('banners');
    Route::get('about', [CmsController::class, 'about'])->name('about');
    Route::get('faq', [CmsController::class, 'faq'])->name('faq');
    Route::get('policies/{slug}', [CmsController::class, 'policy'])->name('policies.show');
});

Route::get('settings/public', [SettingsController::class, 'publicSettings'])->name('settings.public');
Route::get('campaigns/active', [CampaignController::class, 'active'])->name('campaigns.active');

/*
|--------------------------------------------------------------------------
| Authenticated customer routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::put('password', [AuthController::class, 'changePassword'])
            ->middleware('throttle:auth-sensitive')
            ->name('password.update');
    });

    Route::prefix('cart')->name('cart.')->group(function (): void {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('items', [CartController::class, 'addItem'])->name('items.store');
        Route::put('items/{cartItem}', [CartController::class, 'updateItem'])->name('items.update');
        Route::delete('items/{cartItem}', [CartController::class, 'removeItem'])->name('items.destroy');
        Route::delete('/', [CartController::class, 'clear'])->name('clear');
    });

    Route::prefix('wishlist')->name('wishlist.')->group(function (): void {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('items', [WishlistController::class, 'store'])->name('items.store');
        Route::delete('items/{wishlistItem}', [WishlistController::class, 'destroy'])->name('items.destroy');
    });

    Route::apiResource('addresses', AddressController::class)->except(['create', 'edit']);
    Route::put('addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

    Route::post('checkout', [CheckoutController::class, 'checkout'])->name('checkout');

    Route::prefix('orders')->name('orders.')->group(function (): void {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('{order}', [OrderController::class, 'show'])->name('show');
        Route::put('{order}/payment-reference', [OrderController::class, 'submitPaymentReference'])->name('payment-reference.update');
        Route::post('{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('returns')->name('returns.')->group(function (): void {
        Route::get('/', [ReturnRequestController::class, 'index'])->name('index');
        Route::post('/', [ReturnRequestController::class, 'store'])->name('store');
        Route::get('{returnRequest}', [ReturnRequestController::class, 'show'])->name('show');
    });
});

/*
|--------------------------------------------------------------------------
| Admin / staff routes
|--------------------------------------------------------------------------
|
| The permission middleware names below are placeholders for the approved
| Spatie permission names and require middleware aliases before route use.
|
*/

Route::middleware(['auth:sanctum'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'summary'])
        ->middleware('permission:reports.view')
        ->name('dashboard.summary');

    Route::middleware('permission:products.view')->group(function (): void {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('product-variants', [ProductVariantController::class, 'index'])->name('product-variants.index');
        Route::get('product-variants/{productVariant}', [ProductVariantController::class, 'show'])->name('product-variants.show');
    });

    Route::middleware('permission:products.create')->group(function (): void {
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::post('product-variants', [ProductVariantController::class, 'store'])->name('product-variants.store');
    });

    Route::middleware('permission:products.update')->group(function (): void {
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.patch');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.patch');
        Route::put('product-variants/{productVariant}', [ProductVariantController::class, 'update'])->name('product-variants.update');
        Route::patch('product-variants/{productVariant}', [ProductVariantController::class, 'update'])->name('product-variants.patch');
    });

    Route::middleware('permission:products.delete')->group(function (): void {
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('product-variants/{productVariant}', [ProductVariantController::class, 'destroy'])->name('product-variants.destroy');
    });

    Route::middleware('permission:coupons.manage')->group(function (): void {
        Route::apiResource('coupons', CouponController::class)->except(['create', 'edit']);
        Route::post('coupons/{coupon}/calculate-discount', [CouponController::class, 'calculateDiscount'])->name('coupons.calculate-discount');
    });

    Route::middleware('permission:campaigns.manage')->group(function (): void {
        Route::apiResource('campaigns', CampaignController::class)->except(['create', 'edit']);
    });

    Route::middleware('permission:settings.manage')->group(function (): void {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('settings/{setting}', [SettingsController::class, 'show'])->name('settings.show');
        Route::put('settings/{setting}', [SettingsController::class, 'update'])->name('settings.update');
        Route::patch('settings/{setting}', [SettingsController::class, 'update'])->name('settings.patch');
        Route::post('admin-preferences', [SettingsController::class, 'storeAdminPreference'])->name('admin-preferences.store');
        Route::put('admin-preferences/{key}', [SettingsController::class, 'updateAdminPreference'])->name('admin-preferences.update');
    });

    Route::middleware('permission:cms.manage')->group(function (): void {
        Route::apiResource('media', MediaController::class)->except(['create', 'edit']);
    });

    Route::middleware('permission:orders.view')->group(function (): void {
        Route::get('orders', [OrderController::class, 'adminIndex'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'adminShow'])->name('orders.show');
    });

    Route::middleware('permission:users.view')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('permission:roles.view')->group(function (): void {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');
    });

    Route::middleware('permission:roles.manage')->group(function (): void {
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
    });

    Route::middleware('permission:orders.update')->group(function (): void {
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
        Route::patch('returns/{returnRequest}/status', [ReturnRequestController::class, 'updateStatus'])->name('returns.status.update');
    });

    Route::middleware('permission:payments.view')->group(function (): void {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    });

    Route::middleware('permission:inventory.view')->group(function (): void {
        Route::get('inventory-movements', [InventoryMovementController::class, 'index'])->name('inventory-movements.index');
    });

    Route::middleware('permission:inventory.update')->group(function (): void {
        Route::post('product-variants/{productVariant}/inventory-adjustments', [InventoryMovementController::class, 'store'])->name('product-variants.inventory-adjustments.store');
    });

    Route::middleware('permission:contact.view')->group(function (): void {
        Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
    });

    Route::middleware('permission:contact.manage')->group(function (): void {
        Route::patch('contact-messages/{contactMessage}/status', [ContactMessageController::class, 'updateStatus'])->name('contact-messages.status.update');
        Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
    });

    Route::middleware('permission:payments.update')->group(function (): void {
        Route::patch('payments/{payment}/paid', [PaymentController::class, 'markAsPaid'])->name('payments.paid');
        Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus'])->name('payments.status.update');
    });

    Route::middleware('permission:payments.refund')->group(function (): void {
        Route::post('payments/{payment}/refunds', [PaymentController::class, 'refund'])->name('payments.refunds.store');
        Route::patch('payments/{payment}/refunds/{refund}/status', [PaymentController::class, 'updateRefundStatus'])->name('payments.refunds.status.update');
    });
});
