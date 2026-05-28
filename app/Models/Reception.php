<?php

namespace App\Models;

use App\Models\Budget\BudgetMouvement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Reception extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = ['provisoire', 'definitive', 'partielle', 'refus'];

    public const NATURES = ['biens', 'services', 'travaux'];

    public const CONFORMITES = ['conforme', 'reserves', 'non_conforme'];

    public const STATUTS = ['projet', 'valide', 'rejete', 'cloture'];

    protected $fillable = [
        'budget_mouvement_id', 'service_done_certificate_id', 'reference', 'date_reception',
        'type_reception', 'nature', 'quantite_recue', 'unite_mesure', 'montant_recu',
        'conformite', 'reserves', 'observations',
        'president_commission_id', 'membre1_commission_id', 'membre2_commission_id',
        'saisi_par_id', 'valide_par_id', 'valide_at', 'statut',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'quantite_recue' => 'decimal:3',
        'montant_recu' => 'decimal:2',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(BudgetMouvement::class, 'budget_mouvement_id');
    }

    public function serviceDoneCertificate(): BelongsTo
    {
        return $this->belongsTo(ServiceDoneCertificate::class);
    }

    public function presidentCommission(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_commission_id');
    }

    public function membre1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'membre1_commission_id');
    }

    public function membre2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'membre2_commission_id');
    }

    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public static function genererReference(int $annee): string
    {
        $prefix = "PV-{$annee}-";
        $dernier = static::where('reference', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $dernier ? ((int) substr($dernier->reference, -5)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
