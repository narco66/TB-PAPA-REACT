# Pull Request

## Objet
<!-- Décrire en 1–2 phrases l'objet du PR -->

## Type de changement
- [ ] 🐛 Bug fix
- [ ] ✨ Nouvelle fonctionnalité
- [ ] ♻️ Refactor (sans changement fonctionnel)
- [ ] 📚 Documentation
- [ ] 🔒 Sécurité
- [ ] 🎨 UX / UI
- [ ] ⚙️ Infrastructure / CI

## Périmètre RBM
- [ ] PAPA
- [ ] Axe
- [ ] Produit
- [ ] Sous-Produit
- [ ] Activité
- [ ] Tâche
- [ ] Indicateur (CMR)
- [ ] Budget
- [ ] Workflow / Validation
- [ ] Reporting / PDF
- [ ] GED
- [ ] Audit / Sécurité
- [ ] Administration
- [ ] Hors périmètre fonctionnel

## Détails du changement
<!-- Décrire les modifications apportées (fichiers, modèles, services, écrans) -->

## Constats audit couverts
<!-- Référencer les constats de docs/AUDIT-COMPLET.md (ex. C-001, C-009) si applicable -->

## Conformité référentielle
<!-- Si applicable -->
- [ ] RBM/GAR (Axe → Produit → Sous-Produit → Activité → Tâche)
- [ ] CAD/OCDE (impact/effet/produit/processus)
- [ ] IPSAS (cycle budgétaire)
- [ ] COSO ERM (risques + séparation tâches)
- [ ] ISO 27001 (sécurité)
- [ ] RGPD (protection données)
- [ ] OWASP Top 10
- [ ] WCAG 2.1 AA

## Validation locale
- [ ] `composer lint:check` (Pint) — ✅ passé
- [ ] `composer stan` (PHPStan niveau 5) — ✅ 0 erreurs
- [ ] `composer test` (PHPUnit) — ✅ tous tests verts
- [ ] `npm run typecheck` — ✅ 0 erreurs TS
- [ ] `npm run build` — ✅ build réussi
- [ ] Migrations testées (up + down) si applicable
- [ ] Validation manuelle du parcours utilisateur

## Tests ajoutés
<!-- Nouveau test(s) ou cas couvert(s) -->

## Captures d'écran
<!-- Pour les changements UX/UI, joindre des avant/après -->

## Breaking changes
<!-- Lister les changements qui cassent la compatibilité (migrations destructives, renommage API, etc.) -->

## Plan de bascule
<!-- Étapes de déploiement spécifiques (cache:clear, migrate, seed, etc.) -->
