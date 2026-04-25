<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token d'invitation
    |--------------------------------------------------------------------------
    |
    | Durée de validité (en jours) du lien unique envoyé à l'agent pour
    | accéder au portail candidat.
    |
    */

    'invitation_token_validity_days' => (int) env('INVITATION_TOKEN_VALIDITY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Upload de fichiers
    |--------------------------------------------------------------------------
    |
    | Taille maximale (en kilo-octets) pour les CV et diplômes soumis par
    | les candidats. Utilisé dans les règles de validation Laravel.
    |
    */

    'upload_max_size_kb' => (int) env('UPLOAD_MAX_SIZE_KB', 5120),

    /*
    |--------------------------------------------------------------------------
    | Disque de stockage des documents candidats
    |--------------------------------------------------------------------------
    |
    | Les CV et diplômes sont strictement privés. Voir config/filesystems.php.
    |
    */

    'storage_disk' => env('RECRUTEMENT_STORAGE_DISK', 'private'),

];
