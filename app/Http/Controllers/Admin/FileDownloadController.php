<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diploma;
use App\Models\DistrictChiefAcademicProfile;
use App\Models\DistrictChiefDiploma;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function cv(Submission $submission): StreamedResponse
    {
        abort_if($submission->cv_path === null, 404);

        $disk = Storage::disk(config('recrutement.storage_disk'));
        abort_unless($disk->exists($submission->cv_path), 404);

        $filename = sprintf(
            'cv_%s_%s.pdf',
            $submission->agent->matricule ?? $submission->agent_id,
            $submission->id,
        );

        return $disk->download($submission->cv_path, $filename);
    }

    public function diploma(Diploma $diploma): StreamedResponse
    {
        $disk = Storage::disk(config('recrutement.storage_disk'));
        abort_unless($disk->exists($diploma->file_path), 404);

        $filename = sprintf('diplome_%d_%s.pdf', $diploma->id, str($diploma->title)->slug());

        return $disk->download($diploma->file_path, $filename);
    }

    public function districtChiefTrainingCertificate(DistrictChiefAcademicProfile $profile): StreamedResponse
    {
        abort_if($profile->training_certificate_path === null, 404);

        $disk = Storage::disk(config('recrutement.storage_disk'));
        abort_unless($disk->exists($profile->training_certificate_path), 404);

        $extension = pathinfo($profile->training_certificate_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = sprintf('certificat_inscription_%d_%s.%s', $profile->id, str($profile->full_name)->slug(), $extension);

        return $disk->download($profile->training_certificate_path, $filename);
    }

    public function districtChiefDiplomaScan(DistrictChiefDiploma $diploma): StreamedResponse
    {
        $disk = Storage::disk(config('recrutement.storage_disk'));
        abort_unless($disk->exists($diploma->scan_path), 404);

        $extension = pathinfo($diploma->scan_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = sprintf('diplome_mcd_%d_%s.%s', $diploma->id, str($diploma->name)->slug(), $extension);

        return $disk->download($diploma->scan_path, $filename);
    }
}
