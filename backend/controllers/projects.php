<?php

require_once __DIR__ . '/../models/Projects_model.php';

class ProjectsController {
    private $PDO;
    public function __construct($PDO)
    {
        $this->PDO = $PDO;
    }

    public function listProjects()
    {
    $category = filter_input(INPUT_GET, 'category');
        $model = new Projects_model($this->PDO);
    if ($category) {
        $projects = $model->getProjectsByCategory($category);
    }
    else {

        $projects = $model->getAllProjects();
    }
        return $projects;
}

public function run (){
    $action=filter_input(INPUT_GET, 'action');

    switch ($action) {
        case 'list':
            return $this->listProjects();
        case 'show' :
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            return $this->show($id);
        case 'create':
            return $this->createProject();
        case 'archivate':
            return $this->archiveProject();
        default:
            return ['error' => 'Invalid action'];
    }
}

/**
 * Afficher un projet spécifique par son ID
 * 
 * @param int|false|null $id ID du projet (peut être false si validation échoue)
 * @return array Données du projet ou erreur
 */
public function show($id)
{
    if ($id && is_int($id)) {
        $model = new Projects_model($this->PDO);
        return $model->getProjectById($id);
    } else {
        return ['error' => 'Project ID is required and must be valid'];
    }
}

/**
 * Créer un nouveau projet
 * Traite les données POST, les valide et délègue la création au modèle
 */
public function createProject()
{
    // ============================================
    // ÉTAPE 1 : RÉCUPÉRATION DES DONNÉES UTILISATEUR
    // ============================================
    
    // Récupération des données POST (formulaire Vue.js)
    $name = filter_input(INPUT_POST, 'name');
    $description = filter_input(INPUT_POST, 'description');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    
    // Nettoyage des chaînes
    if ($name) {
        $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
    }
    if ($description) {
        $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');
    }
    
    // ============================================
    // ÉTAPE 2 : VALIDATION DES DONNÉES
    // ============================================
    
    // Vérification que tous les champs obligatoires sont remplis
    if (empty($name) || empty($description) || !$categoryId) {
        return ['error' => 'Missing required fields: name, description, category_id'];
    }

    // Vérification de la longueur selon les limites de la base de données
    // Dans votre BDD, Name_Unique fait VARCHAR(50)
    if (strlen($name) > 50) {
        return ['error' => 'Project name too long (max 50 characters)'];
    }
    
    // Dans votre BDD, Description fait TEXT(1000)
    if (strlen($description) > 1000) {
        return ['error' => 'Description too long (max 1000 characters)'];
    }

    // ============================================
    // ÉTAPE 3 : DÉLÉGATION AU MODÈLE
    // ============================================
    
    try {
        // Création d'une instance du modèle avec la connexion base de données
        $model = new Projects_model($this->PDO);
        
        // Demande au modèle de créer le projet en base
        $projectId = $model->createProject($name, $description, $categoryId);
        
        // Vérification du succès de l'opération
        if ($projectId) {
            // Succès : on renvoie l'ID du nouveau projet
            return [
                'success' => true, 
                'project_id' => $projectId, 
                'message' => 'Project created successfully'
            ];
        } else {
            // Échec sans exception (cas rare)
            return ['error' => 'Failed to create project'];
        }
        
    } catch (Exception $e) {
        // ============================================
        // ÉTAPE 4 : GESTION D'ERREUR SÉCURISÉE
        // ============================================
        
        // Log l'erreur technique pour le développeur
        error_log('Project creation error: ' . $e->getMessage());
        
        // TEMPORAIRE : Mode debug pour voir l'erreur réelle
        return ['error' => 'Debug: ' . $e->getMessage()];
        
        // PRODUCTION : Retourne un message sécurisé à l'utilisateur
        // return ['error' => 'An error occurred while creating the project'];
    }
}

/**
 * Archiver un projet
 * Met à jour Archive_date à la date du jour pour marquer le projet comme archivé
 */
public function archiveProject()
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        return ['error' => 'Project ID is required and must be valid'];
    }
    
    try {
        $model = new Projects_model($this->PDO);
        $success = $model->archiveProject($id);
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Project archived successfully',
                'project_id' => $id,
                'archived_date' => date('Y-m-d')
            ];
        } else {
            return ['error' => 'Failed to archive project (project may not exist)'];
        }
        
    } catch (Exception $e) {
        error_log('Project archive error: ' . $e->getMessage());
        
        if (strpos($e->getMessage(), 'already archived') !== false) {
            return ['error' => 'Project is already archived'];
        }
        
        return ['error' => 'An error occurred while archiving the project'];
    }
}

}