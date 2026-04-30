<?php

use App\Models\Agent;
use App\Models\Submission;
use App\Services\AgentImportService;
use App\Support\ImportResult;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->service = app(AgentImportService::class);
    $this->fixture = base_path('tests/fixtures/agents_sample.xlsx');
});

it('imports all agent rows from the iHRIS Excel fixture', function () {
    $result = $this->service->import($this->fixture);

    expect(Agent::count())->toBe(5);
    expect($result)->toBeInstanceOf(ImportResult::class);
    expect($result->created)->toBe(5);
    expect($result->updated)->toBe(0);
    expect($result->skipped)->toBe(0);
    expect(Agent::whereNull('import_name')->count())->toBe(5);
});

it('is idempotent — re-importing the same file updates existing agents via matricule', function () {
    $this->service->import($this->fixture);
    $result = $this->service->import($this->fixture);

    expect(Agent::count())->toBe(5);
    expect($result->created)->toBe(0);
    expect($result->updated)->toBe(5);
});

it('stores the import name for an iHRIS import and updates it on re-import', function () {
    $this->service->import($this->fixture, 'iHRIS avril 2026');

    expect(Agent::where('import_name', 'iHRIS avril 2026')->count())->toBe(5);

    $this->service->import($this->fixture, 'iHRIS mai 2026');

    expect(Agent::where('import_name', 'iHRIS avril 2026')->count())->toBe(0);
    expect(Agent::where('import_name', 'iHRIS mai 2026')->count())->toBe(5);
});

it('can filter submissions by the agent import name', function () {
    $agentFromFirstImport = Agent::factory()->create(['import_name' => 'Import A']);
    $agentFromSecondImport = Agent::factory()->create(['import_name' => 'Import B']);
    Submission::factory()->create(['agent_id' => $agentFromFirstImport->id]);
    Submission::factory()->create(['agent_id' => $agentFromSecondImport->id]);

    $filtered = Submission::query()
        ->whereHas('agent', fn ($query) => $query->where('import_name', 'Import A'))
        ->pluck('agent_id')
        ->all();

    expect($filtered)->toBe([$agentFromFirstImport->id]);
});

it('converts Excel numeric dates to Carbon dates', function () {
    $this->service->import($this->fixture);

    $seck = Agent::where('matricule', '609216H')->first();

    expect($seck)->not->toBeNull();
    // Excel serial 26484 = 1972-07-04 (PhpSpreadsheet respects Excel's 1900 leap-year bug)
    expect($seck->birth_date->format('Y-m-d'))->toBe('1972-07-04');
    // Excel serial 37636 = 2003-01-15
    expect($seck->position_start_date->format('Y-m-d'))->toBe('2003-01-15');
});

it('accepts rows with null email without raising', function () {
    $this->service->import($this->fixture);

    $withoutEmail = Agent::whereNull('email')->count();
    $withEmail = Agent::whereNotNull('email')->count();

    expect($withoutEmail)->toBe(3);
    expect($withEmail)->toBe(2);
    expect(Agent::where('email', 'dioufmike@yahoo.fr')->exists())->toBeTrue();
});

it('maps iHRIS column names to Agent attributes correctly', function () {
    $this->service->import($this->fixture);

    $seck = Agent::where('matricule', '609216H')->first();

    expect($seck->first_name)->toBe('Malamine');
    expect($seck->last_name)->toBe('SECK');
    expect($seck->gender)->toBe('M');
    expect($seck->nationality)->toBe('Senegal');
    expect($seck->category)->toBe('Ingénieur en informatique');
    expect($seck->current_position)->toBe('Ingénieur en informatique');
    expect($seck->agent_status)->toBe('Fonctionnaire');
    expect($seck->contract_type)->toBe('Fonction Publique');
    expect($seck->employer)->toBe('Fonction Publique');
    expect($seck->marital_status)->toBe('Marié (e)');
    expect($seck->structure)->toBe('DQSH (Direction de la Qualité de la Sécurité et de l\'Hygiène hospitalière)');
    expect($seck->district)->toBe('Dakar Ministère et Services Rattachés');
    expect($seck->region)->toBe('Dakar');
    expect($seck->phone)->toBe('775356289');
    expect($seck->ihris_imported_at)->not->toBeNull();
});

it('imports the WPForms gestionnaire des données Excel format', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        [
            'Nom',
            'Date de naissance',
            'Lieu de naissance',
            'Téléphone',
            'Email',
            'Diplôme',
            'Niveau Diplôme',
            'Filiére/Domaine',
            'Année diplôme',
            "Numéro d'identification CNI ou PASSEPORT",
            'Lieu de résidence',
            'Piéce jointe diplôme',
            'Curriculum vitae',
            'Choix région affectation',
        ],
        [
            'Aminata FALL',
            '1994-10-03',
            'Louga',
            '771234567',
            'aminata@example.test',
            'Informatique',
            'Licence',
            'Gestion des données',
            '2020',
            '1234567890123',
            'Rufisque, Dakar',
            'https://example.test/diplome.pdf',
            'https://example.test/cv.pdf',
            'Kédougou',
        ],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'wpforms-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $result = $this->service->import($path, 'WPForms gestionnaires 2026');

    expect($result->created)->toBe(1);

    $agent = Agent::where('matricule', '1234567890123')->first();
    expect($agent)->not->toBeNull();
    expect($agent->import_source)->toBe('wpforms_gestionnaire_donnees');
    expect($agent->import_name)->toBe('WPForms gestionnaires 2026');
    expect($agent->first_name)->toBe('');
    expect($agent->last_name)->toBe('Aminata FALL');
    expect($agent->birth_date->format('Y-m-d'))->toBe('1994-10-03');
    expect($agent->email)->toBe('aminata@example.test');
    expect($agent->phone)->toBe('771234567');
    expect($agent->category)->toBe('Informatique');
    expect($agent->current_position)->toBe('Licence');
    expect($agent->structure)->toBe('Rufisque, Dakar');
    expect($agent->region)->toBe('Kédougou');
    expect($agent->source_payload['Filiére/Domaine'])->toBe('Gestion des données');
    expect($agent->source_payload['Curriculum vitae'])->toBe('https://example.test/cv.pdf');
});
