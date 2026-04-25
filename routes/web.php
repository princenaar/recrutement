<?php

use App\Http\Controllers\Admin\FileDownloadController;
use App\Http\Controllers\CandidatePortalController;
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
    Route::post('/{token}/diploma', [CandidatePortalController::class, 'addDiploma'])->name('diploma.add');
    Route::delete('/{token}/diploma/{diploma}', [CandidatePortalController::class, 'removeDiploma'])->name('diploma.remove');
});
