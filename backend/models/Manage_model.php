<?php

/**
 * Modèle pour la gestion des attributions de projets
 *
 * Gère les relations entre utilisateurs et projets avec rôles :
 * - Managers : Chefs de projet (peuvent assigner d'autres utilisateurs)
 * - Developers : Développeurs (travaillent sur les projets)
 *
 * @version 1.0
 * @date 31 Décembre 2025
 */
class Manage_model {
    private $PDO;

    public function __construct($PDO)
    {
        $this->PDO = $PDO;
    }

    /**
     * Assigner un utilisateur à un projet avec un rôle spécifique
     *
     * @param int $userId ID de l'utilisateur à assigner
     * @param int $projectId ID du projet
     * @param string $role 'manager' ou 'developer'
     * @param int $assignedByUserId ID de l'utilisateur qui fait l'assignation
     * @param string|null $startDate Date de début (optionnelle, par défaut aujourd'hui)
     * @return bool|array true si succès, array avec erreur sinon
     */
    public function assignUserToProject($userId, $projectId, $role, $assignedByUserId, $startDate = null)
    {
        try {
            // Vérifier que l'utilisateur et le projet existent
            if (!$this->userExists($userId)) {
                return ['error' => 'User does not exist'];
            }

            if (!$this->projectExists($projectId)) {
                return ['error' => 'Project does not exist'];
            }

            // Vérifier que le rôle est valide
            if (!in_array($role, ['manager', 'developer'])) {
                return ['error' => 'Invalid role. Must be manager or developer'];
            }

            // Vérifier les permissions (seuls les managers peuvent assigner)
            if (!$this->canUserAssign($assignedByUserId, $projectId)) {
                return ['error' => 'You do not have permission to assign users to this project'];
            }

            // Vérifier si l'assignation existe déjà
            if ($this->assignmentExists($userId, $projectId)) {
                return ['error' => 'User is already assigned to this project'];
            }

            // Préparer la date de début
            $startDate = $startDate ?: date('Y-m-d');

            $stmt = $this->PDO->prepare("
                INSERT INTO Manage
                (User_id_User, Project_id_Project, role_in_project, assigned_by_user_id, status, start_date)
                VALUES (:userId, :projectId, :role, :assignedBy, 'active', :startDate)
            ");

            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':assignedBy', $assignedByUserId, PDO::PARAM_INT);
            $stmt->bindParam(':startDate', $startDate);

            return $stmt->execute();

        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Retirer l'assignation d'un utilisateur d'un projet
     *
     * @param int $userId ID de l'utilisateur
     * @param int $projectId ID du projet
     * @param int $removedByUserId ID de l'utilisateur qui retire l'assignation
     * @return bool|array true si succès, array avec erreur sinon
     */
    public function removeAssignment($userId, $projectId, $removedByUserId)
    {
        try {
            // Vérifier les permissions
            if (!$this->canUserAssign($removedByUserId, $projectId)) {
                return ['error' => 'You do not have permission to remove assignments from this project'];
            }

            // Marquer comme inactive au lieu de supprimer (historique)
            $stmt = $this->PDO->prepare("
                UPDATE Manage
                SET status = 'inactive', end_date = CURDATE(), updated_at = CURRENT_TIMESTAMP
                WHERE User_id_User = :userId AND Project_id_Project = :projectId AND status = 'active'
            ");

            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return true;
            }

            return ['error' => 'Assignment not found or already inactive'];

        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Obtenir toutes les assignations actives d'un projet
     *
     * @param int $projectId ID du projet
     * @return array Liste des assignations avec informations utilisateurs
     */
    public function getProjectAssignments($projectId)
    {
        $stmt = $this->PDO->prepare("
            SELECT
                m.*,
                u.Username,
                u.Email_Unique,
                assignedBy.Username as assigned_by_username
            FROM Manage m
            LEFT JOIN User u ON m.User_id_User = u.id_User
            LEFT JOIN User assignedBy ON m.assigned_by_user_id = assignedBy.id_User
            WHERE m.Project_id_Project = :projectId AND m.status = 'active'
            ORDER BY m.role_in_project DESC, m.assigned_date ASC
        ");

        $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir tous les projets d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string|null $role Filtrer par rôle (optionnel)
     * @return array Liste des projets avec informations
     */
    public function getUserProjects($userId, $role = null)
    {
        $sql = "
            SELECT
                m.*,
                p.Name_Unique as project_name,
                p.Description as project_description,
                p.Date_of_creation,
                p.Archive_date,
                c.Name_Unique as category_name
            FROM Manage m
            LEFT JOIN Project p ON m.Project_id_Project = p.id_Project
            LEFT JOIN Category c ON p.Category_id_Category = c.id_Category
            WHERE m.User_id_User = :userId AND m.status = 'active'
        ";

        if ($role) {
            $sql .= " AND m.role_in_project = :role";
        }

        $sql .= " ORDER BY m.role_in_project DESC, m.assigned_date DESC";

        $stmt = $this->PDO->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        if ($role) {
            $stmt->bindParam(':role', $role);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Changer le rôle d'un utilisateur dans un projet
     *
     * @param int $userId ID de l'utilisateur
     * @param int $projectId ID du projet
     * @param string $newRole Nouveau rôle ('manager' ou 'developer')
     * @param int $changedByUserId ID de l'utilisateur qui fait le changement
     * @return bool|array true si succès, array avec erreur sinon
     */
    public function changeUserRole($userId, $projectId, $newRole, $changedByUserId)
    {
        try {
            // Vérifications
            if (!in_array($newRole, ['manager', 'developer'])) {
                return ['error' => 'Invalid role'];
            }

            if (!$this->canUserAssign($changedByUserId, $projectId)) {
                return ['error' => 'You do not have permission to change roles in this project'];
            }

            $stmt = $this->PDO->prepare("
                UPDATE Manage
                SET role_in_project = :newRole, updated_at = CURRENT_TIMESTAMP
                WHERE User_id_User = :userId AND Project_id_Project = :projectId AND status = 'active'
            ");

            $stmt->bindParam(':newRole', $newRole);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return true;
            }

            return ['error' => 'Assignment not found or no changes made'];

        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Obtenir les utilisateurs disponibles pour assignation (non encore assignés)
     *
     * @param int $projectId ID du projet
     * @return array Liste des utilisateurs disponibles
     */
    public function getAvailableUsers($projectId)
    {
        $stmt = $this->PDO->prepare("
            SELECT u.id_User, u.Username, u.Email_Unique, u.Role
            FROM User u
            WHERE u.id_User NOT IN (
                SELECT m.User_id_User
                FROM Manage m
                WHERE m.Project_id_Project = :projectId AND m.status = 'active'
            )
            ORDER BY u.Username
        ");

        $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir les statistiques d'attribution d'un projet
     *
     * @param int $projectId ID du projet
     * @return array Statistiques
     */
    public function getProjectStats($projectId)
    {
        $stmt = $this->PDO->prepare("
            SELECT
                COUNT(*) as total_assignments,
                COUNT(CASE WHEN role_in_project = 'manager' THEN 1 END) as managers_count,
                COUNT(CASE WHEN role_in_project = 'developer' THEN 1 END) as developers_count,
                MIN(assigned_date) as first_assignment,
                MAX(assigned_date) as last_assignment
            FROM Manage
            WHERE Project_id_Project = :projectId AND status = 'active'
        ");

        $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ===============================
    // MÉTHODES PRIVÉES (UTILITAIRES)
    // ===============================

    /**
     * Vérifier si un utilisateur existe
     */
    private function userExists($userId)
    {
        $stmt = $this->PDO->prepare("SELECT id_User FROM User WHERE id_User = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Vérifier si un projet existe et n'est pas archivé
     */
    private function projectExists($projectId)
    {
        $stmt = $this->PDO->prepare("SELECT id_Project FROM Project WHERE id_Project = :id AND Archive_date IS NULL");
        $stmt->bindParam(':id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Vérifier si une assignation existe déjà
     */
    private function assignmentExists($userId, $projectId)
    {
        $stmt = $this->PDO->prepare("
            SELECT * FROM Manage
            WHERE User_id_User = :userId AND Project_id_Project = :projectId AND status = 'active'
        ");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Vérifier si un utilisateur peut faire des assignations sur un projet
     * Critères : admin OU manager du projet
     */
    private function canUserAssign($userId, $projectId)
    {
        // Vérifier si c'est un admin
        $adminCheck = $this->PDO->prepare("SELECT Role FROM User WHERE id_User = :userId");
        $adminCheck->bindParam(':userId', $userId, PDO::PARAM_INT);
        $adminCheck->execute();
        $user = $adminCheck->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['Role'] === 'admin') {
            return true;
        }

        // Vérifier si c'est un manager du projet
        $managerCheck = $this->PDO->prepare("
            SELECT * FROM Manage
            WHERE User_id_User = :userId
            AND Project_id_Project = :projectId
            AND role_in_project = 'manager'
            AND status = 'active'
        ");
        $managerCheck->bindParam(':userId', $userId, PDO::PARAM_INT);
        $managerCheck->bindParam(':projectId', $projectId, PDO::PARAM_INT);
        $managerCheck->execute();

        return $managerCheck->rowCount() > 0;
    }
}