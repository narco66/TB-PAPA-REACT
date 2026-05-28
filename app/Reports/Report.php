<?php

namespace App\Reports;

/**
 * Classe abstraite de tous les rapports institutionnels TB-PAPA-CEEAC.
 *
 * Chaque rapport :
 *   - déclare son identifiant unique (key)
 *   - liste ses filtres disponibles
 *   - calcule ses données (dataset)
 *   - pointe vers son template Blade
 *
 * Le PdfGeneratorService prend en charge le rendering, le QR Code,
 * l'archivage et la traçabilité.
 */
abstract class Report
{
    /** Catégories normalisées. */
    public const CAT_STRATEGIQUE = 'strategique';

    public const CAT_BUDGET = 'budget';

    public const CAT_PERFORMANCE = 'performance';

    public const CAT_RBM = 'rbm';

    public const CAT_GOUVERNANCE = 'gouvernance';

    public const CAT_AUDIT = 'audit';

    public const CAT_ANALYTIQUE = 'analytique';

    public const CAT_OPERATIONNEL = 'operationnel';

    public const CAT_PTF = 'ptf';

    public const CAT_GED = 'ged';

    public const CATEGORIES = [
        self::CAT_STRATEGIQUE => 'Stratégique',
        self::CAT_BUDGET => 'Budgétaire',
        self::CAT_PERFORMANCE => 'Performance',
        self::CAT_RBM => 'RBM / GAR',
        self::CAT_GOUVERNANCE => 'Gouvernance',
        self::CAT_AUDIT => 'Audit',
        self::CAT_ANALYTIQUE => 'Analytique',
        self::CAT_OPERATIONNEL => 'Opérationnel',
        self::CAT_PTF => 'Partenaires (PTF)',
        self::CAT_GED => 'GED',
    ];

    /** Identifiant unique du rapport (utilisé en URL et DB). */
    abstract public function key(): string;

    /** Titre humain affiché à l'utilisateur. */
    abstract public function titre(): string;

    /** Description fonctionnelle pour la liste des rapports. */
    abstract public function description(): string;

    /** Catégorie (voir CAT_*). */
    abstract public function categorie(): string;

    /** Données contextuelles passées au template Blade. */
    abstract public function donnees(array $filtres = []): array;

    /** Template Blade (ex: 'reports.strategique.papa'). */
    abstract public function template(): string;

    /**
     * Filtres disponibles pour ce rapport. Chaque filtre = ['key', 'label', 'type', 'options'?].
     * Types : 'select', 'date', 'text', 'number', 'multi_select', 'bool'.
     */
    public function filtres(): array
    {
        return [];
    }

    /** Orientation : portrait | landscape. */
    public function orientation(): string
    {
        return 'portrait';
    }

    /** Format papier : A4 | A3 | letter. */
    public function format(): string
    {
        return 'A4';
    }

    /** Permission requise pour générer ce rapport. */
    public function permission(): string
    {
        return 'generate_reports';
    }

    /** Icône Lucide affichée dans l'UI (ex: 'FileText', 'BarChart3'). */
    public function icone(): string
    {
        return 'FileText';
    }
}
