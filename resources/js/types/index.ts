export interface User {
    id: number;
    name: string;
    email: string;
    matricule: string | null;
    fonction: string | null;
    direction_id: number | null;
    roles: string[];
    permissions: string[];
}

export interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: FlashMessages;
    ziggy?: {
        location: string;
        url: string;
    };
    app: {
        name: string;
        env: string;
    };
    [key: string]: unknown;
}

export type StatutPapa = 'brouillon' | 'en_validation' | 'valide' | 'revise' | 'cloture' | 'archive';

export type TypeAction = 'technique' | 'appui_soutien';

export type Priorite = 'haute' | 'moyenne' | 'basse';

export type TypeResultat = 'output' | 'outcome';

export type TypeIndicateur = 'quantitatif' | 'qualitatif';

export type FrequenceCollecte = 'mensuelle' | 'trimestrielle' | 'semestrielle' | 'annuelle';

export type SourceBudget = 'ceeac' | 'partenaire';

export type StatutActivite = 'planifiee' | 'en_cours' | 'realisee' | 'suspendue' | 'annulee';

export type NiveauAlerte = 'info' | 'attention' | 'critique';

export interface Papa {
    id: number;
    annee: number;
    version: string;
    libelle: string;
    description: string | null;
    statut: StatutPapa;
    date_validation: string | null;
    valide_par_id: number | null;
    cloture_le: string | null;
    created_at: string;
    updated_at: string;
    actions_count?: number;
    taux_execution_physique?: number;
    taux_execution_financier?: number;
}

export interface Departement {
    id: number;
    code: string;
    libelle: string;
    commissaire_id: number | null;
    commissaire?: User;
    directions?: Direction[];
}

export interface Direction {
    id: number;
    code: string;
    libelle: string;
    type: 'technique' | 'appui_soutien';
    departement_id: number | null;
    departement?: Departement;
    directeur_id: number | null;
}

// === Chaîne RBM/GAR CEEAC : Axe → Produit → SousProduit → Activité → Tâche ===

export type StatutRbm = 'brouillon' | 'soumis' | 'en_validation' | 'valide' | 'rejete' | 'archive';

export interface Axe {
    id: number;
    papa_id: number;
    code: string;
    libelle: string;
    description: string | null;
    statut: StatutRbm;
    ordre: number;
    poids: number;
    taux_execution: number;
    date_debut: string | null;
    date_fin: string | null;
    departement_id: number | null;
    responsable_id: number | null;
    papa?: Papa;
    departement?: Departement;
}

export interface Produit {
    id: number;
    axe_id: number;
    code: string;
    libelle: string;
    description: string | null;
    statut: StatutRbm;
    ordre: number;
    poids: number;
    taux_execution: number;
    axe?: Axe;
}

export interface SousProduit {
    id: number;
    produit_id: number;
    code: string;
    libelle: string;
    description: string | null;
    statut: StatutRbm;
    ordre: number;
    poids: number;
    taux_execution: number;
    produit?: Produit;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

// === Activités & Tâches (exécution) ===

export type NiveauRisque = 'faible' | 'moyen' | 'eleve' | 'critique';

export type StatutTache = 'planifiee' | 'en_cours' | 'realisee' | 'suspendue';

export interface Activite {
    id: number;
    sous_produit_id: number;
    code: string;
    libelle: string;
    description: string | null;
    statut: StatutActivite;
    ordre: number;
    poids: number;
    taux_execution: number;
    avancement?: number;
    date_debut_prevue: string | null;
    date_fin_prevue: string | null;
    date_debut_reelle: string | null;
    date_fin_reelle: string | null;
    niveau_risque: NiveauRisque;
    est_jalon: boolean;
    direction_id: number | null;
    responsable_id: number | null;
    point_focal_id: number | null;
    sous_produit?: SousProduit;
    direction?: Direction;
    responsable?: User;
    point_focal?: User;
    taches?: Tache[];
    taches_count?: number;
}

export interface Tache {
    id: number;
    activite_id: number;
    code: string;
    libelle: string;
    description: string | null;
    statut: StatutTache;
    ordre: number;
    poids: number;
    taux_execution: number;
    date_debut: string | null;
    date_fin: string | null;
    date_debut_prevue?: string | null;
    date_fin_prevue?: string | null;
    responsable_id: number | null;
    assigne_a_id: number | null;
    activite?: Activite;
    responsable?: User;
    assigne_a?: User;
}

// === Indicateurs (CMR) ===

export interface Indicateur {
    id: number;
    sous_produit_id: number;
    code: string;
    libelle: string;
    definition: string | null;
    type: TypeIndicateur;
    unite: string | null;
    baseline: number | null;
    cible: number | null;
    valeur_actuelle: number | null;
    taux_realisation: number;
    date_baseline: string | null;
    methode_calcul: string | null;
    frequence_collecte: FrequenceCollecte;
    source_donnees: string | null;
    responsable_id: number | null;
    sous_produit?: SousProduit;
    responsable?: User;
    valeurs?: ValeurIndicateur[];
}

export interface ValeurIndicateur {
    id: number;
    indicateur_id: number;
    date_observation: string;
    periode_libelle: string | null;
    valeur: number;
    commentaire: string | null;
    source_verification: string | null;
    saisi_par_id: number | null;
    saisi_par?: User;
}

// === Budget ===

export type NatureBudget = 'recette' | 'depense';

export type TypeBudget =
    | 'recette_interne'
    | 'recette_externe'
    | 'fonctionnement'
    | 'investissement'
    | 'equipement'
    | 'dotation'
    | 'dette'
    | 'transfert'
    | 'autre';

export interface BudgetExercice {
    id: number;
    annee: number;
    libelle: string;
    statut: 'projet' | 'en_validation' | 'arrete' | 'execute' | 'cloture';
    date_ouverture: string | null;
    date_cloture: string | null;
    montant_total_prevu: number;
}

export interface BudgetSourceFinancement {
    id: number;
    code: string;
    libelle: string;
    type: 'etat_membre' | 'ptf' | 'fonds_propre' | 'autre';
}

export interface BudgetLigne {
    id: number;
    exercice_id: number;
    nature: NatureBudget;
    type_budget: TypeBudget;
    pilier: number | null;
    libelle: string;
    code_action: string | null;
    article_code: string | null;
    paragraphe_code: string | null;
    montant_ceeac_em: number;
    montant_ptf: number;
    montant_total: number;
    axe_id: number | null;
    produit_id: number | null;
    sous_produit_id: number | null;
    activite_id: number | null;
    source_financement_id: number | null;
    exercice?: BudgetExercice;
    axe?: Axe;
    source?: BudgetSourceFinancement;
}

// === Documents (GED) ===

export interface Document {
    id: number;
    titre: string;
    description: string | null;
    type: string;
    chemin_stockage: string;
    nom_fichier: string;
    taille_octets: number;
    mime_type: string;
    documentable_type: string | null;
    documentable_id: number | null;
    uploaded_by_id: number;
    uploaded_by?: User;
}

// === Rapports générés ===

export type CategorieRapport = 'strategique' | 'performance' | 'rbm' | 'budget' | 'gouvernance' | 'audit' | 'analytique';

export interface GeneratedReport {
    id: number;
    rapport_key: string;
    categorie: CategorieRapport;
    titre: string;
    nom_fichier: string;
    chemin_stockage: string;
    taille_octets: number;
    hash_sha256: string;
    code_verification: string;
    parametres: Record<string, unknown>;
    nb_telechargements: number;
    genere_par_id: number;
    genere_at: string;
    genereur?: User;
}

// === Alertes ===

export interface Alerte {
    id: number;
    niveau: NiveauAlerte;
    titre: string;
    message: string;
    alertable_type: string;
    alertable_id: number;
    lue_at: string | null;
    resolue_at: string | null;
    created_at: string;
}

// === Filtres communs ===

export interface FiltreCommun {
    q?: string;
    statut?: string;
    papa_id?: number;
}

// === Inertia helpers ===

export interface CanFlags {
    create?: boolean;
    update?: boolean;
    delete?: boolean;
    validate?: boolean;
    [key: string]: boolean | undefined;
}
