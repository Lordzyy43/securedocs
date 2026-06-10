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

Route::get('csrf-token', fn() => response()->json([
    'token' => csrf_token(),
]))->name('csrf-token');

Route::middleware('guest')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('me', [AuthController::class, 'user'])->name('me');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::put('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('change-password', [AuthController::class, 'changePassword'])->name('profile.password');


    Route::middleware('role:admin')->group(function () {
        Route::get('admin/users', [UserController::class, 'adminIndex'])->name('admin.users.index');
        Route::post('admin/users', [UserController::class, 'adminStore'])->name('admin.users.store');
        Route::put('admin/users/{user}', [UserController::class, 'adminUpdate'])->name('admin.users.update');
        Route::put('admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::get('roles', fn() => response()->json(App\Models\Role::all(['id', 'name'])))->name('roles.index');
    });


    Route::resource('documents', DocumentController::class)->except(['create', 'edit']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');

    Route::resource('document-shares', DocumentShareController::class)->except(['create', 'edit']);
    Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show']);
});

