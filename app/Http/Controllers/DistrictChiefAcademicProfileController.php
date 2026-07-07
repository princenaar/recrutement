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
    public function show(Request $request): View
    {
        $uploadLimits = $this->uploadLimits();

        return view('district-chief-academic-profiles.form', [
            'uploadError' => $request->query('upload_error') === 'too_large'
                ? $this->postTooLargeMessage($uploadLimits)
                : null,
            'uploadLimits' => $uploadLimits,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uploadLimits = $this->uploadLimits();
        $maxKb = $uploadLimits['max_file_kb'];
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
        ], $this->validationMessages($uploadLimits));

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

    /**
     * @return array{
     *     configured_max_file_kb: int,
     *     max_file_kb: int,
     *     max_file_bytes: int,
     *     max_file_label: string,
     *     max_total_upload_bytes: int|null,
     *     max_total_upload_label: string|null,
     *     compression_url: string,
     *     allowed_extensions: array<int, string>,
     *     allowed_mime_types: array<int, string>
     * }
     */
    private function uploadLimits(): array
    {
        $configuredMaxFileKb = max(1, (int) config('recrutement.upload_max_size_kb'));
        $configuredMaxFileBytes = $configuredMaxFileKb * 1024;
        $phpMaxFileBytes = (int) UploadedFile::getMaxFilesize();
        $postMaxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        if ($phpMaxFileBytes <= 0) {
            $phpMaxFileBytes = $configuredMaxFileBytes;
        }

        $maxFileBytes = min($configuredMaxFileBytes, $phpMaxFileBytes);
        $maxFileKb = max(1, (int) floor($maxFileBytes / 1024));
        $maxFileBytes = $maxFileKb * 1024;

        return [
            'configured_max_file_kb' => $configuredMaxFileKb,
            'max_file_kb' => $maxFileKb,
            'max_file_bytes' => $maxFileBytes,
            'max_file_label' => $this->formatBytes($maxFileBytes),
            'max_total_upload_bytes' => $postMaxBytes > 0 ? $postMaxBytes : null,
            'max_total_upload_label' => $postMaxBytes > 0 ? $this->formatBytes($postMaxBytes) : null,
            'compression_url' => 'https://www.ilovepdf.com/compress_pdf',
            'allowed_extensions' => ['pdf', 'png', 'jpg', 'jpeg'],
            'allowed_mime_types' => ['application/pdf', 'image/png', 'image/jpeg'],
        ];
    }

    /**
     * @param  array{max_file_label: string, compression_url: string}  $uploadLimits
     * @return array<string, string>
     */
    private function validationMessages(array $uploadLimits): array
    {
        $fileTooLargeMessage = "Le fichier ne doit pas dépasser {$uploadLimits['max_file_label']}. Si votre PDF est trop volumineux, compressez-le avec iLovePDF : {$uploadLimits['compression_url']}.";

        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'service_start_date.required' => 'La date de prise de service est obligatoire.',
            'service_start_date.date' => 'La date de prise de service doit être une date valide.',
            'training_certificate.file' => 'Le certificat doit être un fichier valide.',
            'training_certificate.mimes' => 'Le certificat doit être au format PDF, PNG, JPG ou JPEG.',
            'training_certificate.max' => $fileTooLargeMessage,
            'training_certificate.uploaded' => $fileTooLargeMessage,
            'diplomas.required' => 'Veuillez ajouter au moins un diplôme.',
            'diplomas.array' => 'Veuillez ajouter au moins un diplôme.',
            'diplomas.min' => 'Veuillez ajouter au moins un diplôme.',
            'diplomas.*.name.required' => 'Le nom du diplôme est obligatoire.',
            'diplomas.*.name.max' => 'Le nom du diplôme ne doit pas dépasser 255 caractères.',
            'diplomas.*.obtained_year.required' => "L'année d'obtention est obligatoire.",
            'diplomas.*.obtained_year.integer' => "L'année d'obtention doit être une année valide.",
            'diplomas.*.obtained_year.min' => "L'année d'obtention doit être supérieure ou égale à 1900.",
            'diplomas.*.obtained_year.max' => "L'année d'obtention ne peut pas être dans le futur.",
            'diplomas.*.scan.required' => 'Le scan du diplôme est obligatoire.',
            'diplomas.*.scan.file' => 'Le scan du diplôme doit être un fichier valide.',
            'diplomas.*.scan.mimes' => 'Le scan du diplôme doit être au format PDF, PNG, JPG ou JPEG.',
            'diplomas.*.scan.max' => $fileTooLargeMessage,
            'diplomas.*.scan.uploaded' => $fileTooLargeMessage,
        ];
    }

    /**
     * @param  array{max_total_upload_label: string|null, compression_url: string}  $uploadLimits
     */
    private function postTooLargeMessage(array $uploadLimits): string
    {
        $limit = $uploadLimits['max_total_upload_label'] !== null
            ? ' de '.$uploadLimits['max_total_upload_label']
            : '';

        return "La taille totale des fichiers sélectionnés dépasse la limite autorisée{$limit}. Retirez un fichier ou compressez vos PDF avec iLovePDF : {$uploadLimits['compression_url']}.";
    }

    private function iniSizeToBytes(string $size): int
    {
        $size = trim($size);

        if ($size === '') {
            return 0;
        }

        $unit = strtolower(substr($size, -1));
        $value = (float) $size;

        return match ($unit) {
            'g' => (int) ($value * 1024 * 1024 * 1024),
            'm' => (int) ($value * 1024 * 1024),
            'k' => (int) ($value * 1024),
            default => (int) $value,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return $this->formatNumber($bytes / 1024 / 1024).' Mo';
        }

        return $this->formatNumber($bytes / 1024).' Ko';
    }

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 1, ',', ' ');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
