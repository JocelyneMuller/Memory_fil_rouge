<?php

require_once __DIR__ . '/../models/Projects_model.php';
require_once __DIR__ . '/../models/Manage_model.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

class ProjectsController {
    private $PDO;

    public function __construct($PDO)
    {
        $this->PDO = $PDO;
    }

    /**
     * Méthode privée pour vérifier l'authentification
     * Évite la duplication du code dans toutes les méthodes
     */
    private function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }

    public function listProjects()
    {
        $this->requireAuth();
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

        // ========================================
        // ENDPOINTS D'ATTRIBUTION DE PROJETS
        // ========================================
        case 'assign':
            return $this->assignUser();
        case 'remove_assignment':
            return $this->removeAssignment();
        case 'assignments':
            $projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
            return $this->getAssignments($projectId);
        case 'user_projects':
            $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
            $role = filter_input(INPUT_GET, 'role');
            return $this->getUserProjects($userId, $role);
        case 'change_role':
            return $this->changeUserRole();
        case 'available_users':
            $projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
            return $this->getAvailableUsers($projectId);
        case 'assignment_stats':
            $projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
            return $this->getAssignmentStats($projectId);

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
    $this->requireAuth();
    
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
    $this->requireAuth();
    
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
    $this->requireAuth();
    
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

// ========================================
// MÉTHODES D'ATTRIBUTION DE PROJETS
// ========================================

/**
 * Assigner un utilisateur à un projet
 * POST: user_id, project_id, role
 */
public function assignUser()
{
    $this->requireAuth();

    // Récupération des données POST
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    $role = filter_input(INPUT_POST, 'role');
    $startDate = filter_input(INPUT_POST, 'start_date');

    // Validation
    if (!$userId || !$projectId || !$role) {
        return ['error' => 'Missing required fields: user_id, project_id, role'];
    }

    // Obtenir l'ID de l'utilisateur authentifié
    $assignedByUserId = AuthMiddleware::getCurrentUserId();

    try {
        $model = new Manage_model($this->PDO);
        $result = $model->assignUserToProject($userId, $projectId, $role, $assignedByUserId, $startDate);

        if ($result === true) {
            return [
                'success' => true,
                'message' => 'User successfully assigned to project',
                'assignment' => [
                    'user_id' => $userId,
                    'project_id' => $projectId,
                    'role' => $role,
                    'assigned_date' => date('Y-m-d H:i:s')
                ]
            ];
        } else {
            return $result; // Retourne l'array avec l'erreur
        }

    } catch (Exception $e) {
        error_log('Assignment error: ' . $e->getMessage());
        return ['error' => 'Failed to assign user to project'];
    }
}

/**
 * Retirer l'assignation d'un utilisateur
 * POST: user_id, project_id
 */
public function removeAssignment()
{
    $this->requireAuth();

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);

    if (!$userId || !$projectId) {
        return ['error' => 'Missing required fields: user_id, project_id'];
    }

    $removedByUserId = AuthMiddleware::getCurrentUserId();

    try {
        $model = new Manage_model($this->PDO);
        $result = $model->removeAssignment($userId, $projectId, $removedByUserId);

        if ($result === true) {
            return [
                'success' => true,
                'message' => 'Assignment removed successfully',
                'removed_date' => date('Y-m-d')
            ];
        } else {
            return $result;
        }

    } catch (Exception $e) {
        error_log('Remove assignment error: ' . $e->getMessage());
        return ['error' => 'Failed to remove assignment'];
    }
}

/**
 * Obtenir toutes les assignations d'un projet
 * GET: project_id
 */
public function getAssignments($projectId)
{
    $this->requireAuth();

    if (!$projectId) {
        return ['error' => 'Project ID is required'];
    }

    try {
        $model = new Manage_model($this->PDO);
        $assignments = $model->getProjectAssignments($projectId);

        return [
            'success' => true,
            'project_id' => $projectId,
            'assignments' => $assignments,
            'total' => count($assignments)
        ];

    } catch (Exception $e) {
        error_log('Get assignments error: ' . $e->getMessage());
        return ['error' => 'Failed to retrieve assignments'];
    }
}

/**
 * Obtenir tous les projets d'un utilisateur
 * GET: user_id, role (optionnel)
 */
public function getUserProjects($userId, $role = null)
{
    $this->requireAuth();

    // Si pas d'user_id fourni, utilise l'utilisateur connecté
    if (!$userId) {
        $userId = AuthMiddleware::getCurrentUserId();
    }

    try {
        $model = new Manage_model($this->PDO);
        $projects = $model->getUserProjects($userId, $role);

        return [
            'success' => true,
            'user_id' => $userId,
            'role_filter' => $role,
            'projects' => $projects,
            'total' => count($projects)
        ];

    } catch (Exception $e) {
        error_log('Get user projects error: ' . $e->getMessage());
        return ['error' => 'Failed to retrieve user projects'];
    }
}

/**
 * Changer le rôle d'un utilisateur dans un projet
 * POST: user_id, project_id, new_role
 */
public function changeUserRole()
{
    $this->requireAuth();

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    $newRole = filter_input(INPUT_POST, 'new_role');

    if (!$userId || !$projectId || !$newRole) {
        return ['error' => 'Missing required fields: user_id, project_id, new_role'];
    }

    $changedByUserId = AuthMiddleware::getCurrentUserId();

    try {
        $model = new Manage_model($this->PDO);
        $result = $model->changeUserRole($userId, $projectId, $newRole, $changedByUserId);

        if ($result === true) {
            return [
                'success' => true,
                'message' => 'User role changed successfully',
                'user_id' => $userId,
                'project_id' => $projectId,
                'new_role' => $newRole,
                'changed_date' => date('Y-m-d H:i:s')
            ];
        } else {
            return $result;
        }

    } catch (Exception $e) {
        error_log('Change role error: ' . $e->getMessage());
        return ['error' => 'Failed to change user role'];
    }
}

/**
 * Obtenir les utilisateurs disponibles pour assignation
 * GET: project_id
 */
public function getAvailableUsers($projectId)
{
    $this->requireAuth();

    if (!$projectId) {
        return ['error' => 'Project ID is required'];
    }

    try {
        $model = new Manage_model($this->PDO);
        $users = $model->getAvailableUsers($projectId);

        return [
            'success' => true,
            'project_id' => $projectId,
            'available_users' => $users,
            'total' => count($users)
        ];

    } catch (Exception $e) {
        error_log('Get available users error: ' . $e->getMessage());
        return ['error' => 'Failed to retrieve available users'];
    }
}

/**
 * Obtenir les statistiques d'attribution d'un projet
 * GET: project_id
 */
public function getAssignmentStats($projectId)
{
    $this->requireAuth();

    if (!$projectId) {
        return ['error' => 'Project ID is required'];
    }

    try {
        $model = new Manage_model($this->PDO);
        $stats = $model->getProjectStats($projectId);

        return [
            'success' => true,
            'project_id' => $projectId,
            'statistics' => $stats
        ];

    } catch (Exception $e) {
        error_log('Get assignment stats error: ' . $e->getMessage());
        return ['error' => 'Failed to retrieve assignment statistics'];
    }
}

}