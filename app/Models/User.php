<?php

namespace App\Models;

use App\Auth\TwoFactor\TwoFactorAuthenticatable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'matricule',
        'fonction',
        'telephone',
        'bio',
        'profile_photo_path',
        'direction_id',
        'service_id',
        'organizational_unit_id',
        'actif',
        'derniere_connexion_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'matricule', 'fonction', 'direction_id', 'actif'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function departementsDiriges(): HasMany
    {
        return $this->hasMany(Departement::class, 'commissaire_id');
    }

    public function directionsDirigees(): HasMany
    {
        return $this->hasMany(Direction::class, 'directeur_id');
    }

    public function actionsResponsable(): HasMany
    {
        return $this->hasMany(ActionPrioritaire::class, 'responsable_id');
    }

    public function activitesPointFocal(): HasMany
    {
        return $this->hasMany(Activite::class, 'point_focal_id');
    }

    // === Helpers institutionnels (gouvernance CEEAC) ===

    public function estPresident(): bool
    {
        return $this->hasRole('president');
    }

    public function estVicePresident(): bool
    {
        return $this->hasRole('vice_president');
    }

    public function estCommissaire(): bool
    {
        return $this->hasRole('commissaire');
    }

    public function estSecretaireGeneral(): bool
    {
        return $this->hasRole('secretaire_general');
    }

    public function estDirecteur(): bool
    {
        return $this->hasAnyRole(['directeur_technique', 'directeur_appui']);
    }

    public function estPointFocal(): bool
    {
        return $this->hasRole('point_focal');
    }

    public function estChefService(): bool
    {
        return $this->hasRole('chef_service');
    }

    public function estAuditeur(): bool
    {
        return $this->hasAnyRole(['audit_interne', 'controle_financier']);
    }

    /**
     * Niveau d'autorité institutionnelle.
     * Conforme à la hiérarchie de la Section 1.2 du CDC.
     */
    public function niveauAutorite(): int
    {
        return match (true) {
            $this->estPresident() => 1,
            $this->estVicePresident() => 2,
            $this->estCommissaire() => 3,
            $this->estSecretaireGeneral() => 4,
            $this->estDirecteur() => 5,
            $this->estChefService() => 6,
            $this->estPointFocal() => 7,
            $this->estAuditeur() => 8,
            default => 99,
        };
    }
}
