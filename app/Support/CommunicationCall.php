<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

class CommunicationCall
{
    public static function closesAt(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-14 00:00:00', 'Africa/Dakar');
    }

    public static function isOpen(): bool
    {
        return CarbonImmutable::now('Africa/Dakar')->lessThan(self::closesAt());
    }

    /** @return array<string, string> */
    public static function administrativeStatuses(): array
    {
        return [
            'fonctionnaire' => 'Fonctionnaire',
            'contractuel' => 'Contractuel',
        ];
    }

    public static function maxUploadKilobytes(): int
    {
        $configuredLimit = max(1, min(5120, (int) config('recrutement.upload_max_size_kb')));
        $phpLimit = (int) UploadedFile::getMaxFilesize();

        return $phpLimit > 0
            ? max(1, min($configuredLimit, (int) floor($phpLimit / 1024)))
            : $configuredLimit;
    }

    public static function maxUploadLabel(): string
    {
        $kilobytes = self::maxUploadKilobytes();

        return $kilobytes >= 1024
            ? rtrim(rtrim(number_format($kilobytes / 1024, 2, ',', ''), '0'), ',').' Mo'
            : $kilobytes.' Ko';
    }

    /** @return array<string, string> */
    public static function positionOptions(): array
    {
        return array_column(self::positions(), 'title', 'key');
    }

    /**
     * @return array<string, array{key: string, title: string, mission: string, responsibilities: list<string>, training: string, experience: string, skills: string|null}>
     */
    public static function positions(): array
    {
        return [
            'infographiste' => [
                'key' => 'infographiste',
                'title' => 'Infographiste',
                'mission' => 'Concevoir les supports visuels imprimés et numériques et produire des infographies dans le respect de l’identité graphique du MSHP.',
                'responsibilities' => [
                    'Concevoir des supports graphiques et décliner les messages validés pour les différents canaux.',
                    'Structurer les informations pour produire des infographies lisibles et cohérentes.',
                    'Soumettre les créations à validation, intégrer les corrections et archiver les fichiers sources et versions finales.',
                ],
                'training' => 'Bac+2 minimum en design graphique, infographie ou communication digitale.',
                'experience' => 'Au moins 3 ans d’expérience en conception graphique pour les supports imprimés et numériques.',
                'skills' => 'Maîtrise d’Adobe Suite, Canva Pro ou outils équivalents et de l’application d’une charte graphique.',
            ],
            'veille-mediatique' => [
                'key' => 'veille-mediatique',
                'title' => 'Chargé de veille médiatique',
                'mission' => 'Assurer la veille médiatique, l’analyse de l’information, la diffusion des contenus validés et le suivi des performances des canaux de communication.',
                'responsibilities' => [
                    'Collecter, vérifier et analyser les informations issues de la presse et des plateformes numériques.',
                    'Contribuer aux revues de presse, notes de veille et alertes sur les sujets sensibles.',
                    'Participer à la diffusion des contenus validés et renseigner les indicateurs de portée et de performance.',
                ],
                'training' => 'Bac+3 en journalisme, communication ou sciences politiques.',
                'experience' => 'Au moins 3 ans d’expérience pertinente en veille médiatique, analyse de l’information ou communication digitale.',
                'skills' => 'Capacité à rechercher et vérifier les sources, synthétiser les informations, utiliser les outils de veille et analyser des indicateurs.',
            ],
            'webdesigner' => [
                'key' => 'webdesigner',
                'title' => 'Webdesigner',
                'mission' => 'Administrer les sites web du ministère, garantir leur actualisation, leur qualité technique et le suivi de leurs performances.',
                'responsibilities' => [
                    'Mettre à jour les sites et intégrer les textes, images et vidéos préalablement validés.',
                    'Surveiller la disponibilité, les sauvegardes, la sécurité et la maintenance autorisée des plateformes.',
                    'Optimiser l’ergonomie et le référencement, analyser le trafic et rendre compte des incidents.',
                ],
                'training' => 'Bac+3 en développement web, webdesign ou informatique.',
                'experience' => 'Au moins 4 ans d’expérience pertinente en administration de sites web, gestion de CMS, intégration web et référencement.',
                'skills' => null,
            ],
            'community-manager' => [
                'key' => 'community-manager',
                'title' => 'Community manager',
                'mission' => 'Animer les réseaux sociaux institutionnels, publier les contenus validés, assurer la modération et analyser les résultats des publications.',
                'responsibilities' => [
                    'Préparer et programmer les publications conformément au calendrier et aux validations de la Cellule.',
                    'Suivre les commentaires et messages, assurer une modération adaptée et orienter les sollicitations vers les services compétents.',
                    'Analyser la portée, les interactions et l’engagement des publics et contribuer aux bilans des plateformes.',
                ],
                'training' => 'Bac+3 en communication digitale ou marketing, ou expérience pertinente.',
                'experience' => 'Au moins 3 ans d’expérience pertinente en gestion et animation de réseaux sociaux institutionnels ou professionnels.',
                'skills' => 'Maîtrise des plateformes sociales, des outils de programmation, de la modération et des statistiques de performance.',
            ],
            'videaste-photographe' => [
                'key' => 'videaste-photographe',
                'title' => 'Vidéaste-photographe',
                'mission' => 'Capturer, produire et valoriser les contenus photographiques et vidéo du ministère, assurer les reportages et organiser les archives audiovisuelles.',
                'responsibilities' => [
                    'Préparer le matériel de prise de vue et de prise de son et assurer les reportages photo et vidéo.',
                    'Sélectionner les séquences, réaliser les montages et intégrer les corrections après validation.',
                    'Livrer les productions dans les délais et classer les fichiers et versions validées.',
                ],
                'training' => 'Bac+2 en audiovisuel ou expérience équivalente.',
                'experience' => 'Au moins 3 ans d’expérience pertinente en prise de vue photographique, captation vidéo, prise de son et montage audiovisuel.',
                'skills' => 'Maîtrise de la prise de vue, du son et d’un logiciel de montage (Premiere Pro, DaVinci ou équivalent).',
            ],
        ];
    }
}
