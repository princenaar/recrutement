<?php

use App\Http\Controllers\Admin\FileDownloadController;
use App\Http\Controllers\CandidatePortalController;
use App\Http\Controllers\CnisPositionInterestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])
    ->prefix('admin/files')
    ->name('admin.files.')
    ->group(function () {
        Route::get('submission/{submission}/cv', [FileDownloadController::class, 'cv'])->name('cv');
        Route::get('diploma/{diploma}', [FileDownloadController::class, 'diploma'])->name('diploma');
    });

Route::prefix('candidature')->name('candidate.')->group(function () {
    Route::get('/{token}', [CandidatePortalController::class, 'show'])->name('portal');
    Route::post('/{token}', [CandidatePortalController::class, 'save'])->name('save');
});

Route::prefix('cnis')->name('cnis.')->group(function () {
    Route::get('/postes', [CnisPositionInterestController::class, 'show'])->name('positions.form');
    Route::post('/postes', [CnisPositionInterestController::class, 'store'])->name('positions.store');
});
