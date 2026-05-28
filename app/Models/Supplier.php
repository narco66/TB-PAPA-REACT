<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = ['personne_physique', 'personne_morale', 'administration', 'organisme_public', 'autre'];

    public const STATUTS = ['actif', 'suspendu', 'archive'];

    protected $fillable = [
        'code', 'libelle', 'type', 'nif', 'rccm',
        'contact_principal', 'email', 'telephone', 'adresse', 'pays',
        'compte_bancaire', 'banque', 'statut', 'observations', 'created_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
