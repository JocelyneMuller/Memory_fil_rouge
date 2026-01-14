<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Manage_model.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Tests unitaires pour le modèle Manage_model
 * Validation de la compétence CDA "Préparer et exécuter les plans de tests"
 *
 * Tests couverts :
 * - Attribution et désattribution d'utilisateurs aux projets
 * - Gestion des rôles (manager/developer)
 * - Validation des permissions
 * - Gestion des erreurs et cas limites
 */
class ManageModelTest extends TestCase
{
    private $PDO;
    private $model;
    private $testUserId;
    private $testManagerId;
    private $testProjectId;
    private $testCategoryId;

    protected function setUp(): void
    {
        // Initialisation de la base de données de test
        require_once __DIR__ . '/../config/database.php';
        global $PDO;

        if (!$PDO instanceof PDO) {
            $this->fail('Database connection failed. Check config/database.php');
        }

        $this->PDO = $PDO;
        $this->model = new Manage_model($this->PDO);

        // Créer des données de test
        $this->createTestData();
    }

    /**
     * Créer les données de test nécessaires
     */
    private function createTestData()
    {
        try {
            // Créer une catégorie de test
            $categoryStmt = $this->PDO->prepare("INSERT INTO Category (Name_Unique) VALUES (?)");
            $categoryStmt->execute(['Test Category ' . time()]);
            $this->testCategoryId = $this->PDO->lastInsertId();

            // Créer un projet de test
            $projectStmt = $this->PDO->prepare("
                INSERT INTO Project (Name_Unique, Description, Date_of_creation, Category_id_Category)
                VALUES (?, ?, CURDATE(), ?)
            ");
            $projectStmt->execute([
                'Test Project ' . time(),
                'Description for manage tests',
                $this->testCategoryId
            ]);
            $this->testProjectId = $this->PDO->lastInsertId();

            // Créer un utilisateur de test (développeur)
            $userStmt = $this->PDO->prepare("
                INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at)
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $userStmt->execute([
                'test_dev_' . time(),
                'test_dev_' . time() . '@test.local',
                password_hash('test123', PASSWORD_DEFAULT)
            ]);
            $this->testUserId = $this->PDO->lastInsertId();

            // Créer un manager de test
            $managerStmt = $this->PDO->prepare("
                INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at)
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $managerStmt->execute([
                'test_manager_' . time(),
                'test_manager_' . time() . '@test.local',
                password_hash('test123', PASSWORD_DEFAULT)
            ]);
            $this->testManagerId = $this->PDO->lastInsertId();

            // Assigner le manager au projet
            $assignStmt = $this->PDO->prepare("
                INSERT INTO Manage (User_id_User, Project_id_Project, role_in_project, assigned_by_user_id, status, start_date)
                VALUES (?, ?, 'manager', 1, 'active', CURDATE())
            ");
            $assignStmt->execute([$this->testManagerId, $this->testProjectId]);

        } catch (Exception $e) {
            $this->fail('Failed to create test data: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
        try {
            if ($this->testUserId) {
                $this->PDO->prepare("DELETE FROM Manage WHERE User_id_User = ?")->execute([$this->testUserId]);
                $this->PDO->prepare("DELETE FROM User WHERE id_User = ?")->execute([$this->testUserId]);
            }

            if ($this->testManagerId) {
                $this->PDO->prepare("DELETE FROM Manage WHERE User_id_User = ?")->execute([$this->testManagerId]);
                $this->PDO->prepare("DELETE FROM User WHERE id_User = ?")->execute([$this->testManagerId]);
            }

            if ($this->testProjectId) {
                $this->PDO->prepare("DELETE FROM Project WHERE id_Project = ?")->execute([$this->testProjectId]);
            }

            if ($this->testCategoryId) {
                $this->PDO->prepare("DELETE FROM Category WHERE id_Category = ?")->execute([$this->testCategoryId]);
            }
        } catch (Exception $e) {
            // Log cleanup errors but don't fail the test
            error_log('Cleanup error: ' . $e->getMessage());
        }
    }

    /**
     * Test MAN01: Attribution manager valide
     */
    public function testAssignUserAsManager()
    {
        // Créer un nouveau utilisateur pour ce test
        $stmt = $this->PDO->prepare("
            INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at)
            VALUES (?, ?, ?, 'user', NOW())
        ");
        $stmt->execute(['test_new_manager_' . time(), 'new_manager_' . time() . '@test.local', password_hash('test123', PASSWORD_DEFAULT)]);
        $newUserId = $this->PDO->lastInsertId();

        // Assigner comme manager
        $result = $this->model->assignUserToProject($newUserId, $this->testProjectId, 'manager', $this->testManagerId);

        $this->assertTrue($result, 'Assignment should succeed');

        // Vérifier en base
        $assignments = $this->model->getProjectAssignments($this->testProjectId);
        $found = false;
        foreach ($assignments as $assignment) {
            if ($assignment['User_id_User'] == $newUserId && $assignment['role_in_project'] == 'manager') {
                $found = true;
                $this->assertEquals('active', $assignment['status']);
                break;
            }
        }

        $this->assertTrue($found, 'Manager assignment should be found in database');

        // Cleanup
        $this->PDO->prepare("DELETE FROM Manage WHERE User_id_User = ?")->execute([$newUserId]);
        $this->PDO->prepare("DELETE FROM User WHERE id_User = ?")->execute([$newUserId]);
    }

    /**
     * Test MAN02: Attribution developer valide
     */
    public function testAssignUserAsDeveloper()
    {
        $result = $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        $this->assertTrue($result, 'Developer assignment should succeed');

        // Vérifier en base
        $assignments = $this->model->getProjectAssignments($this->testProjectId);
        $found = false;
        foreach ($assignments as $assignment) {
            if ($assignment['User_id_User'] == $this->testUserId && $assignment['role_in_project'] == 'developer') {
                $found = true;
                $this->assertEquals('active', $assignment['status']);
                break;
            }
        }

        $this->assertTrue($found, 'Developer assignment should be found in database');
    }

    /**
     * Test MAN03: Attribution multiple rôles (doit échouer)
     */
    public function testAssignUserMultipleRoles()
    {
        // Première assignation
        $result1 = $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);
        $this->assertTrue($result1, 'First assignment should succeed');

        // Deuxième assignation (doit échouer)
        $result2 = $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'manager', $this->testManagerId);

        $this->assertIsArray($result2, 'Second assignment should return error array');
        $this->assertArrayHasKey('error', $result2);
        $this->assertStringContainsString('already assigned', $result2['error']);
    }

    /**
     * Test MAN04: Désattribution
     */
    public function testRemoveAssignment()
    {
        // Assigner d'abord
        $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        // Puis retirer
        $result = $this->model->removeAssignment($this->testUserId, $this->testProjectId, $this->testManagerId);

        $this->assertTrue($result, 'Assignment removal should succeed');

        // Vérifier que le statut est 'inactive'
        $stmt = $this->PDO->prepare("
            SELECT status FROM Manage
            WHERE User_id_User = ? AND Project_id_Project = ?
        ");
        $stmt->execute([$this->testUserId, $this->testProjectId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('inactive', $assignment['status']);
    }

    /**
     * Test MAN05: Attribution avec utilisateur inexistant
     */
    public function testAssignNonExistentUser()
    {
        $result = $this->model->assignUserToProject(999999, $this->testProjectId, 'developer', $this->testManagerId);

        $this->assertIsArray($result, 'Should return error array');
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('User does not exist', $result['error']);
    }

    /**
     * Test MAN06: Attribution avec projet inexistant
     */
    public function testAssignToNonExistentProject()
    {
        $result = $this->model->assignUserToProject($this->testUserId, 999999, 'developer', $this->testManagerId);

        $this->assertIsArray($result, 'Should return error array');
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Project does not exist', $result['error']);
    }

    /**
     * Test MAN07: Attribution avec rôle invalide
     */
    public function testAssignInvalidRole()
    {
        $result = $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'invalid_role', $this->testManagerId);

        $this->assertIsArray($result, 'Should return error array');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid role', $result['error']);
    }

    /**
     * Test MAN08: Liste équipe projet
     */
    public function testGetTeamByProject()
    {
        // Assigner quelques utilisateurs
        $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        $assignments = $this->model->getProjectAssignments($this->testProjectId);

        $this->assertIsArray($assignments);
        $this->assertGreaterThanOrEqual(2, count($assignments), 'Should have at least manager + developer');

        // Vérifier la structure des données
        foreach ($assignments as $assignment) {
            $this->assertArrayHasKey('User_id_User', $assignment);
            $this->assertArrayHasKey('role_in_project', $assignment);
            $this->assertArrayHasKey('Username', $assignment);
            $this->assertArrayHasKey('status', $assignment);
            $this->assertEquals('active', $assignment['status']);
        }
    }

    /**
     * Test: Permissions - Utilisateur non autorisé ne peut pas assigner
     */
    public function testUnauthorizedUserCannotAssign()
    {
        // Créer un utilisateur simple (pas manager)
        $stmt = $this->PDO->prepare("
            INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at)
            VALUES (?, ?, ?, 'user', NOW())
        ");
        $stmt->execute(['unauthorized_' . time(), 'unauth_' . time() . '@test.local', password_hash('test123', PASSWORD_DEFAULT)]);
        $unauthorizedUserId = $this->PDO->lastInsertId();

        $result = $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $unauthorizedUserId);

        $this->assertIsArray($result, 'Should return error array');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('permission', $result['error']);

        // Cleanup
        $this->PDO->prepare("DELETE FROM User WHERE id_User = ?")->execute([$unauthorizedUserId]);
    }

    /**
     * Test: Changement de rôle
     */
    public function testChangeUserRole()
    {
        // Assigner comme developer d'abord
        $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        // Changer en manager
        $result = $this->model->changeUserRole($this->testUserId, $this->testProjectId, 'manager', $this->testManagerId);

        $this->assertTrue($result, 'Role change should succeed');

        // Vérifier le changement
        $assignments = $this->model->getProjectAssignments($this->testProjectId);
        foreach ($assignments as $assignment) {
            if ($assignment['User_id_User'] == $this->testUserId) {
                $this->assertEquals('manager', $assignment['role_in_project']);
                break;
            }
        }
    }

    /**
     * Test: Récupérer les projets d'un utilisateur
     */
    public function testGetUserProjects()
    {
        // Assigner l'utilisateur au projet
        $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        $projects = $this->model->getUserProjects($this->testUserId);

        $this->assertIsArray($projects);
        $this->assertGreaterThan(0, count($projects));

        $found = false;
        foreach ($projects as $project) {
            if ($project['Project_id_Project'] == $this->testProjectId) {
                $found = true;
                $this->assertEquals('developer', $project['role_in_project']);
                $this->assertArrayHasKey('project_name', $project);
                break;
            }
        }

        $this->assertTrue($found, 'User should be assigned to the test project');
    }

    /**
     * Test: Récupérer les projets d'un utilisateur filtré par rôle
     */
    public function testGetUserProjectsByRole()
    {
        $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        // Récupérer seulement les projets où il est developer
        $devProjects = $this->model->getUserProjects($this->testUserId, 'developer');
        $managerProjects = $this->model->getUserProjects($this->testUserId, 'manager');

        $this->assertGreaterThan(0, count($devProjects), 'Should have developer projects');
        $this->assertEquals(0, count($managerProjects), 'Should have no manager projects');

        foreach ($devProjects as $project) {
            $this->assertEquals('developer', $project['role_in_project']);
        }
    }

    /**
     * Test: Récupérer les utilisateurs disponibles
     */
    public function testGetAvailableUsers()
    {
        $availableUsers = $this->model->getAvailableUsers($this->testProjectId);

        $this->assertIsArray($availableUsers);

        // Vérifier que le manager assigné n'est pas dans la liste
        foreach ($availableUsers as $user) {
            $this->assertNotEquals($this->testManagerId, $user['id_User'], 'Assigned manager should not be available');
        }

        // Vérifier que testUserId est disponible (pas encore assigné)
        $found = false;
        foreach ($availableUsers as $user) {
            if ($user['id_User'] == $this->testUserId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Unassigned user should be available');
    }

    /**
     * Test: Statistiques d'attribution de projet
     */
    public function testGetProjectStats()
    {
        // Assigner un developer
        $this->model->assignUserToProject($this->testUserId, $this->testProjectId, 'developer', $this->testManagerId);

        $stats = $this->model->getProjectStats($this->testProjectId);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_assignments', $stats);
        $this->assertArrayHasKey('managers_count', $stats);
        $this->assertArrayHasKey('developers_count', $stats);

        $this->assertEquals(2, $stats['total_assignments']); // 1 manager + 1 developer
        $this->assertEquals(1, $stats['managers_count']);
        $this->assertEquals(1, $stats['developers_count']);
    }

    /**
     * Test: Retrait d'assignation inexistante
     */
    public function testRemoveNonExistentAssignment()
    {
        $result = $this->model->removeAssignment($this->testUserId, $this->testProjectId, $this->testManagerId);

        $this->assertIsArray($result, 'Should return error array');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }
}
?>