<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ValeurIndicateur extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'valeurs_indicateurs';

    protected $fillable = [
        'indicateur_id',
        'date_observation',
        'periode_libelle',
        'trimestre',
        'annee',
        'valeur',
        'valeur_hommes',
        'valeur_femmes',
        'desagregation_age',
        'desagregation_geographique',
        'desagregation_vulnerabilite',
        'commentaire',
        'source_verification',
        'saisi_par_id',
        'valide_at',
        'valide_par_id',
    ];

    protected $casts = [
        'date_observation' => 'date',
        'valeur' => 'float',
        'valeur_hommes' => 'float',
        'valeur_femmes' => 'float',
        'annee' => 'integer',
        'desagregation_age' => 'array',
        'desagregation_geographique' => 'array',
        'desagregation_vulnerabilite' => 'array',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function indicateur(): BelongsTo
    {
        return $this->belongsTo(Indicateur::class);
    }

    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    /** Vérifie la cohérence de la désagrégation genre (somme = valeur totale). */
    public function genreCoherent(?float $tolerance = 0.01): bool
    {
        if ($this->valeur_hommes === null || $this->valeur_femmes === null) {
            return true;
        }
        $somme = (float) $this->valeur_hommes + (float) $this->valeur_femmes;

        return abs($somme - (float) $this->valeur) <= ($tolerance ?? 0.01);
    }
}
