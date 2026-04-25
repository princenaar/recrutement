<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidSubmissionFileException extends RuntimeException
{
    public static function notPdf(string $filename): self
    {
        return new self("Le fichier « {$filename} » doit être un PDF.");
    }

    public static function tooLarge(string $filename, int $maxKb): self
    {
        return new self("Le fichier « {$filename} » dépasse la taille maximale autorisée ({$maxKb} Ko).");
    }
}
