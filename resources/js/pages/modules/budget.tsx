import { CheckCircle2, Coins, CreditCard, FileText, Layers, PiggyBank, Receipt, Scale, Shield, Wallet } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleBudget() {
    return (
        <ModuleLayout
            eyebrow="Comptabilité publique"
            title="Budget institutionnel IPSAS"
            description="Exercices budgétaires, lignes CEEAC-EM et PTF, cycle complet IPSAS : engagement → liquidation → ordonnancement → paiement. Conformité internationale comptes publics."
            icon={Wallet}
            gradient="from-rose-500 to-pink-600"
            metrics={[
                { value: '4', label: 'Étapes IPSAS' },
                { value: 'CEEAC + PTF', label: 'Sources financement' },
                { value: 'XAF', label: 'Devise CEEAC' },
                { value: '100%', label: 'Traçabilité' },
            ]}
            overview="Le module Budget institutionnel implémente le cycle complet IPSAS (International Public Sector Accounting Standards) tel qu'appliqué dans la sphère publique. La structure budgétaire respecte la nomenclature officielle CEEAC (Chapitre → Article → Paragraphe → Ligne) et différencie strictement les contributions des États Membres (CEEAC-EM) des financements des Partenaires Techniques et Financiers (PTF). Le cycle dépense respecte la séparation ordonnateur/comptable issue de COSO ERM."
            features={[
                { icon: PiggyBank, title: 'Exercices budgétaires', description: 'Cadre annuel de planification avec statut (brouillon, validé, clôturé), période et devise. Recalcul automatique des totaux.' },
                { icon: Layers, title: 'Nomenclature CEEAC', description: 'Chapitre → Article → Paragraphe → Ligne, alignée sur la pratique des organisations régionales.' },
                { icon: Scale, title: 'Sources de financement', description: 'CEEAC-EM (États Membres) + PTF (UE, BAD, BM, ONUDI, etc.). Règle : Total = CEEAC + PTF.' },
                { icon: FileText, title: 'Lignes budgétaires', description: 'Détail par ligne avec rattachement RBM (activité/tâche), département, source, statut workflow.' },
                { icon: Coins, title: 'Cycle IPSAS — Engagement', description: 'Engagement de dépense par l\'ordonnateur. Contrôle de disponibilité budgétaire (anti-dépassement).' },
                { icon: Receipt, title: 'Cycle IPSAS — Liquidation', description: 'Constatation du service fait. Plafond ≤ engagement. Pièce justificative obligatoire.' },
                { icon: CheckCircle2, title: 'Cycle IPSAS — Ordonnancement', description: 'Ordre de payer émis par l\'ordonnateur. Séparation stricte ordonnateur/comptable (COSO ERM).' },
                { icon: CreditCard, title: 'Cycle IPSAS — Paiement', description: 'Décaissement par le comptable. Mode (virement, chèque, etc.), pièce comptable et bénéficiaire.' },
                { icon: Shield, title: 'Import Excel multi-feuilles', description: 'Import des budgets depuis Excel avec mapping intelligent, contrôles d\'intégrité et rapport d\'erreurs.' },
            ]}
            workflow={[
                { titre: 'Préparation budgétaire', description: 'Le Directeur de la Planification et du Budget (DPPB) initialise un nouvel exercice budgétaire et saisit les enveloppes globales.' },
                { titre: 'Validation Présidence', description: 'Le budget est soumis au Président de la Commission pour validation officielle.' },
                { titre: 'Engagement', description: 'L\'ordonnateur engage une dépense sur une ligne budgétaire. Contrôle anti-dépassement automatique.' },
                { titre: 'Liquidation', description: 'Constatation du service fait. Plafond ≤ montant engagé. Pièce justificative obligatoire.' },
                { titre: 'Ordonnancement', description: 'L\'ordonnateur émet l\'ordre de payer (mandat). Bénéficiaire et mode de paiement renseignés.' },
                { titre: 'Paiement', description: 'Le comptable exécute le décaissement (virement, chèque). La chaîne IPSAS est complète et auditable.' },
            ]}
            standards={[
                { code: 'IPSAS 1', label: 'Présentation des états financiers' },
                { code: 'IPSAS 24', label: 'Comptabilité budgétaire publique' },
                { code: 'COSO ERM 2017', label: 'Séparation ordonnateur/comptable, contrôle interne' },
                { code: 'RGCP', label: 'Règlement Général de Comptabilité Publique' },
                { code: 'OHADA', label: 'Système Comptable applicable en Afrique centrale' },
            ]}
            benefits={[
                'Conformité aux standards IPSAS reconnus par tous les bailleurs internationaux',
                'Séparation institutionnelle stricte ordonnateur/comptable, prévention des conflits d\'intérêts',
                'Anti-dépassement budgétaire automatique : impossible d\'engager au-delà du disponible',
                'Visibilité immédiate sur les soldes par ligne (prévu vs engagé vs payé vs disponible)',
                'Reporting financier directement exploitable pour la Cour des Comptes et les PTF',
                'Audit trail complet : qui a engagé, liquidé, ordonnancé, payé — avec dates',
            ]}
        />
    );
}
