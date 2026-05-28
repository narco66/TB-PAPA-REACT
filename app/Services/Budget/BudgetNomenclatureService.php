<?php

namespace App\Services\Budget;

use App\Models\Budget\BudgetArticle;
use App\Models\Budget\BudgetChapitre;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetParagraphe;
use InvalidArgumentException;

class BudgetNomenclatureService
{
    public function decomposerCode(string $code): array
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) < 5) {
            throw new InvalidArgumentException('Le code de ligne budgétaire doit contenir au moins 5 chiffres.');
        }

        return [
            'chapitre_code' => substr($code, 0, 2),
            'article_code' => substr($code, 0, 3),
            'paragraphe_code' => substr($code, 0, 4),
            'budget_ligne_code' => $code,
        ];
    }

    public function normaliserLigne(array $data, BudgetExercice $exercice, ?int $userId = null, bool $persistReferentiels = true): array
    {
        $code = (string) ($data['budget_ligne_code'] ?? $data['code_action'] ?? '');
        if ($code !== '' && preg_match('/^\D*\d{5,}\D*$/', $code) === 1) {
            $parts = $this->decomposerCode($code);
            $data = [
                ...$data,
                'chapitre_code' => $parts['chapitre_code'],
                'article_code' => $parts['article_code'],
                'paragraphe_code' => $parts['paragraphe_code'],
                'budget_ligne_code' => $parts['budget_ligne_code'],
            ];

            $refs = $persistReferentiels
                ? $this->assurerReferentiels($exercice, $parts, (string) ($data['libelle'] ?? $parts['budget_ligne_code']), $userId)
                : $this->trouverReferentiels($exercice, $parts);

            $data['budget_chapitre_id'] = $refs['chapitre']?->id;
            $data['budget_article_id'] = $refs['article']?->id;
            $data['budget_paragraphe_id'] = $refs['paragraphe']?->id;
        }

        $total = round((float) ($data['montant_total'] ?? 0), 2);
        $ceeac = round((float) ($data['montant_ceeac_em'] ?? 0), 2);
        $ptf = round((float) ($data['montant_ptf'] ?? 0), 2);

        if (abs(($ceeac + $ptf) - $total) > 0.01) {
            throw new InvalidArgumentException('Contrôle financier rejeté : Total doit être égal à Part CEEAC + Part PTF.');
        }

        $data['devise'] = strtoupper((string) ($data['devise'] ?? $exercice->devise ?? 'XAF'));

        return $data;
    }

    public function assurerReferentiels(BudgetExercice $exercice, array $parts, string $libelle, ?int $userId = null): array
    {
        $chapitre = BudgetChapitre::firstOrCreate(
            ['exercice_id' => $exercice->id, 'code' => $parts['chapitre_code']],
            ['libelle' => "Chapitre {$parts['chapitre_code']}", 'created_by' => $userId],
        );

        $article = BudgetArticle::firstOrCreate(
            ['exercice_id' => $exercice->id, 'code' => $parts['article_code']],
            ['chapitre_id' => $chapitre->id, 'libelle' => "Article {$parts['article_code']}", 'created_by' => $userId],
        );

        if ((int) $article->chapitre_id !== (int) $chapitre->id) {
            throw new InvalidArgumentException("Collision hiérarchique : l'article {$article->code} n'appartient pas au chapitre {$chapitre->code}.");
        }

        $paragraphe = BudgetParagraphe::firstOrCreate(
            ['exercice_id' => $exercice->id, 'code' => $parts['paragraphe_code']],
            ['article_id' => $article->id, 'libelle' => "Paragraphe {$parts['paragraphe_code']}", 'created_by' => $userId],
        );

        if ((int) $paragraphe->article_id !== (int) $article->id) {
            throw new InvalidArgumentException("Collision hiérarchique : le paragraphe {$paragraphe->code} n'appartient pas à l'article {$article->code}.");
        }

        return compact('chapitre', 'article', 'paragraphe');
    }

    public function trouverReferentiels(BudgetExercice $exercice, array $parts): array
    {
        $chapitre = BudgetChapitre::where('exercice_id', $exercice->id)->where('code', $parts['chapitre_code'])->first();
        $article = BudgetArticle::where('exercice_id', $exercice->id)->where('code', $parts['article_code'])->first();
        $paragraphe = BudgetParagraphe::where('exercice_id', $exercice->id)->where('code', $parts['paragraphe_code'])->first();

        return compact('chapitre', 'article', 'paragraphe');
    }

    public function codeExisteDeja(BudgetExercice $exercice, string $code, ?int $ignoreId = null): bool
    {
        $query = BudgetLigne::query()
            ->where('exercice_id', $exercice->id)
            ->where('budget_ligne_code', $this->decomposerCode($code)['budget_ligne_code']);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
