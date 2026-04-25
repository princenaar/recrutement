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

    public function import(string $filePath): ImportResult
    {
        $sheets = Excel::toArray([], $filePath);
        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            return new ImportResult;
        }

        $headerIndex = $this->buildHeaderIndex($rows[0]);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            $matricule = $this->stringifyValue($this->readColumn($row, $headerIndex, 'matricule'));

            if ($matricule === null || $matricule === '') {
                $skipped++;

                continue;
            }

            $attributes = $this->mapRow($row, $headerIndex);
            $attributes['ihris_imported_at'] = now();

            $agent = Agent::updateOrCreate(['matricule' => $matricule], $attributes);

            $agent->wasRecentlyCreated ? $created++ : $updated++;
        }

        return new ImportResult($created, $updated, $skipped);
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<string, int> field name → column index
     */
    private function buildHeaderIndex(array $headers): array
    {
        $index = [];

        foreach ($headers as $i => $label) {
            $trimmed = trim((string) $label);
            if (isset(self::COLUMN_MAP[$trimmed])) {
                $index[self::COLUMN_MAP[$trimmed]] = $i;
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
