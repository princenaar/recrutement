<?php

use App\Http\Controllers\Admin\FileDownloadController;
use App\Http\Controllers\CandidatePortalController;
use App\Http\Controllers\CnisPositionInterestController;
use App\Http\Controllers\DistrictChiefAcademicProfileController;
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
        Route::get('district-chief-academic-profile/{profile}/certificate', [FileDownloadController::class, 'districtChiefTrainingCertificate'])->name('district-chief-academic-profile.certificate');
        Route::get('district-chief-diploma/{diploma}/scan', [FileDownloadController::class, 'districtChiefDiplomaScan'])->name('district-chief-diploma.scan');
    });

Route::prefix('candidature')->name('candidate.')->group(function () {
    Route::get('/{token}', [CandidatePortalController::class, 'show'])->name('portal');
    Route::post('/{token}', [CandidatePortalController::class, 'save'])->name('save');
});

Route::prefix('cnis')->name('cnis.')->group(function () {
    Route::get('/postes', [CnisPositionInterestController::class, 'show'])->name('positions.form');
    Route::post('/postes', [CnisPositionInterestController::class, 'store'])->name('positions.store');
});

Route::prefix('medecins-chefs-district')->name('district-chief-academic-profiles.')->group(function () {
    Route::get('/academique', [DistrictChiefAcademicProfileController::class, 'show'])->name('form');
    Route::post('/academique', [DistrictChiefAcademicProfileController::class, 'store'])->name('store');
});
