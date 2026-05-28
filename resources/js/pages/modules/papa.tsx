import { Archive, CheckCircle2, ClipboardCheck, FileText, GitBranch, History, Lock, Send, Sparkles } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModulePapa() {
    return (
        <ModuleLayout
            eyebrow="Pilotage stratégique"
            title="PAPA — Plan d'Action Prioritaire Annuel"
            description="Référentiel annuel racine de la Commission de la CEEAC. Cadre opérationnel de planification, soumission, validation et archivage du plan stratégique."
            icon={ClipboardCheck}
            gradient="from-blue-500 to-indigo-600"
            metrics={[
                { value: '1/an', label: 'Cycle annuel' },
                { value: '5', label: 'Niveaux RBM' },
                { value: '7', label: 'États de workflow' },
                { value: '100%', label: 'Traçabilité' },
            ]}
            overview="Le module PAPA constitue le point d'entrée et le référentiel racine de toute la planification opérationnelle de la Commission. Chaque exercice annuel donne lieu à la création d'un PAPA validé par la Présidence, qui structure les axes stratégiques, produits, sous-produits, activités et tâches selon la chaîne RBM/GAR officielle CEEAC."
            features={[
                { icon: FileText, title: 'Création de PAPA annuel', description: 'Initialisation d\'un PAPA pour un exercice donné avec libellé, description, périmètre institutionnel et fenêtre temporelle.' },
                { icon: Send, title: 'Workflow de soumission', description: 'Cycle de soumission/validation institutionnelle conforme : projet → soumis → en_validation → validé → exécuté → clôturé → archivé.' },
                { icon: CheckCircle2, title: 'Validation présidentielle', description: 'La validation finale est réservée à la Présidence et trace la date, le valideur et verrouille le document.' },
                { icon: GitBranch, title: 'Versionnage', description: 'Chaque PAPA porte un numéro de version. L\'historique complet des modifications est journalisé.' },
                { icon: History, title: 'Activity log Spatie', description: 'Toutes les actions (création, soumission, validation, modification) sont enregistrées avec auteur et timestamp.' },
                { icon: Lock, title: 'Verrouillage après validation', description: 'Un PAPA validé est verrouillé et ne peut plus être modifié. Toute modification nécessite un nouveau cycle.' },
                { icon: Archive, title: 'Archivage institutionnel', description: 'En fin d\'exercice, le PAPA est archivé et reste consultable pour le reporting et l\'audit ex-post.' },
                { icon: Sparkles, title: 'PAPA actif unique', description: 'Un seul PAPA est actif à un instant T. Le module dashboard pilote automatiquement le PAPA en cours.' },
            ]}
            workflow={[
                { titre: 'Projet', description: 'Création du PAPA en brouillon par le Secrétariat Général. Saisie des informations institutionnelles, fenêtre temporelle, périmètre.' },
                { titre: 'Soumis', description: 'Le SG soumet le PAPA pour revue technique. Les Commissaires sectoriels sont notifiés.' },
                { titre: 'En validation', description: 'Revue technique par les Commissaires. Vérification de la cohérence stratégique et opérationnelle.' },
                { titre: 'Validé', description: 'Validation finale par le Président. Le PAPA est verrouillé, daté et signé numériquement.' },
                { titre: 'Exécuté', description: 'Le PAPA passe en phase d\'exécution. Les activités peuvent être suivies via le module Gantt et les indicateurs CMR.' },
                { titre: 'Clôturé', description: 'En fin d\'exercice, le PAPA est clôturé. Les rapports finaux sont générés.' },
                { titre: 'Archivé', description: 'Conservation institutionnelle. Le PAPA reste consultable pour l\'audit et le reporting historique.' },
            ]}
            standards={[
                { code: 'RBM/GAR', label: 'Gestion Axée sur les Résultats — chaîne officielle CEEAC' },
                { code: 'CDC §1-7', label: 'Cadre de Développement Communautaire' },
                { code: 'ISO 9001', label: 'Démarche qualité et amélioration continue' },
            ]}
            benefits={[
                'Un référentiel unique partagé par toute la Commission, fin du travail en silos',
                'Workflow institutionnel formalisé, conforme aux exigences de gouvernance régionale',
                'Traçabilité complète : qui a fait quoi, quand, sur quel élément du PAPA',
                'Verrouillage post-validation garantissant l\'intégrité du document de référence',
                'Conformité aux audits internes et externes (cour des comptes, partenaires)',
            ]}
        />
    );
}
