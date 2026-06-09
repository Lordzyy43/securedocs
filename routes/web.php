<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('csrf-token', fn () => response()->json([
    'token' => csrf_token(),
]))->name('csrf-token');

Route::middleware('guest')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('me', [AuthController::class, 'user'])->name('me');
    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::resource('documents', DocumentController::class)->except(['create', 'edit']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::resource('document-shares', DocumentShareController::class)->except(['create', 'edit']);
    Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show']);
});
