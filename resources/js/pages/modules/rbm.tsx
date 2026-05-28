import { Boxes, GitBranch, Layers, ListChecks, Network, Package, Target, TrendingUp, Workflow } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleRbm() {
    return (
        <ModuleLayout
            eyebrow="Chaîne RBM/GAR officielle CEEAC"
            title="Chaîne RBM/GAR"
            description="Structuration officielle de la planification : Axe stratégique → Produit → Sous-Produit → Activité → Tâche. Conforme aux principes de Gestion Axée sur les Résultats."
            icon={Target}
            gradient="from-emerald-500 to-teal-600"
            metrics={[
                { value: '5', label: 'Niveaux hiérarchiques' },
                { value: '∞', label: 'Profondeur métier' },
                { value: '100%', label: 'Traçabilité descendante' },
                { value: 'OCDE', label: 'Référentiel cible' },
            ]}
            overview="La chaîne RBM/GAR (Results-Based Management / Gestion Axée sur les Résultats) constitue l'ossature opérationnelle de TB-PAPA. Elle décline les orientations stratégiques de la Commission en livrables mesurables, en respectant la nomenclature officielle CEEAC à 5 niveaux. Chaque niveau hérite des contraintes du niveau supérieur (calendrier, budget, responsable) et alimente automatiquement les indicateurs de performance."
            features={[
                { icon: Target, title: 'Axes stratégiques (N1)', description: 'Politiques sectorielles validées par les Commissaires. Représentent les grandes orientations annuelles du PAPA.' },
                { icon: Package, title: 'Produits (N2)', description: 'Livrables institutionnels rattachés aux axes. Décrivent ce qui sera concrètement produit dans l\'année.' },
                { icon: Boxes, title: 'Sous-Produits (N3)', description: 'Décomposition opérationnelle des produits. Permettent un suivi granulaire des livrables intermédiaires.' },
                { icon: Workflow, title: 'Activités (N4)', description: 'Actions planifiées avec calendrier, responsable et budget. Visualisables en vue Gantt.' },
                { icon: ListChecks, title: 'Tâches (N5)', description: 'Exécution opérationnelle granulaire. Assignées à des points focaux avec date d\'échéance.' },
                { icon: TrendingUp, title: 'Propagation automatique', description: 'Le taux d\'exécution remonte automatiquement de la tâche vers l\'axe via un observer Eloquent.' },
                { icon: GitBranch, title: 'Workflow par niveau', description: 'Chaque niveau possède son propre cycle de validation (brouillon, soumis, validé, etc.).' },
                { icon: Network, title: 'Codification automatique', description: 'Les codes (A.1, P.1.1, SP.1.1.1, ACT.1.1.1.1, T.1.1.1.1.1) sont générés et maintenus par observer.' },
                { icon: Layers, title: 'Indicateurs au niveau SP', description: 'Les indicateurs CMR sont rattachés au niveau Sous-Produit selon la typologie OCDE/CAD.' },
            ]}
            workflow={[
                { titre: 'Niveau 1 — Axe stratégique', description: 'Le Commissaire sectoriel propose un axe pour l\'exercice annuel. Validation par le Président de la Commission.' },
                { titre: 'Niveau 2 — Produit', description: 'Définition du livrable institutionnel rattaché à l\'axe. Affectation d\'une Direction technique et d\'un responsable.' },
                { titre: 'Niveau 3 — Sous-Produit', description: 'Décomposition opérationnelle du produit. Rattachement des indicateurs CMR pour la mesure de la performance.' },
                { titre: 'Niveau 4 — Activité', description: 'Planification d\'une action avec dates, responsable, point focal, niveau de risque. Suivi Gantt activé.' },
                { titre: 'Niveau 5 — Tâche', description: 'Exécution granulaire avec assignation, calendrier court, saisie quotidienne de l\'avancement.' },
            ]}
            standards={[
                { code: 'RBM/GAR', label: 'Results-Based Management — méthodologie internationale' },
                { code: 'OCDE/CAD', label: 'Typologie indicateurs : intrant, produit, effet, impact (Manuel CAD §1.43)' },
                { code: 'CEEAC-CDC', label: 'Cadre de Développement Communautaire officiel' },
                { code: 'UN-DESA', label: 'Lignes directrices RBM des Nations Unies' },
            ]}
            benefits={[
                'Cohérence garantie de la planification : impossible de créer une activité orpheline',
                'Reporting automatique consolidé du niveau Tâche au niveau Axe',
                'Comparaison inter-axes facilitée par la nomenclature uniforme',
                'Conformité aux exigences de transparence des partenaires techniques et financiers (PTF)',
                'Identification rapide des goulots d\'étranglement à n\'importe quel niveau',
            ]}
        />
    );
}
