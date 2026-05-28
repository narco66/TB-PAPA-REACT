import { AlertTriangle, Calendar, CalendarRange, ChartGantt, Clock, GanttChartSquare, ListChecks, TrendingUp, Users } from 'lucide-react';
import { ModuleLayout } from '@/components/public/module-layout';

export default function ModuleGantt() {
    return (
        <ModuleLayout
            eyebrow="Suivi opérationnel"
            title="Activités & Gantt"
            description="Planification opérationnelle, suivi de l'avancement, vue Gantt institutionnelle avec filtres période (jour/semaine/mois/trimestre)."
            icon={GanttChartSquare}
            gradient="from-violet-500 to-purple-600"
            metrics={[
                { value: '4', label: 'Filtres période' },
                { value: 'Live', label: 'Mise à jour' },
                { value: '100%', label: 'Suivi avancement' },
                { value: '0-100%', label: 'Granularité' },
            ]}
            overview="Le module Activités & Gantt est l'outil quotidien des chefs de service et points focaux. Il offre une vue chronologique consolidée de l'ensemble des activités du PAPA, avec calendrier prévisionnel vs réel, taux d'avancement, niveau de risque et alertes automatiques en cas de dérive. Le filtre de période s'adapte au type de revue (quotidienne, hebdomadaire, mensuelle, trimestrielle)."
            features={[
                { icon: ChartGantt, title: 'Vue Gantt interactive', description: 'Représentation chronologique de toutes les activités sur une frise horizontale, avec dépendances et jalons.' },
                { icon: CalendarRange, title: 'Filtres période adaptatifs', description: 'Vue jour, semaine, mois ou trimestre. Idéal pour les revues opérationnelles à différentes fréquences.' },
                { icon: Calendar, title: 'Dates prévues vs réelles', description: 'Comparaison directe entre date_debut/date_fin planifiées et dates de réalisation effective.' },
                { icon: TrendingUp, title: 'Taux d\'avancement saisi', description: 'Saisie quotidienne du % d\'avancement par l\'assigné. Remontée automatique vers les niveaux supérieurs.' },
                { icon: AlertTriangle, title: 'Niveaux de risque', description: 'Classement automatique faible/moyen/élevé/critique selon les écarts entre planning et réel.' },
                { icon: Clock, title: 'Détection retards', description: 'Alerte automatique pour les activités dont date_fin < aujourd\'hui et statut ≠ realisee/annulee.' },
                { icon: Users, title: 'Assignation tâches', description: 'Chaque tâche est assignée à un agent (assigne_a_id) qui reçoit notifications et alertes d\'échéance.' },
                { icon: ListChecks, title: 'Statuts opérationnels', description: 'planifiée, en_cours, réalisée, suspendue — avec workflow de transition contrôlé.' },
            ]}
            workflow={[
                { titre: 'Création de l\'activité', description: 'Saisie depuis l\'éditeur RBM ou directement dans le module activités. Renseignement des dates prévisionnelles.' },
                { titre: 'Affectation responsable + point focal', description: 'Désignation du responsable de l\'activité (chef_service) et du point focal opérationnel quotidien.' },
                { titre: 'Démarrage', description: 'Passage du statut planifiée → en_cours. Saisie de la date_debut_reelle.' },
                { titre: 'Suivi quotidien', description: 'Saisie périodique du taux d\'avancement par l\'assigné. Alertes en cas de dépassement de jalons.' },
                { titre: 'Clôture', description: 'Passage à realisée avec date_fin_reelle. Remontée automatique du taux 100 % vers le sous-produit.' },
            ]}
            standards={[
                { code: 'PMI/PMBOK', label: 'Project Management Institute — bonnes pratiques' },
                { code: 'PRINCE2', label: 'Gestion de projet par étapes (référence britannique)' },
                { code: 'OCDE', label: 'Suivi de l\'aide publique au développement' },
            ]}
            benefits={[
                'Vision panoramique de toutes les activités en cours et à venir',
                'Détection précoce des retards via alertes automatiques',
                'Coordination facilitée entre chefs de service et points focaux',
                'Reporting d\'avancement automatique pour les revues de pilotage',
                'Visualisation des goulots d\'étranglement et conflits de ressources',
            ]}
        />
    );
}
