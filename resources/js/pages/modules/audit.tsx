import { Activity, AlertTriangle, ClipboardCheck, FileWarning, Inspect, ListChecks, Search, ShieldCheck, Target, Users } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleAudit() {
    return (
        <ModuleLayout
            eyebrow="Inspection Générale des Services (IGS)"
            title="Audit interne IGS"
            description="Plan d'audit annuel, missions, constats, recommandations et suivi de mise en œuvre. Conformité IIA/IPPF, IFACI, ISO 19011 et COSO Internal Control."
            icon={ShieldCheck}
            gradient="from-cyan-500 to-sky-600"
            metrics={[
                { value: '6', label: 'Tables dédiées' },
                { value: '5', label: 'Standards intégrés' },
                { value: 'IIA', label: 'Référentiel pivot' },
                { value: '5', label: 'Phases mission' },
            ]}
            overview="Le module Audit interne IGS implémente le cycle d'audit complet d'une Inspection Générale des Services, conformément aux normes internationales IIA/IPPF (International Professional Practices Framework). De la programmation annuelle au suivi des recommandations, chaque étape est tracée, datée, signée et auditable. Les missions couvrent l'audit financier, de conformité, de performance, organisationnel, des systèmes d'information et le suivi des recommandations antérieures."
            features={[
                { icon: ClipboardCheck, title: 'Plan d\'audit annuel', description: 'Programmation annuelle des missions (IIA Standard 2010). Validation par la Direction générale, périmètre et orientation stratégique.' },
                { icon: Target, title: 'Missions d\'audit', description: 'Lettre de mission, équipe (chef, auditeurs, observateurs), périmètre, calendrier prévu/réel, synthèse exécutive.' },
                { icon: Users, title: 'Équipe pivot', description: 'Pivot user × rôle : chef de mission, auditeur senior, auditeur, observateur, expert externe.' },
                { icon: Search, title: 'Constats', description: 'Saisie des constats par mission avec gravité (critique/majeur/moyen/mineur/observation), nature (non-conformité, risque, etc.).' },
                { icon: AlertTriangle, title: 'Cause racine + impact', description: 'Analyse 5 Pourquoi pour chaque constat. Impact opérationnel/financier/image documenté.' },
                { icon: ListChecks, title: 'Recommandations', description: 'Plan d\'action proposé avec priorité (urgente/haute/moyenne/basse), responsable, échéance et pourcentage d\'avancement.' },
                { icon: Activity, title: 'Suivi mise en œuvre', description: 'Suivis périodiques de chaque recommandation (état, %, actions réalisées, actions restantes, blocages).' },
                { icon: FileWarning, title: 'Alertes retard', description: 'Détection automatique des recommandations dont l\'échéance est dépassée — relances trimestrielles.' },
                { icon: Inspect, title: 'Activity log Spatie', description: 'Journalisation native de toutes les actions audit (création mission, validation, modifications).' },
            ]}
            workflow={[
                { titre: 'Élaboration du plan annuel', description: 'L\'IGS rédige le plan d\'audit pour l\'exercice : missions retenues, orientation stratégique, périmètre.' },
                { titre: 'Validation Direction générale', description: 'Le plan est soumis au Président pour validation officielle. Statut : projet → soumis → validé.' },
                { titre: 'Lancement mission', description: 'Lettre de mission émise. Équipe constituée. Calendrier prévisionnel arrêté. Statut : planifiée → lettre_emise.' },
                { titre: 'Exécution terrain', description: 'Audit sur le terrain : entretiens, échantillonnage, vérifications documentaires. Statut : en_cours.' },
                { titre: 'Projet de rapport', description: 'Rédaction du projet de rapport avec constats et recommandations. Communication aux audités pour contradictoire.' },
                { titre: 'Rapport définitif', description: 'Validation du rapport final après contradictoire. Diffusion à la Direction générale.' },
                { titre: 'Suivi recommandations', description: 'Suivi périodique de la mise en œuvre par l\'IGS. Évaluation jusqu\'à clôture (statut "vérifiée").' },
            ]}
            standards={[
                { code: 'IIA / IPPF', label: 'International Professional Practices Framework (Standards 1000/2010/2200/2400/2500)' },
                { code: 'IFACI', label: 'Institut Français de l\'Audit et du Contrôle Internes — Cadre de référence' },
                { code: 'ISO 19011', label: 'Lignes directrices pour l\'audit des systèmes de management' },
                { code: 'COSO IC', label: 'Internal Control — Integrated Framework' },
                { code: 'INTOSAI', label: 'Organisation internationale des Institutions Supérieures de Contrôle' },
            ]}
            benefits={[
                'Cycle d\'audit institutionnel conforme aux meilleures pratiques mondiales',
                'Traçabilité complète : qui a constaté, qui a recommandé, qui a mis en œuvre, quand',
                'Suivi systématique des recommandations — fin des recommandations sans suite',
                'Reporting automatique à la Direction générale sur l\'efficacité du contrôle interne',
                'Conformité aux exigences des audits externes (Cour des Comptes, PTF)',
                'Tableaux de bord IGS avec recommandations en retard, missions en cours, taux de mise en œuvre',
            ]}
        />
    );
}
