<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'categorie',
        'libelle',
        'description',
        'nom_fichier',
        'chemin_stockage',
        'mime_type',
        'taille_octets',
        'hash_sha256',
        'uploade_par_id',
        'confidentiel',
        'valide_at',
        'valide_par_id',
        'version',
        'document_parent_id',
    ];

    protected $casts = [
        'confidentiel' => 'boolean',
        'valide_at' => 'datetime',
        'taille_octets' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['libelle', 'categorie', 'valide_at', 'version', 'confidentiel'])
            ->logOnlyDirty();
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploade_par_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'document_parent_id');
    }
}
