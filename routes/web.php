<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Richness\RichPayments\Http\Controllers\Admin\AttemptController;
use Richness\RichPayments\Http\Controllers\Admin\AuditLogController;
use Richness\RichPayments\Http\Controllers\Admin\GatewayController;
use Richness\RichPayments\Http\Controllers\CheckoutController;
use Richness\RichPayments\Http\Controllers\WebhookController;

Route::prefix(config('rich-payments.route_prefix', 'payments'))
    ->middleware(config('rich-payments.middleware.checkout', ['web']))
    ->group(function (): void {
        Route::get('/methods', [CheckoutController::class, 'methods'])->name('rich-payments.methods');
        Route::post('/start', [CheckoutController::class, 'start'])->name('rich-payments.start');
        Route::get('/pending', [CheckoutController::class, 'pending'])->name('rich-payments.pending');
        Route::get('/status/{reference}', [CheckoutController::class, 'status'])->name('rich-payments.status');
        Route::get('/success', [CheckoutController::class, 'success'])->name('rich-payments.success');
        Route::get('/failed', [CheckoutController::class, 'failed'])->name('rich-payments.failed');
    });

Route::post(config('rich-payments.route_prefix', 'payments').'/{gateway}/webhook', WebhookController::class)
    ->middleware(config('rich-payments.middleware.webhook', ['api']))
    ->name('rich-payments.webhook');

Route::get(config('rich-payments.route_prefix', 'payments').'/{gateway}/callback', [WebhookController::class, 'response'])
    ->middleware(config('rich-payments.middleware.checkout', ['web']))
    ->name('rich-payments.response');

Route::prefix(config('rich-payments.admin_route_prefix', 'admin/rich-payments'))
    ->middleware(config('rich-payments.middleware.admin', ['web', 'auth']))
    ->group(function (): void {
        Route::get('/gateways', [GatewayController::class, 'index'])->name('rich-payments.admin.gateways.index');
        Route::get('/gateways/{gateway}', [GatewayController::class, 'edit'])->name('rich-payments.admin.gateways.edit');
        Route::put('/gateways/{gateway}', [GatewayController::class, 'update'])->name('rich-payments.admin.gateways.update');
        Route::post('/gateways/{gateway}/test-connection', [GatewayController::class, 'testConnection'])->name('rich-payments.admin.gateways.test-connection');
        Route::get('/attempts', [AttemptController::class, 'index'])->name('rich-payments.admin.attempts.index');
        Route::post('/attempts/{attempt}/inquire', [AttemptController::class, 'inquire'])->name('rich-payments.admin.attempts.inquire');
        Route::post('/attempts/{attempt}/refund', [AttemptController::class, 'refund'])->name('rich-payments.admin.attempts.refund');
        Route::post('/attempts/{attempt}/void', [AttemptController::class, 'void'])->name('rich-payments.admin.attempts.void');
        Route::post('/attempts/{attempt}/capture', [AttemptController::class, 'capture'])->name('rich-payments.admin.attempts.capture');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('rich-payments.admin.audit-logs.index');
    });
