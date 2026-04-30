<?php

namespace App\Services;

use App\Models\Agent;
use App\Support\ImportResult;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AgentImportService
{
    /**
     * Map iHRIS Excel column headers (after trim) to Agent attribute names.
     * Column name "Email personnnel" contains a typo in the source file (3 n's) — preserved.
     */
    private const COLUMN_MAP = [
        "Numéro d'identification" => 'matricule',
        'Prénom' => 'first_name',
        'Nom' => 'last_name',
        'Nationalité' => 'nationality',
        'Sexe' => 'gender',
        'Date de naissance' => 'birth_date',
        'Email personnnel' => 'email',
        'Catégorie socio-professionnelle' => 'category',
        'Poste occupé (Fonction)' => 'current_position',
        "Date d'Occupation du Poste" => 'position_start_date',
        'Service' => 'service',
        "Statut de l'agent" => 'agent_status',
        'Type de contrat' => 'contract_type',
        'Employeur' => 'employer',
        "Date d'entrée dans le systeme de santé" => 'entry_date',
        'Situation matrimoniale' => 'marital_status',
        'Numéro de téléphone mobile' => 'phone',
        'Nom de la Structure' => 'structure',
        'Districts/Hôpitaux' => 'district',
        'Région' => 'region',
    ];

    private const DATE_FIELDS = ['birth_date', 'position_start_date', 'entry_date'];

    private const WPFORMS_COLUMN_MAP = [
        'Nom' => 'last_name',
        'Date de naissance' => 'birth_date',
        'Téléphone' => 'phone',
        'Email' => 'email',
        'Diplôme' => 'category',
        'Niveau Diplôme' => 'current_position',
        "Numéro d'identification CNI ou PASSEPORT" => 'matricule',
        'Lieu de résidence' => 'structure',
        'Choix région affectation' => 'region',
    ];

    private const WPFORMS_PAYLOAD_COLUMNS = [
        'Lieu de naissance',
        'Diplôme',
        'Niveau Diplôme',
        'Filiére/Domaine',
        'Année diplôme',
        'Lieu de résidence',
        'Piéce jointe diplôme',
        'Curriculum vitae',
        'Choix région affectation',
    ];

    public function import(string $filePath, ?string $importName = null): ImportResult
    {
        $sheets = Excel::toArray([], $filePath);
        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            return new ImportResult;
        }

        $headers = $rows[0];
        $isWpForms = $this->isWpFormsExport($headers);
        $headerIndex = $isWpForms
            ? $this->buildHeaderIndex($headers, self::WPFORMS_COLUMN_MAP)
            : $this->buildHeaderIndex($headers, self::COLUMN_MAP);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            $matricule = $this->stringifyValue($this->readColumn($row, $headerIndex, 'matricule'));

            if ($matricule === null || $matricule === '') {
                $skipped++;

                continue;
            }

            $attributes = $isWpForms
                ? $this->mapWpFormsRow($row, $headerIndex, $headers)
                : $this->mapRow($row, $headerIndex);
            $attributes['ihris_imported_at'] = now();
            $attributes['import_name'] = $this->stringifyValue($importName);

            $agent = Agent::updateOrCreate(['matricule' => $matricule], $attributes);

            $agent->wasRecentlyCreated ? $created++ : $updated++;
        }

        return new ImportResult($created, $updated, $skipped);
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  array<string, string>  $columnMap
     * @return array<string, int> field name → column index
     */
    private function buildHeaderIndex(array $headers, array $columnMap): array
    {
        $index = [];

        foreach ($headers as $i => $label) {
            $trimmed = trim((string) $label);
            if (isset($columnMap[$trimmed])) {
                $index[$columnMap[$trimmed]] = $i;
            }
        }

        return $index;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $headerIndex
     * @return array<string, mixed>
     */
    private function mapRow(array $row, array $headerIndex): array
    {
        $attributes = [];

        foreach ($headerIndex as $field => $col) {
            if ($field === 'matricule') {
                continue;
            }

            $raw = $row[$col] ?? null;

            if (in_array($field, self::DATE_FIELDS, true)) {
                $attributes[$field] = $this->parseExcelDate($raw);
            } else {
                $attributes[$field] = $this->stringifyValue($raw);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $headerIndex
     * @param  array<int, mixed>  $headers
     * @return array<string, mixed>
     */
    private function mapWpFormsRow(array $row, array $headerIndex, array $headers): array
    {
        $attributes = [
            'first_name' => '',
            'import_source' => 'wpforms_gestionnaire_donnees',
            'source_payload' => $this->buildSourcePayload($row, $headers),
        ];

        foreach ($headerIndex as $field => $col) {
            if ($field === 'matricule') {
                continue;
            }

            $raw = $row[$col] ?? null;
            $attributes[$field] = $field === 'birth_date'
                ? $this->parseExcelDate($raw)
                : $this->stringifyValue($raw);
        }

        return $attributes;
    }

    /**
     * @param  array<int, mixed>  $headers
     */
    private function isWpFormsExport(array $headers): bool
    {
        $labels = array_map(fn ($label): string => trim((string) $label), $headers);

        return in_array("Numéro d'identification CNI ou PASSEPORT", $labels, true)
            && in_array('Niveau Diplôme', $labels, true)
            && in_array('Choix région affectation', $labels, true);
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<int, mixed>  $headers
     * @return array<string, mixed>
     */
    private function buildSourcePayload(array $row, array $headers): array
    {
        $payload = [];

        foreach ($headers as $col => $label) {
            $label = trim((string) $label);

            if (! in_array($label, self::WPFORMS_PAYLOAD_COLUMNS, true)) {
                continue;
            }

            $payload[$label] = $this->stringifyValue($row[$col] ?? null);
        }

        return $payload;
    }

    private function readColumn(array $row, array $headerIndex, string $field): mixed
    {
        $col = $headerIndex[$field] ?? null;

        return $col === null ? null : ($row[$col] ?? null);
    }

    private function stringifyValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function parseExcelDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
