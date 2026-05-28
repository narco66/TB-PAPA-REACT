<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_key', 'categorie', 'titre', 'description',
        'filtres',
        'chemin_stockage', 'nom_fichier', 'taille_octets', 'hash_sha256',
        'format', 'nb_pages',
        'code_verification', 'signe_numeriquement', 'signature_hash',
        'genere_par_id', 'genere_at',
        'nb_telechargements', 'dernier_telechargement_at', 'dernier_telechargement_par_id',
        'archive_ged', 'document_id',
        'statut', 'erreur',
    ];

    protected $casts = [
        'filtres' => 'array',
        'genere_at' => 'datetime',
        'dernier_telechargement_at' => 'datetime',
        'signe_numeriquement' => 'boolean',
        'archive_ged' => 'boolean',
        'taille_octets' => 'integer',
        'nb_pages' => 'integer',
        'nb_telechargements' => 'integer',
    ];

    public function genereur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'genere_par_id');
    }

    public function dernierTelechargeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dernier_telechargement_par_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function urlVerification(): string
    {
        return url('/rapports/verifier/' . $this->code_verification);
    }
}
