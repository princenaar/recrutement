<?php

use App\Filament\Exports\DistrictChiefAcademicProfileExporter;
use App\Models\DistrictChiefAcademicProfile;
use App\Models\DistrictChiefDiploma;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('recrutement.storage_disk'));
});

function districtChiefUpload(string $name, int $kilobytes = 100, string $mime = 'application/pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, $mime);
}

it('renders the public academic profile form', function () {
    $this->get(route('district-chief-academic-profiles.form'))
        ->assertOk()
        ->assertViewIs('district-chief-academic-profiles.form')
        ->assertSee('Informations académiques')
        ->assertSee('Date de prise de service')
        ->assertSee("Certificat d'inscription", false);
});

it('stores a profile with one diploma and an optional training certificate', function () {
    $this->post(route('district-chief-academic-profiles.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'service_start_date' => '2024-03-15',
        'training_certificate' => districtChiefUpload('certificat.pdf'),
        'diplomas' => [
            [
                'name' => 'Doctorat en médecine',
                'obtained_year' => 2018,
                'scan' => districtChiefUpload('doctorat.pdf'),
            ],
        ],
    ])->assertRedirect(route('district-chief-academic-profiles.form'));

    $profile = DistrictChiefAcademicProfile::with('diplomas')->first();

    expect($profile)->not->toBeNull();
    expect($profile->first_name)->toBe('Awa');
    expect($profile->last_name)->toBe('DIOP');
    expect($profile->service_start_date->format('Y-m-d'))->toBe('2024-03-15');
    expect($profile->training_certificate_path)
        ->toStartWith("district-chief-academic-profiles/{$profile->id}/certificate/")
        ->toEndWith('.pdf');
    expect($profile->diplomas)->toHaveCount(1);
    expect($profile->diplomas->first()->name)->toBe('Doctorat en médecine');

    Storage::disk(config('recrutement.storage_disk'))
        ->assertExists($profile->training_certificate_path)
        ->assertExists($profile->diplomas->first()->scan_path);
});

it('stores multiple diplomas without a training certificate', function () {
    $this->post(route('district-chief-academic-profiles.store'), [
        'first_name' => 'Mamadou',
        'last_name' => 'SARR',
        'service_start_date' => '2022-01-10',
        'diplomas' => [
            [
                'name' => 'Doctorat en médecine',
                'obtained_year' => 2015,
                'scan' => districtChiefUpload('doctorat.jpg', 100, 'image/jpeg'),
            ],
            [
                'name' => 'Master Santé publique',
                'obtained_year' => 2020,
                'scan' => districtChiefUpload('master.png', 100, 'image/png'),
            ],
        ],
    ])->assertRedirect(route('district-chief-academic-profiles.form'));

    $profile = DistrictChiefAcademicProfile::with('diplomas')->firstOrFail();

    expect($profile->training_certificate_path)->toBeNull();
    expect($profile->diplomas)->toHaveCount(2);
    expect($profile->diplomas->pluck('scan_path')->all()[0])->toEndWith('.jpg');
    expect($profile->diplomas->pluck('scan_path')->all()[1])->toEndWith('.png');
});

it('requires at least one diploma', function () {
    $this->post(route('district-chief-academic-profiles.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'service_start_date' => '2024-03-15',
    ])->assertSessionHasErrors('diplomas');

    expect(DistrictChiefAcademicProfile::count())->toBe(0);
    expect(DistrictChiefDiploma::count())->toBe(0);
});

it('rejects unsupported files and oversized certificates', function () {
    $this->post(route('district-chief-academic-profiles.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'service_start_date' => '2024-03-15',
        'diplomas' => [
            [
                'name' => 'Doctorat en médecine',
                'obtained_year' => 2018,
                'scan' => districtChiefUpload('scan.txt', 100, 'text/plain'),
            ],
        ],
    ])->assertSessionHasErrors('diplomas.0.scan');

    $maxKb = (int) config('recrutement.upload_max_size_kb');

    $this->post(route('district-chief-academic-profiles.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'service_start_date' => '2024-03-15',
        'training_certificate' => districtChiefUpload('certificat.pdf', $maxKb + 1),
        'diplomas' => [
            [
                'name' => 'Doctorat en médecine',
                'obtained_year' => 2018,
                'scan' => districtChiefUpload('doctorat.pdf'),
            ],
        ],
    ])->assertSessionHasErrors('training_certificate');

    expect(DistrictChiefAcademicProfile::count())->toBe(0);
});

it('renders the admin list and detail pages', function () {
    $admin = User::factory()->create();
    $profile = DistrictChiefAcademicProfile::factory()
        ->withTrainingCertificate()
        ->create([
            'first_name' => 'Awa',
            'last_name' => 'DIOP',
        ]);
    DistrictChiefDiploma::factory()->create([
        'district_chief_academic_profile_id' => $profile->id,
        'name' => 'Doctorat en médecine',
        'obtained_year' => 2018,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.district-chief-academic-profiles.index'))
        ->assertOk()
        ->assertSee('Awa')
        ->assertSee('DIOP');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.district-chief-academic-profiles.view', $profile))
        ->assertOk()
        ->assertSee('Doctorat en médecine')
        ->assertSee('Télécharger le certificat');
});

it('protects and serves district chief academic files through admin routes', function () {
    $profile = DistrictChiefAcademicProfile::factory()->create([
        'training_certificate_path' => 'district-chief-academic-profiles/1/certificate/certificat.pdf',
    ]);
    $diploma = DistrictChiefDiploma::factory()->create([
        'district_chief_academic_profile_id' => $profile->id,
        'name' => 'Doctorat en médecine',
        'scan_path' => 'district-chief-academic-profiles/1/diplomas/doctorat.jpg',
    ]);

    Storage::disk(config('recrutement.storage_disk'))->put($profile->training_certificate_path, 'CERTIFICATE');
    Storage::disk(config('recrutement.storage_disk'))->put($diploma->scan_path, 'DIPLOMA');

    $this->get(route('admin.files.district-chief-academic-profile.certificate', ['profile' => $profile]))
        ->assertRedirect(route('filament.admin.auth.login'));

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.files.district-chief-academic-profile.certificate', ['profile' => $profile]))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($admin)
        ->get(route('admin.files.district-chief-diploma.scan', ['diploma' => $diploma]))
        ->assertOk()
        ->assertDownload();
});

it('defines the export columns for academic profiles', function () {
    $columns = collect(DistrictChiefAcademicProfileExporter::getColumns())
        ->map(fn ($column): string => $column->getName())
        ->all();

    expect($columns)->toContain(
        'first_name',
        'last_name',
        'service_start_date',
        'diplomas_count',
        'diplomas_detail',
        'training_certificate_present',
    );
});
