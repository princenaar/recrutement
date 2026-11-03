<?php

use App\Filament\Resources\CommunicationApplications\CommunicationApplicationResource;
use App\Filament\Resources\CommunicationApplications\Pages\ListCommunicationApplications;
use App\Models\CommunicationApplication;
use App\Models\User;
use App\Support\CommunicationCall;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\DatabasePresenceVerifier;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-03 12:00:00', 'Africa/Dakar'));
    Storage::fake('private');
    Storage::fake('public');
});

afterEach(function () {
    $this->travelBack();
});

/** @return array<string, mixed> */
function communicationApplicationPayload(array $overrides = []): array
{
    return array_replace([
        'first_name' => 'Awa',
        'last_name' => 'Diop',
        'email' => 'awa.diop@example.com',
        'phone' => '+221 77 123 45 67',
        'administrative_status' => 'fonctionnaire',
        'current_structure' => 'Direction de la communication',
        'position' => 'infographiste',
        'motivation' => 'Je souhaite mettre mes cinq années d’expérience en conception graphique au service du MSHP.',
        'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
    ], $overrides);
}

it('shows the five documented positions and links the public form from home', function () {
    $url = route('communication-applications.form');

    $this->get('/')->assertOk()->assertSee($url, false);
    $this->get($url)->assertOk()
        ->assertSee('Rejoindre la Cellule de communication')
        ->assertSee('13 novembre 2026')
        ->assertSee('23 h 59')
        ->assertSee(CommunicationCall::maxUploadLabel())
        ->assertSee('Infographiste')
        ->assertSee('Chargé de veille médiatique')
        ->assertSee('Webdesigner')
        ->assertSee('Community manager')
        ->assertSee('Vidéaste-photographe')
        ->assertDontSee('Rédacteur')
        ->assertSee('Bac+2 minimum en design graphique')
        ->assertSee('Au moins 4 ans d’expérience');
});

it('stores a complete public application and a private CV then confirms receipt', function () {
    $this->followingRedirects()->post(route('communication-applications.store'), communicationApplicationPayload([
        'first_name' => ' Awa   Marie ',
        'email' => ' AWA.DIOP@EXAMPLE.COM ',
    ]))->assertOk()->assertSessionHasNoErrors()
        ->assertSee('Votre dossier a bien été reçu')
        ->assertDontSee('id="communication-application-form"', false);

    $application = CommunicationApplication::sole();
    expect($application->first_name)->toBe('Awa Marie')
        ->and($application->email)->toBe('awa.diop@example.com')
        ->and($application->position)->toBe('infographiste')
        ->and($application->motivation)->toContain('cinq années')
        ->and($application->cv_path)->toStartWith('communication-applications/')->toEndWith('/cv.pdf');
    Storage::disk('private')->assertExists($application->cv_path);
    Storage::disk('public')->assertMissing($application->cv_path);
});

it('requires every field and shows French errors', function () {
    $this->from(route('communication-applications.form'))->post(route('communication-applications.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'administrative_status', 'current_structure', 'position', 'motivation', 'cv']);
    $this->withCookie(config('session.cookie'), session()->getId())
        ->get(route('communication-applications.form'))->assertOk()
        ->assertSee('Le champ prénom est obligatoire.')
        ->assertSee('Le champ CV est obligatoire.');

    expect(CommunicationApplication::count())->toBe(0);
    expect(Storage::disk('private')->allFiles())->toBeEmpty();
});

it('rejects an invalid field without storing a dossier', function (array $overrides, string $field) {
    $this->post(route('communication-applications.store'), communicationApplicationPayload($overrides))
        ->assertSessionHasErrors($field);
    expect(CommunicationApplication::count())->toBe(0);
    expect(Storage::disk('private')->allFiles())->toBeEmpty();
})->with([
    'email' => [['email' => 'invalid'], 'email'],
    'array email' => [['email' => ['awa.diop@example.com']], 'email'],
    'unlisted role' => [['position' => 'redacteur'], 'position'],
    'array role' => [['position' => ['infographiste']], 'position'],
    'status' => [['administrative_status' => 'other'], 'administrative_status'],
    'long motivation' => [['motivation' => str_repeat('a', 10001)], 'motivation'],
]);

it('rejects files disguised as PDFs and keeps the form values', function () {
    $fake = UploadedFile::fake()->createWithContent('cv.pdf', 'This is a text file, not a PDF.');
    $file = new UploadedFile($fake->getPathname(), 'cv.pdf', 'application/pdf', null, true);

    $this->from(route('communication-applications.form'))->post(route('communication-applications.store'), communicationApplicationPayload([
        'cv' => $file,
    ]))->assertSessionHasErrors('cv');
    $this->withCookie(config('session.cookie'), session()->getId())
        ->get(route('communication-applications.form'))->assertOk()
        ->assertSee('Le CV doit être au format PDF.')
        ->assertSee('value="Awa"', false)
        ->assertSee('cinq années');

    expect(CommunicationApplication::count())->toBe(0);
});

it('rejects oversized CVs and handles the PHP request size limit', function () {
    config(['recrutement.upload_max_size_kb' => 1]);
    $this->post(route('communication-applications.store'), communicationApplicationPayload())
        ->assertSessionHasErrors('cv');

    $redirectUrl = route('communication-applications.form', ['upload_error' => 'too_large']);
    $this->withServerVariables(['CONTENT_LENGTH' => PHP_INT_MAX])
        ->post(route('communication-applications.store'), [])->assertRedirect($redirectUrl);
    $this->withServerVariables(['CONTENT_LENGTH' => 0])
        ->get($redirectUrl)->assertOk()->assertSee('Le fichier envoyé dépasse la limite autorisée.');

    expect(CommunicationApplication::count())->toBe(0);
});

it('caps the displayed upload limit at both configuration and PHP limits', function () {
    config(['recrutement.upload_max_size_kb' => 10240]);
    expect(CommunicationCall::maxUploadKilobytes())->toBeLessThanOrEqual(5120);
    if (UploadedFile::getMaxFilesize() > 0) {
        expect(CommunicationCall::maxUploadKilobytes() * 1024)->toBeLessThanOrEqual(UploadedFile::getMaxFilesize());
    }
    config(['recrutement.upload_max_size_kb' => 1024]);
    $this->get(route('communication-applications.form'))->assertOk()->assertSee('1 Mo maximum');
});

it('blocks a normalized duplicate without replacing the original dossier', function () {
    $application = CommunicationApplication::factory()->create(['email' => 'awa.diop@example.com', 'position' => 'infographiste']);
    Storage::disk('private')->put($application->cv_path, 'ORIGINAL');

    $this->post(route('communication-applications.store'), communicationApplicationPayload(['email' => ' AWA.DIOP@EXAMPLE.COM ']))
        ->assertSessionHasErrors('email');

    expect(CommunicationApplication::count())->toBe(1)
        ->and($application->fresh()->cv_path)->toBe($application->cv_path)
        ->and(Storage::disk('private')->get($application->cv_path))->toBe('ORIGINAL')
        ->and(Storage::disk('private')->allFiles())->toHaveCount(1);
});

it('handles a competing deposit caught by the database unique constraint', function () {
    $application = CommunicationApplication::factory()->create(['email' => 'awa.diop@example.com', 'position' => 'infographiste']);
    Storage::disk('private')->put($application->cv_path, 'ORIGINAL');

    $verifier = Mockery::mock(DatabasePresenceVerifier::class, [app('db')])->makePartial();
    $verifier->shouldReceive('getCount')->once()->andReturn(0);
    app('validator')->setPresenceVerifier($verifier);

    $this->post(route('communication-applications.store'), communicationApplicationPayload())
        ->assertSessionHasErrors('email');

    expect(CommunicationApplication::count())->toBe(1)
        ->and(Storage::disk('private')->get($application->cv_path))->toBe('ORIGINAL')
        ->and(Storage::disk('private')->allFiles())->toHaveCount(1);
});

it('accepts separate applications for distinct positions with the same email', function () {
    CommunicationApplication::factory()->create(['email' => 'awa.diop@example.com', 'position' => 'infographiste']);
    $this->post(route('communication-applications.store'), communicationApplicationPayload(['position' => 'webdesigner']))
        ->assertSessionHasNoErrors();
    expect(CommunicationApplication::count())->toBe(2);
});

it('rolls back the dossier and removes partial files when storage fails', function (bool $throws) {
    $disk = Storage::disk('private');
    $failingDisk = Mockery::mock($disk);
    $failingDisk->shouldReceive('putFileAs')->once()->andReturnUsing(function ($directory, $file, $name) use ($disk, $throws) {
        $disk->put($directory.'/'.$name, 'PARTIAL');
        if ($throws) {
            throw new RuntimeException('Simulated storage failure');
        }

        return false;
    });
    Storage::shouldReceive('disk')->with('private')->andReturn($failingDisk);

    $this->from(route('communication-applications.form'))
        ->post(route('communication-applications.store'), communicationApplicationPayload())
        ->assertRedirect(route('communication-applications.form'))
        ->assertSessionHasErrors('cv');

    expect(CommunicationApplication::count())->toBe(0)
        ->and($disk->allFiles())->toBeEmpty();
})->with([false, true]);

it('enforces the Dakar deadline on GET and POST', function (string $instant, bool $open) {
    $this->travelTo(CarbonImmutable::parse($instant, 'Africa/Dakar'));
    $page = $this->get(route('communication-applications.form'))->assertOk();
    $response = $this->post(route('communication-applications.store'), communicationApplicationPayload());

    if ($open) {
        $page->assertSee('id="communication-application-form"', false);
        $response->assertSessionHasNoErrors();
        expect(CommunicationApplication::count())->toBe(1);
    } else {
        $page->assertSee('Cet appel à candidatures est clos')->assertDontSee('id="communication-application-form"', false);
        $response->assertSessionHasErrors('closed');
        expect(CommunicationApplication::count())->toBe(0);
        expect(Storage::disk('private')->allFiles())->toBeEmpty();
    }
})->with([
    'last accepted second' => ['2026-11-13 23:59:59', true],
    'exact closing instant' => ['2026-11-14 00:00:00', false],
    'after closing' => ['2026-11-14 00:00:01', false],
]);

it('lets administrators read and search dossiers and filter by position', function () {
    $admin = User::factory()->create();
    $application = CommunicationApplication::factory()->create(['first_name' => 'Awa', 'last_name' => 'Diop', 'position' => 'infographiste', 'motivation' => '<script>alert("unsafe")</script>']);
    $other = CommunicationApplication::factory()->create(['first_name' => 'Moussa', 'last_name' => 'Ndiaye', 'position' => 'webdesigner']);

    $this->get(CommunicationApplicationResource::getUrl('index'))->assertRedirect(route('filament.admin.auth.login'));
    $this->actingAs($admin)->get(CommunicationApplicationResource::getUrl('view', ['record' => $application]))
        ->assertOk()->assertSee('Awa Diop')->assertSee('Ouvrir le CV')->assertSee('Télécharger le CV')
        ->assertSee('<iframe', false)->assertSee($application->motivation)
        ->assertDontSee($application->motivation, false);

    Livewire::actingAs($admin)->test(ListCommunicationApplications::class)
        ->assertCanSeeTableRecords([$application, $other])
        ->searchTable($application->email)
        ->assertCanSeeTableRecords([$application])->assertCanNotSeeTableRecords([$other])
        ->searchTable('Moussa')
        ->assertCanSeeTableRecords([$other])->assertCanNotSeeTableRecords([$application])
        ->searchTable('')
        ->filterTable('position', 'infographiste')
        ->assertCanSeeTableRecords([$application])->assertCanNotSeeTableRecords([$other]);

    expect(CommunicationApplicationResource::canCreate())->toBeFalse()
        ->and(CommunicationApplicationResource::canEdit($application))->toBeFalse()
        ->and(CommunicationApplicationResource::canDelete($application))->toBeFalse()
        ->and(CommunicationApplicationResource::canDeleteAny())->toBeFalse();
    $this->get(CommunicationApplicationResource::getUrl('view', ['record' => $application]).'/edit')->assertNotFound();
});

it('serves CV previews and downloads only to authenticated administrators', function () {
    $application = CommunicationApplication::factory()->create();
    $content = "%PDF-1.4\nPrivate CV";
    Storage::disk('private')->put($application->cv_path, $content);
    $url = route('admin.files.communication-application.cv', ['application' => $application]);

    $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
    $this->get($url.'?download=1')->assertRedirect(route('filament.admin.auth.login'));
    $this->get('/storage/'.$application->cv_path)->assertForbidden();

    $this->actingAs(User::factory()->create());
    $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->streamedContent())->toBe($content);
    $this->get($url.'?download=1')->assertOk()->assertDownload();

    Storage::disk('private')->delete($application->cv_path);
    $this->get($url)->assertNotFound();
});
