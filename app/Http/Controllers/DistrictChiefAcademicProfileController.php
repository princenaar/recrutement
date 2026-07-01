<?php

namespace App\Http\Controllers;

use App\Models\DistrictChiefAcademicProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class DistrictChiefAcademicProfileController extends Controller
{
    public function show(): View
    {
        return view('district-chief-academic-profiles.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('recrutement.upload_max_size_kb');
        $currentYear = (int) date('Y');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'service_start_date' => ['required', 'date'],
            'training_certificate' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', "max:{$maxKb}"],
            'diplomas' => ['required', 'array', 'min:1'],
            'diplomas.*.name' => ['required', 'string', 'max:255'],
            'diplomas.*.obtained_year' => ['required', 'integer', 'min:1900', "max:{$currentYear}"],
            'diplomas.*.scan' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', "max:{$maxKb}"],
        ]);

        $disk = Storage::disk($this->diskName());
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $validated, &$storedPaths): void {
                $profile = DistrictChiefAcademicProfile::create([
                    'first_name' => str($validated['first_name'])->squish()->toString(),
                    'last_name' => str($validated['last_name'])->squish()->toString(),
                    'service_start_date' => $validated['service_start_date'],
                ]);

                $certificate = $request->file('training_certificate');

                if ($certificate instanceof UploadedFile) {
                    $path = $this->storeFile($profile->id, 'certificate', $certificate);
                    $storedPaths[] = $path;
                    $profile->update(['training_certificate_path' => $path]);
                }

                foreach ($validated['diplomas'] as $index => $diploma) {
                    $scan = $request->file("diplomas.{$index}.scan");

                    if (! $scan instanceof UploadedFile) {
                        throw new RuntimeException('Le scan du diplôme est obligatoire.');
                    }

                    $path = $this->storeFile($profile->id, 'diplomas', $scan);
                    $storedPaths[] = $path;

                    $profile->diplomas()->create([
                        'name' => str($diploma['name'])->squish()->toString(),
                        'obtained_year' => $diploma['obtained_year'],
                        'scan_path' => $path,
                    ]);
                }
            });
        } catch (Throwable $throwable) {
            $disk->delete($storedPaths);

            throw $throwable;
        }

        return redirect()
            ->route('district-chief-academic-profiles.form')
            ->with('status', 'Vos informations académiques ont bien été enregistrées.');
    }

    private function storeFile(int $profileId, string $directory, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs(
            "district-chief-academic-profiles/{$profileId}/{$directory}",
            Str::uuid().'.'.$extension,
            ['disk' => $this->diskName()],
        );

        if ($path === false) {
            throw new RuntimeException('Le fichier n’a pas pu être enregistré.');
        }

        return $path;
    }

    private function diskName(): string
    {
        return (string) config('recrutement.storage_disk');
    }
}
