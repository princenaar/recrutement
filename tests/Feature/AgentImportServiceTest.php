<?php

use App\Models\Agent;
use App\Services\AgentImportService;
use App\Support\ImportResult;

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
});

it('is idempotent — re-importing the same file updates existing agents via matricule', function () {
    $this->service->import($this->fixture);
    $result = $this->service->import($this->fixture);

    expect(Agent::count())->toBe(5);
    expect($result->created)->toBe(0);
    expect($result->updated)->toBe(5);
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
