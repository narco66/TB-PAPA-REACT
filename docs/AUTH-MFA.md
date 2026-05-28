# Authentification & Double authentification (MFA)

**Version** : 1.0 · **Date** : 27 mai 2026
**Couvre les constats C-001 (MFA absent) et OWASP A07 (Authentication Failures) de [docs/AUDIT-COMPLET.md](AUDIT-COMPLET.md).**

---

## 1. Vue d'ensemble

Trois couches de défense sont en place :

1. **Authentification primaire** : email + mot de passe **fort** (≥ 12 caractères, mixed case + chiffre + spécial).
2. **Anti-bruteforce** : rate limiting IP+email (5 essais / 60 s) + compteur d'échecs persistant en BDD + verrouillage temporaire optionnel.
3. **Double authentification TOTP** : code à 6 chiffres généré par Google Authenticator / Microsoft Authenticator / Authy à chaque login pour les rôles sensibles.

Aucune dépendance externe lourde (pas de Fortify) — implémentation directe avec [`pragmarx/google2fa`](https://github.com/antonioribeiro/google2fa) (RFC 6238 TOTP) + QR via `simplesoftwareio/simple-qrcode` déjà présent.

---

## 2. Schéma BDD (table `users`)

Migration : `2026_05_27_000010_add_two_factor_columns_to_users_table`.

| Colonne | Type | Rôle |
|---|---|---|
| `two_factor_secret` | TEXT, nullable | Secret TOTP, **chiffré** via `encrypt()` Laravel. |
| `two_factor_recovery_codes` | TEXT, nullable | JSON chiffré de 8 codes de récupération à usage unique. |
| `two_factor_confirmed_at` | TIMESTAMP, nullable | Activation effective. Null = en cours de configuration. |
| `failed_login_attempts` | UNSIGNED INT, default 0 | Compteur d'échecs persistant. |
| `locked_until` | TIMESTAMP, nullable | Verrouillage temporaire admin (au-delà du rate limiter session). |
| `password_changed_at` | TIMESTAMP, nullable | Pour détection mots de passe anciens (à exploiter en V2). |

---

## 3. Flux fonctionnels

### 3.1 Activation 2FA par l'utilisateur

```
/settings/two-factor
  → bouton "Activer la 2FA"           POST /settings/two-factor/enable
  → génère secret + 8 recovery codes  (état : pending)
  → affichage QR + clé manuelle
  → saisie code à 6 chiffres          POST /settings/two-factor/confirm
  → vérification TOTP
  → confirmed_at = now()              (état : enabled)
  → affichage des recovery codes (à archiver hors-ligne)
```

### 3.2 Login avec 2FA active

```
POST /login (email + password + remember?)
  → si credentials OK et 2FA activée :
       session.login.id = user.id
       session.login.remember = remember
       redirect → /two-factor-challenge
  → sinon login direct.

GET  /two-factor-challenge                  (Inertia page)
POST /two-factor-challenge (code OU recovery_code)
  → vérification TOTP (verifyKey, fenêtre ±30 s)
  → ou vérification recovery code (usage unique, supprimé après)
  → Auth::login + regenerate session
  → derniere_connexion_at + failed_login_attempts = 0
  → redirect intended (dashboard)
```

### 3.3 Désactivation

```
DELETE /settings/two-factor (password actuel requis)
  → secret + recovery_codes + confirmed_at = null
```

### 3.4 Régénération des codes de récupération

```
POST /settings/two-factor/recovery-codes (password actuel requis)
  → nouveaux 8 codes (les anciens deviennent invalides)
```

---

## 4. Rôles soumis à l'obligation de 2FA

Définis dans `App\Http\Middleware\EnforceTwoFactor::ROLES_SENSIBLES` :

- `president`
- `vice_president`
- `commissaire`
- `secretaire_general`
- `audit_interne`
- `controle_financier`
- `admin_fonctionnel`
- `admin_technique`

**Effet** : tant que la 2FA n'est pas confirmée pour ces rôles, l'utilisateur est redirigé vers `/settings/two-factor` à chaque tentative de navigation (sauf logout + routes 2FA elles-mêmes). Le middleware est aliasé `enforce.2fa` et appliqué sur le groupe `auth` complet.

Pour ajouter ou retirer un rôle de cette liste : éditer `ROLES_SENSIBLES` dans le middleware.

---

## 5. Politique de mot de passe

Règle `App\Rules\StrongPassword` (12 caractères minimum par défaut, paramétrable) :

- ≥ 12 caractères
- ≥ 1 minuscule
- ≥ 1 majuscule
- ≥ 1 chiffre
- ≥ 1 caractère spécial (non alphanumérique)

Appliquée dans :
- `Admin\UserController::store` (création par admin)
- `Admin\UserController::update` (changement de mot de passe par admin)
- `password_changed_at` est mis à jour automatiquement.

À étendre en V2 :
- Vérification HIBP (Have I Been Pwned API) pour rejeter les mots de passe compromis.
- Rotation forcée tous les 180 jours via middleware lisant `password_changed_at`.

---

## 6. Anti-bruteforce & verrouillage

### 6.1 Rate limiting de session (déjà en place)

`AuthenticatedSessionController::store` : clé `lower(email)|ip`, limite 5 tentatives, déclenche `Lockout` event et message d'erreur.

### 6.2 Compteur persistant

À chaque échec d'authentification primaire, `users.failed_login_attempts` est incrémenté. Reset à 0 lors d'une connexion réussie.

### 6.3 Verrouillage administratif `locked_until`

Si `users.locked_until` est dans le futur, le login est refusé avec un message explicite. Permet à un admin de verrouiller manuellement un compte compromis sans toucher au mot de passe.

Exemple en tinker :
```php
User::find(1)->update(['locked_until' => now()->addDays(7)]);
```

---

## 7. Pages React

| Route Inertia | Fichier | Rôle |
|---|---|---|
| `auth/two-factor-challenge` | `resources/js/pages/auth/two-factor-challenge.tsx` | Page de challenge post-login. Toggle TOTP / recovery code. |
| `settings/two-factor` | `resources/js/pages/settings/two-factor.tsx` | Activation, QR + secret, confirmation, codes de récupération, désactivation. |

---

## 8. API publique

Aucune nouvelle route API. Tout passe par les routes web Inertia. La 2FA s'applique uniquement à la session web ; pour une future API REST, prévoir Laravel Sanctum avec scopes + jetons à durée limitée.

---

## 9. Bonnes pratiques d'exploitation

- **Onboarding utilisateur sensible** : l'admin crée le compte → premier login → redirect automatique vers `/settings/two-factor` (middleware) → enrollment guidé.
- **Récupération** : si l'utilisateur perd son appareil + ses codes de récupération → l'admin peut désactiver la 2FA via tinker (`$user->update(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])`) après vérification d'identité hors-ligne.
- **Audit** : les changements 2FA passent par `Spatie\ActivityLog` (via `LogsActivity` trait sur User). Vérifier dans `/admin/audit` toute activation/désactivation suspecte.
- **Stockage des secrets** : secret + recovery codes sont chiffrés avec la clé `APP_KEY`. **Backup régulier de `APP_KEY` impératif** — sans elle, les utilisateurs perdent leur 2FA et doivent ré-enroller.

---

## 10. Tests

Tests prévus (à implémenter dans le chantier P0 "Couverture tests") :

- `Auth\TwoFactorEnableTest` : génère un secret + confirme avec un code TOTP calculé.
- `Auth\TwoFactorChallengeTest` : login → challenge → code valide → connecté.
- `Auth\TwoFactorRecoveryTest` : recovery code à usage unique.
- `Auth\EnforceTwoFactorMiddlewareTest` : utilisateur president sans 2FA → redirigé.
- `Auth\BruteforceTest` : 5 échecs en < 60s → lockout.
- `Rules\StrongPasswordTest` : 6 cas (court, sans maj, sans min, sans chiffre, sans spécial, valide).

---

## 11. Conformité référentielle

| Référentiel | Exigence | Couverture |
|---|---|---|
| **OWASP ASVS 4.0 V2.x** | MFA pour comptes privilégiés | ✅ |
| **NIST SP 800-63B AAL2** | Authentificateur cryptographique (TOTP RFC 6238) | ✅ |
| **ISO 27001 A.9.4.2** | Procédures sécurisées d'ouverture de session | ✅ |
| **RGPD art. 32** | Pseudonymisation/chiffrement, robustesse des systèmes | ✅ secret chiffré |
| **PSD2 (si paiements)** | SCA — Strong Customer Authentication | ⏳ N/A pour PAPA |

---

## 12. Chantiers de renforcement futurs

| Priorité | Action | Effort |
|---|---|---|
| P1 | Tests MFA complets (cf. §10) | S |
| P2 | Notification email/SMS d'activation/désactivation 2FA | S |
| P2 | Webauthn / FIDO2 (clés physiques) comme alternative TOTP | L |
| P2 | Rotation forcée mots de passe (180 j) via middleware | S |
| P3 | Intégration HIBP Pwned Passwords API | S |
| P3 | SSO institutionnel (SAML / OIDC Keycloak CEEAC) | L |

---

**Fin du document** — version 1.0
