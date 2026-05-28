import { Activity, BarChart3, Calculator, Calendar, ChartLine, FileBarChart, Layers, Target, TrendingUp } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleIndicateurs() {
    return (
        <ModuleLayout
            eyebrow="Mesure de la performance"
            title="Indicateurs CMR"
            description="Cadre de Mesure des Résultats : typologie OCDE/CAD (intrant/produit/effet/impact), désagrégation, paliers trimestriels, mesure fine de la performance institutionnelle."
            icon={BarChart3}
            gradient="from-amber-500 to-orange-600"
            metrics={[
                { value: '4', label: 'Typologies OCDE' },
                { value: '4', label: 'Paliers trimestriels' },
                { value: 'Multi', label: 'Désagrégation' },
                { value: 'CMR', label: 'Référentiel' },
            ]}
            overview="Le Cadre de Mesure des Résultats (CMR) est la pièce maîtresse de la mesure de performance dans TB-PAPA. Chaque indicateur est rattaché au niveau Sous-Produit et catégorisé selon la typologie officielle OCDE/CAD (intrant, produit, effet, impact). La saisie périodique des valeurs alimente automatiquement les tableaux de bord stratégiques et les rapports trimestriels/annuels."
            features={[
                { icon: Target, title: 'Typologie OCDE/CAD', description: 'Classification rigoureuse des indicateurs : intrant (input), produit (output), effet (outcome), impact. Conforme au Manuel CAD §1.43.' },
                { icon: Calculator, title: 'Baseline, cible, valeur', description: 'Trois mesures clés : baseline initiale, cible annuelle, valeur actuelle observée. Taux de réalisation calculé automatiquement.' },
                { icon: Calendar, title: 'Paliers trimestriels', description: 'Saisie des valeurs trimestrielles (T1, T2, T3, T4) avec date d\'observation et commentaire validateur.' },
                { icon: Layers, title: 'Désagrégation', description: 'Décomposition par sexe, âge, État membre, zone géographique pour des analyses fines (genre, équité régionale).' },
                { icon: TrendingUp, title: 'Tendance dynamique', description: 'Calcul automatique de la tendance : hausse, stable, baisse. Visualisation graphique de l\'évolution.' },
                { icon: ChartLine, title: 'Indicateurs à risque', description: 'Identification automatique des indicateurs sous-performants (< 40 %) et tableaux de remédiation.' },
                { icon: Activity, title: 'Fréquence collecte', description: 'Mensuelle, trimestrielle, semestrielle, annuelle — adaptable par indicateur.' },
                { icon: FileBarChart, title: 'Méthode de calcul', description: 'Documentation explicite de la formule, source de vérification et responsable de la collecte.' },
            ]}
            workflow={[
                { titre: 'Définition de l\'indicateur', description: 'Saisie du code, libellé, définition métier, type (quantitatif/qualitatif), catégorie OCDE, unité de mesure.' },
                { titre: 'Fixation baseline et cible', description: 'Baseline = valeur de référence avant intervention. Cible = ambition annuelle validée par les Commissaires.' },
                { titre: 'Définition méthode et fréquence', description: 'Méthode de calcul, source de vérification, fréquence de collecte (mensuelle, trimestrielle, etc.).' },
                { titre: 'Saisie périodique', description: 'Saisie de la valeur observée à chaque palier par le responsable de la collecte. Validation par le superviseur.' },
                { titre: 'Calcul automatique du taux', description: 'Le taux de réalisation est calculé : (valeur_actuelle / cible) × 100. Tendance ajustée.' },
                { titre: 'Restitution', description: 'Intégration aux dashboards exécutifs, rapports trimestriels et annuels. Export PDF/Excel.' },
            ]}
            standards={[
                { code: 'OCDE/CAD', label: 'Comité d\'Aide au Développement — Manuel CAD' },
                { code: 'ONU-Agenda 2030', label: 'Objectifs de Développement Durable (ODD)' },
                { code: 'UA Agenda 2063', label: 'Aspirations de l\'Union Africaine' },
                { code: 'IATI', label: 'International Aid Transparency Initiative' },
            ]}
            benefits={[
                'Mesure objective de la performance, basée sur des données chiffrées',
                'Reporting automatisé aux partenaires techniques et financiers (PTF)',
                'Détection précoce des sous-performances et plans correctifs',
                'Désagrégation fine pour analyses genre/équité/territoires',
                'Conformité aux exigences de transparence et de redevabilité internationales',
            ]}
        />
    );
}
