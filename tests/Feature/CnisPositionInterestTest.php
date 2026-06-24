<?php

use App\Models\CnisPositionInterest;
use App\Models\User;
use App\Support\CnisPositions;

it('renders the public CNIS position interest form with all positions and descriptions', function () {
    $response = $this->get(route('cnis.positions.form'));

    $response->assertOk()
        ->assertViewIs('cnis.positions')
        ->assertSee('Choix des postes CNIS')
        ->assertSee('Voir la description')
        ->assertSee('Support de Niveau 1 et 2', false);

    foreach (CnisPositions::all() as $position) {
        $response->assertSee($position['title']);
    }
});

it('stores an interested response with one choice', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'interest_status' => 'interested',
        'first_choice' => 'data_analyst',
    ])->assertRedirect(route('cnis.positions.form'));

    $interest = CnisPositionInterest::first();

    expect($interest)->not->toBeNull();
    expect($interest->first_name)->toBe('Awa');
    expect($interest->last_name)->toBe('DIOP');
    expect($interest->not_interested)->toBeFalse();
    expect($interest->first_choice)->toBe('data_analyst');
    expect($interest->second_choice)->toBeNull();
    expect($interest->third_choice)->toBeNull();
});

it('stores an interested response with three ordered choices', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Mamadou',
        'last_name' => 'SARR',
        'interest_status' => 'interested',
        'first_choice' => 'technical_operations',
        'second_choice' => 'bi_full_stack_developer',
        'third_choice' => 'senior_data_engineer',
    ])->assertRedirect(route('cnis.positions.form'));

    $interest = CnisPositionInterest::first();

    expect($interest->not_interested)->toBeFalse();
    expect($interest->first_choice)->toBe('technical_operations');
    expect($interest->second_choice)->toBe('bi_full_stack_developer');
    expect($interest->third_choice)->toBe('senior_data_engineer');
});

it('stores a not interested response without position choices', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Fatou',
        'last_name' => 'NDIAYE',
        'interest_status' => 'not_interested',
    ])->assertRedirect(route('cnis.positions.form'));

    $interest = CnisPositionInterest::first();

    expect($interest->not_interested)->toBeTrue();
    expect($interest->first_choice)->toBeNull();
    expect($interest->second_choice)->toBeNull();
    expect($interest->third_choice)->toBeNull();
});

it('requires identity and an explicit interest status', function () {
    $this->post(route('cnis.positions.store'), [])
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'interest_status',
        ]);

    expect(CnisPositionInterest::count())->toBe(0);
});

it('requires a first choice when the candidate is interested', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'interest_status' => 'interested',
    ])->assertSessionHasErrors('first_choice');

    expect(CnisPositionInterest::count())->toBe(0);
});

it('rejects duplicate ranked choices', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'interest_status' => 'interested',
        'first_choice' => 'data_analyst',
        'second_choice' => 'data_analyst',
    ])->assertSessionHasErrors('second_choice');

    expect(CnisPositionInterest::count())->toBe(0);
});

it('rejects unknown position keys', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'interest_status' => 'interested',
        'first_choice' => 'unknown_position',
    ])->assertSessionHasErrors('first_choice');

    expect(CnisPositionInterest::count())->toBe(0);
});

it('rejects position choices when the candidate is not interested', function () {
    $this->post(route('cnis.positions.store'), [
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'interest_status' => 'not_interested',
        'first_choice' => 'data_analyst',
    ])->assertSessionHasErrors('first_choice');

    expect(CnisPositionInterest::count())->toBe(0);
});

it('renders the CNIS admin resource with readable position labels', function () {
    $admin = User::factory()->create();
    CnisPositionInterest::factory()->create([
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'not_interested' => false,
        'first_choice' => 'data_analyst',
        'second_choice' => 'program_lead',
        'third_choice' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.cnis-position-interests.index'))
        ->assertOk()
        ->assertSee('Awa')
        ->assertSee('DIOP')
        ->assertSee(CnisPositions::title('data_analyst'))
        ->assertSee(CnisPositions::title('program_lead'));
});
