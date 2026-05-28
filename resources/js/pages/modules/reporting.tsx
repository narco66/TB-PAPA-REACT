import { BarChart3, Calendar, CheckCircle2, Download, FileBarChart, FileSpreadsheet, FileText, Hash, QrCode, Shield, Sparkles } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleReporting() {
    return (
        <ModuleLayout
            eyebrow="Reporting institutionnel certifié"
            title="Reporting institutionnel"
            description="Rapports PDF certifiés, exports Excel, tableaux de bord présidence/sectoriels/budgétaires. Chaque document signé par hash SHA-256 et code de vérification."
            icon={FileBarChart}
            gradient="from-orange-500 to-red-600"
            metrics={[
                { value: '20+', label: 'Modèles disponibles' },
                { value: 'PDF + XLSX', label: 'Formats supportés' },
                { value: 'SHA-256', label: 'Empreinte' },
                { value: 'QR', label: 'Vérification' },
            ]}
            overview="Le module Reporting institutionnel produit des documents officiels exploitables par la Présidence, les Commissaires, les partenaires et la Cour des Comptes. Chaque rapport est généré dynamiquement à partir des données live, archivé en base avec hash cryptographique, et porte un QR code de vérification permettant d'attester l'authenticité du document à tout moment."
            features={[
                { icon: FileText, title: 'Tableau de bord exécutif', description: 'KPI consolidés, performance par département, alertes, recommandations. Format PDF institutionnel A4 portrait.' },
                { icon: Sparkles, title: 'Synthèse Cabinet Présidence', description: 'Note confidentielle pour la Présidence : décisions requises, axes critiques, indicateurs CMR.' },
                { icon: BarChart3, title: 'PAPA stratégique complet', description: 'Document de référence avec page de garde, cadre RBM/GAR par axe, budgétisation, calendrier, gouvernance.' },
                { icon: FileSpreadsheet, title: 'Budget consolidé', description: 'Exécution budgétaire complète : prévisions, engagements, paiements, par axe et source de financement.' },
                { icon: FileBarChart, title: 'Matrice RBM/GAR', description: 'Vue matricielle complète de la chaîne : axes, produits, sous-produits, indicateurs avec taux d\'exécution.' },
                { icon: Download, title: 'Liste d\'index PDF', description: 'Export PDF de chaque page liste de l\'app : axes, activités, indicateurs, alertes, etc. avec filtres respectés.' },
                { icon: Calendar, title: 'Rapports planifiés', description: 'Génération automatique périodique : trimestriels, semestriels, annuels selon configuration.' },
                { icon: Hash, title: 'Hash SHA-256', description: 'Chaque PDF généré est hashé. L\'empreinte est stockée en base — impossible à modifier sans détection.' },
                { icon: QrCode, title: 'QR de vérification', description: 'QR code intégré au pied de chaque PDF, redirige vers /rapports/verifier/{code} pour valider l\'authenticité.' },
                { icon: Shield, title: 'Code de vérification', description: 'Code alphanumérique 16 caractères, traçable en base, permettant aux tiers de vérifier la provenance.' },
                { icon: CheckCircle2, title: 'Archivage automatique', description: 'Tous les rapports générés sont archivés dans generated_reports avec auteur, date, filtres appliqués.' },
            ]}
            workflow={[
                { titre: 'Sélection du rapport', description: 'L\'utilisateur choisit le rapport dans le catalogue (par catégorie : stratégique, budgétaire, audit, etc.).' },
                { titre: 'Application des filtres', description: 'Sélection des filtres : PAPA, exercice, département, période. Tous optionnels selon le rapport.' },
                { titre: 'Génération du PDF', description: 'Calcul des données live, rendu Blade institutionnel, conversion PDF via DomPDF, calcul du hash.' },
                { titre: 'Signature cryptographique', description: 'Hash SHA-256 + code de vérification + QR code intégrés au PDF. Métadonnées en base.' },
                { titre: 'Téléchargement / Archivage', description: 'Téléchargement immédiat par l\'utilisateur. Disponible aussi dans l\'historique institutionnel.' },
                { titre: 'Vérification ultérieure', description: 'Tout tiers peut scanner le QR ou saisir le code sur /rapports/verifier pour confirmer l\'authenticité.' },
            ]}
            standards={[
                { code: 'ISO 32000', label: 'Format PDF/A pour archivage long terme' },
                { code: 'ISO 27001', label: 'Sécurité de l\'information et intégrité' },
                { code: 'eIDAS', label: 'Signature électronique européenne (cadre référence)' },
                { code: 'INTOSAI', label: 'Standards des Institutions Supérieures de Contrôle' },
            ]}
            benefits={[
                'Aucun rapport manuel : tout est généré dynamiquement à partir des données live',
                'Authenticité prouvable : un tiers peut vérifier qu\'un PDF n\'a pas été falsifié',
                'Conformité aux exigences de transparence des partenaires internationaux',
                'Gain de temps massif : un rapport généré en quelques secondes au lieu de quelques heures',
                'Historique complet des rapports générés, recherchable et téléchargeable',
                'Logo CEEAC officiel + charte graphique institutionnelle sur chaque document',
            ]}
        />
    );
}
