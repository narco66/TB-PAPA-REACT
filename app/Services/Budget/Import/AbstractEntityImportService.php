<?php

namespace App\Services\Budget\Import;

use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetImportErreur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Base abstraite des services d'import RBM par feuille.
 *
 * Chaque service concret (Axe, Produit, Sous-Produit, Activité, Tâche, Indicateur)
 * étend cette classe et fournit :
 *   - le mapping des colonnes canoniques
 *   - la résolution des références parentes
 *   - la création/mise à jour de l'entité
 *
 * Toute l'orchestration (lecture feuille, transaction, persistance erreurs)
 * est mutualisée ici.
 */
abstract class AbstractEntityImportService
{
    protected array $erreurs = [];

    protected array $stats = [
        'lues' => 0,
        'valides' => 0,
        'creees' => 0,
        'mises_a_jour' => 0,
        'erreurs' => 0,
        'doublons' => 0,
    ];

    /** Type canonique de la feuille (axes, produits, etc.). */
    abstract public function typeDonnees(): string;

    /** Liste des colonnes obligatoires (clés canoniques). */
    abstract protected function colonnesObligatoires(): array;

    /**
     * Importe une ligne canonique en entité Eloquent.
     *
     * @param array<string, mixed> $ligne ligne déjà mappée (clés canoniques)
     *
     * @return Model|null l'entité créée/mise à jour, ou null si erreur
     */
    abstract protected function importerLigne(array $ligne, int $numeroLigne, array $contexte): ?Model;

    /**
     * Synonymes par colonne canonique (réutilisés par l'analyzer).
     * Doit retourner le sous-tableau correspondant au type, depuis ExcelAnalyzerService::SYNONYMS.
     */
    abstract protected function synonymes(): array;

    /**
     * Importe une feuille (multi-feuilles : appelé une fois par feuille pertinente).
     *
     * @param string $cheminFichier chemin absolu vers le XLSX
     * @param string $nomFeuille nom de la feuille à importer
     * @param array<string, mixed> $contexte données partagées entre feuilles (papa_id, exercice_id, references existantes…)
     * @param array<string, string>|null $mappingOverride override utilisateur du mapping (en-tête fichier → clé canonique)
     */
    public function importerFeuille(
        BudgetImport $parent,
        string $cheminFichier,
        string $nomFeuille,
        array $contexte,
        ?array $mappingOverride = null,
        bool $dryRun = false,
    ): BudgetImport {
        $this->resetEtat();

        $importEnfant = BudgetImport::create([
            'parent_import_id' => $parent->id,
            'exercice_id' => $parent->exercice_id,
            'fichier_nom' => $parent->fichier_nom,
            'chemin_stockage' => $parent->chemin_stockage,
            'taille_octets' => $parent->taille_octets,
            'hash_sha256' => $parent->hash_sha256,
            'feuille_source' => $nomFeuille,
            'type_donnees' => $this->typeDonnees(),
            'statut' => 'en_cours',
            'execute_par_id' => $parent->execute_par_id,
            'execute_at' => now(),
        ]);

        try {
            $spreadsheet = IOFactory::load($cheminFichier);
            $feuille = $spreadsheet->getSheetByName($nomFeuille);
            if (! $feuille) {
                $this->ajouterErreur($importEnfant, $nomFeuille, null, null, null, 'critique', 'colonne_manquante', "Feuille {$nomFeuille} introuvable.");
                $this->finaliserImport($importEnfant, 'echec');

                return $importEnfant;
            }

            $rows = $feuille->toArray(null, true, true, false);
            if (count($rows) < 2) {
                $this->ajouterErreur($importEnfant, $nomFeuille, 1, null, null, 'avertissement', 'autre', 'Feuille vide ou ne contient que les en-têtes.');
                $this->finaliserImport($importEnfant, 'reussi');

                return $importEnfant;
            }

            $enTetes = array_map(fn ($v) => trim((string) $v), $rows[0]);
            $mapping = $mappingOverride ?? $this->deviner_mapping($enTetes);

            $colonnesManquantes = array_diff($this->colonnesObligatoires(), array_values(array_filter($mapping)));
            if (! empty($colonnesManquantes)) {
                foreach ($colonnesManquantes as $manquante) {
                    $this->ajouterErreur($importEnfant, $nomFeuille, null, $manquante, null, 'critique', 'colonne_manquante', "Colonne obligatoire manquante : {$manquante}.");
                }
                $this->finaliserImport($importEnfant, 'echec');

                return $importEnfant;
            }

            DB::beginTransaction();

            try {
                for ($i = 1; $i < count($rows); $i++) {
                    $this->stats['lues']++;
                    $numeroLigne = $i + 1; // 1-indexé pour utilisateur

                    $ligneCanonique = $this->canonicaliserLigne($rows[$i], $enTetes, $mapping);

                    if ($this->estLigneVide($ligneCanonique)) {
                        continue;
                    }

                    if (! $this->validerLigne($importEnfant, $ligneCanonique, $numeroLigne, $nomFeuille)) {
                        continue;
                    }

                    try {
                        $entite = $this->importerLigne($ligneCanonique, $numeroLigne, $contexte);
                        if ($entite) {
                            if ($entite->wasRecentlyCreated) {
                                $this->stats['creees']++;
                            } else {
                                $this->stats['mises_a_jour']++;
                            }
                            $this->stats['valides']++;
                        }
                    } catch (Throwable $e) {
                        $this->ajouterErreur($importEnfant, $nomFeuille, $numeroLigne, null, null, 'erreur', 'autre', "Erreur ligne {$numeroLigne} : {$e->getMessage()}");
                    }
                }

                if ($dryRun) {
                    DB::rollBack();
                    $this->finaliserImport($importEnfant, $this->stats['erreurs'] > 0 ? 'echec' : 'prevu');
                } elseif ($this->stats['erreurs'] > 0) {
                    DB::rollBack();
                    $this->finaliserImport($importEnfant, 'echec');
                } else {
                    DB::commit();
                    $this->finaliserImport($importEnfant, 'reussi');
                }
            } catch (Throwable $e) {
                DB::rollBack();
                $this->ajouterErreur($importEnfant, $nomFeuille, null, null, null, 'critique', 'autre', "Erreur transaction : {$e->getMessage()}");
                $this->finaliserImport($importEnfant, 'echec');
            }
        } catch (Throwable $e) {
            $this->ajouterErreur($importEnfant, $nomFeuille, null, null, null, 'critique', 'autre', "Erreur fatale : {$e->getMessage()}");
            $this->finaliserImport($importEnfant, 'echec');
        }

        return $importEnfant->fresh(['erreurs']);
    }

    /** Valide les contraintes propres au type (à overrider si besoin). */
    protected function validerLigne(BudgetImport $import, array $ligne, int $numeroLigne, string $nomFeuille): bool
    {
        $manquants = [];
        foreach ($this->colonnesObligatoires() as $champ) {
            $val = $ligne[$champ] ?? null;
            if ($val === null || $val === '') {
                $manquants[] = $champ;
            }
        }

        if (! empty($manquants)) {
            foreach ($manquants as $champ) {
                $this->ajouterErreur($import, $nomFeuille, $numeroLigne, $champ, null, 'erreur', 'valeur_obligatoire', "Valeur obligatoire manquante : {$champ}.");
            }

            return false;
        }

        return true;
    }

    /** Canonicalise une ligne brute via le mapping (en-tête → champ canonique). */
    protected function canonicaliserLigne(array $rowBrut, array $enTetes, array $mapping): array
    {
        $canonique = [];
        foreach ($enTetes as $idx => $en) {
            $champ = $mapping[$en] ?? null;
            if ($champ) {
                $valeur = $rowBrut[$idx] ?? null;
                $canonique[$champ] = is_string($valeur) ? trim($valeur) : $valeur;
            }
        }

        return $canonique;
    }

    /** True si la ligne ne contient aucune valeur exploitable. */
    protected function estLigneVide(array $ligne): bool
    {
        foreach ($ligne as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }

        return true;
    }

    /** Détecte le mapping si non fourni explicitement. */
    protected function deviner_mapping(array $enTetes): array
    {
        $mapping = [];
        $synonymes = $this->synonymes();
        foreach ($enTetes as $en) {
            $enNorm = $this->normaliser($en);
            $champTrouve = null;
            foreach ($synonymes as $champ => $syns) {
                foreach ($syns as $syn) {
                    if ($this->normaliser($syn) === $enNorm) {
                        $champTrouve = $champ;
                        break 2;
                    }
                }
            }
            $mapping[$en] = $champTrouve;
        }

        return $mapping;
    }

    protected function normaliser(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);

        return preg_replace('/[^a-z0-9]+/', '_', $s) ?: '';
    }

    /**
     * Collecte une erreur en mémoire. La persistance en BDD est différée à finaliserImport()
     * pour ne pas être affectée par un rollback de la transaction métier.
     */
    protected function ajouterErreur(
        BudgetImport $import,
        ?string $feuille,
        ?int $ligne,
        ?string $colonne,
        ?string $valeur,
        string $gravite,
        string $regle,
        string $message,
        ?string $correction = null,
    ): void {
        $this->erreurs[] = [
            'import_id' => $import->id,
            'feuille' => $feuille,
            'ligne' => $ligne,
            'colonne' => $colonne,
            'valeur_fautive' => $valeur,
            'gravite' => $gravite,
            'regle' => $regle,
            'message' => $message,
            'correction_suggeree' => $correction,
        ];

        if (in_array($gravite, ['critique', 'erreur'], true)) {
            $this->stats['erreurs']++;
        }
    }

    protected function finaliserImport(BudgetImport $import, string $statut): void
    {
        // Persister les erreurs APRÈS d'éventuels rollback — hors transaction métier.
        foreach ($this->erreurs as $err) {
            BudgetImportErreur::create([
                'import_id' => $err['import_id'] ?? $import->id,
                'feuille' => $err['feuille'] ?? null,
                'ligne' => $err['ligne'] ?? null,
                'colonne' => $err['colonne'] ?? null,
                'valeur_fautive' => $err['valeur_fautive'] ?? null,
                'gravite' => $err['gravite'],
                'regle' => $err['regle'],
                'message' => $err['message'],
                'correction_suggeree' => $err['correction_suggeree'] ?? null,
            ]);
        }

        $import->update([
            'statut' => $statut,
            'nb_lignes_lues' => $this->stats['lues'],
            'nb_lignes_creees' => $this->stats['creees'],
            'nb_lignes_mises_a_jour' => $this->stats['mises_a_jour'],
            'nb_erreurs' => $this->stats['erreurs'],
            'journal' => [
                'stats' => $this->stats,
                'erreurs' => array_slice($this->erreurs, 0, 200),
            ],
        ]);
    }

    protected function resetEtat(): void
    {
        $this->erreurs = [];
        $this->stats = [
            'lues' => 0,
            'valides' => 0,
            'creees' => 0,
            'mises_a_jour' => 0,
            'erreurs' => 0,
            'doublons' => 0,
        ];
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getErreurs(): array
    {
        return $this->erreurs;
    }
}
