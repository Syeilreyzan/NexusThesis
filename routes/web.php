<?php

use App\Http\Controllers\ThesisController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/test', function () {
    return response('Laravel is working!', 200)
        ->header('Content-Type', 'text/plain');
});

// Route::middleware('auth')->group(function () {
//     Route::get('/theses', [ThesisController::class, 'index'])
//         ->name('theses.index');

//     Route::get('/theses/create', [ThesisController::class, 'create'])
//         ->name('theses.create');

//     Route::post('/theses', [ThesisController::class, 'store'])
//         ->name('theses.store');

//     Route::get('/theses/{thesis}', [ThesisController::class, 'show'])
//         ->name('theses.show');
// });

Route::middleware('auth')->group(function () {
    Route::get('/theses', [ThesisController::class, 'index'])
        ->middleware('permission:view theses');

    Route::get('/theses/{thesis}', [ThesisController::class, 'show'])
        ->name('theses.show')
        ->middleware('permission:view theses');

    Route::post('/theses', [ThesisController::class, 'store'])
        ->middleware('permission:submit thesis');

    Route::post('/theses/{thesis}/review', [
        ThesisController::class,
        'review',
    ])->middleware('permission:review thesis');

    Route::get('/theses/{thesis}/download', [
        ThesisController::class,
        'download',
    ])->middleware('permission:download thesis');
});

// Route::middleware(['auth'])
//     ->group(function () {
//         Route::view('dashboard', 'dashboard')->name('dashboard');
//     });

// Route::middleware(['auth', 'role:student'])
//     ->group(function () {
//         Route::view('dashboard', 'dashboard')->name('dashboard');
//     });

Route::middleware(['auth', 'role:student|lecturer|admin'])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
    });

require __DIR__ . '/settings.php';
