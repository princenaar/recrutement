<?php

namespace App\Support;

class ImportResult
{
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $skipped = 0,
    ) {}

    public function total(): int
    {
        return $this->created + $this->updated + $this->skipped;
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'total' => $this->total(),
        ];
    }
}
