import { Archive, CheckCircle2, FileText, FolderArchive, Lock, PiggyBank, Search, Shield, Upload } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleGed() {
    return (
        <ModuleLayout
            eyebrow="Gestion électronique documentaire"
            title="GED Documentaire"
            description="Gestion électronique des pièces justificatives, validation institutionnelle, classement par typologie, contrôle d'accès et confidentialité."
            icon={PiggyBank}
            gradient="from-indigo-500 to-blue-600"
            metrics={[
                { value: 'SHA-256', label: 'Hash intégrité' },
                { value: 'Multi', label: 'Typologies' },
                { value: 'RBAC', label: 'Contrôle accès' },
                { value: '100%', label: 'Audit trail' },
            ]}
            overview="Le module GED (Gestion Électronique de Documents) centralise toutes les pièces justificatives institutionnelles : lettres de mission, conventions, rapports, pièces budgétaires, comptes rendus de réunion. Chaque document est versionné, validé selon un workflow institutionnel et protégé par un contrôle d'accès fin basé sur les rôles. L'intégrité est garantie par un hash SHA-256 calculé à l'upload."
            features={[
                { icon: Upload, title: 'Upload sécurisé', description: 'Téléversement avec calcul automatique de hash SHA-256 avant stockage. Validation MIME et taille.' },
                { icon: FolderArchive, title: 'Typologies métiers', description: 'Classement par catégorie : lettre de mission, convention, rapport, pièce comptable, CR de réunion, etc.' },
                { icon: Shield, title: 'Confidentialité', description: 'Trois niveaux : public, restreint, confidentiel. Visibilité contrôlée par rôle utilisateur.' },
                { icon: Lock, title: 'Contrôle d\'accès RBAC', description: 'Permissions granulaires : document.view, document.viewConfidential, document.validate, document.delete.' },
                { icon: CheckCircle2, title: 'Validation workflow', description: 'Cycle de validation institutionnelle : projet → soumis → validé. Daté et signé numériquement.' },
                { icon: Search, title: 'Recherche multicritères', description: 'Filtres par typologie, statut, période, auteur, projet rattaché. Recherche plein-texte sur libellés.' },
                { icon: FileText, title: 'Rattachement RBM', description: 'Chaque document peut être lié à un axe, produit, sous-produit, activité ou ligne budgétaire (polymorphique).' },
                { icon: Archive, title: 'Archivage long terme', description: 'Soft deletes — les documents validés ne peuvent être supprimés définitivement. Restauration possible.' },
            ]}
            workflow={[
                { titre: 'Upload du document', description: 'L\'utilisateur téléverse le fichier avec libellé, description, typologie et niveau de confidentialité.' },
                { titre: 'Calcul hash + stockage', description: 'Hash SHA-256 calculé automatiquement. Stockage sur disque local sécurisé.' },
                { titre: 'Soumission validation', description: 'Le document passe au statut soumis. Notification aux validateurs habilités.' },
                { titre: 'Validation institutionnelle', description: 'Le validateur (chef de service, directeur, SG selon le type) approuve ou rejette avec commentaire.' },
                { titre: 'Diffusion contrôlée', description: 'Document validé visible selon le niveau de confidentialité et les rôles des consultants.' },
                { titre: 'Conservation', description: 'Conservation institutionnelle. Soft delete uniquement, restauration possible par admin.' },
            ]}
            standards={[
                { code: 'ISO 15489', label: 'Records management — gestion documentaire' },
                { code: 'ISO 27001', label: 'Sécurité de l\'information' },
                { code: 'RGPD', label: 'Protection des données personnelles' },
                { code: 'MoReq', label: 'Model Requirements for the management of Electronic Records' },
            ]}
            benefits={[
                'Fin du papier dispersé : tous les documents institutionnels centralisés',
                'Intégrité garantie par hash cryptographique — impossible de manipuler un document validé',
                'Contrôle d\'accès fin : un document confidentiel n\'est jamais visible par les non-habilités',
                'Audit ex-post facilité : un document peut être retrouvé en quelques clics',
                'Conformité RGPD : qui a accédé à quel document, quand',
                'Rattachement aux activités RBM/GAR pour preuves de réalisation',
            ]}
        />
    );
}
