<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/User_model.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Tests unitaires pour le modèle User_model
 * Validation de la compétence CDA "Préparer et exécuter les plans de tests"
 *
 * Tests couverts :
 * - Création et authentification d'utilisateurs
 * - Validation des données et contraintes d'unicité
 * - Sécurité des mots de passe (hashage, vérification)
 * - Gestion des rôles et permissions
 * - Opérations CRUD sur les utilisateurs
 */
class UserModelTest extends TestCase
{
    private $PDO;
    private $model;
    private $testUserIds = []; // Pour nettoyer les données de test

    protected function setUp(): void
    {
        // Initialisation de la base de données de test
        require_once __DIR__ . '/../config/database.php';
        global $PDO;

        if (!$PDO instanceof PDO) {
            $this->fail('Database connection failed. Check config/database.php');
        }

        $this->PDO = $PDO;
        $this->model = new User_model($this->PDO);
    }

    protected function tearDown(): void
    {
        // Nettoyer les utilisateurs de test créés
        foreach ($this->testUserIds as $userId) {
            try {
                // Supprimer d'abord les relations dans Manage
                $this->PDO->prepare("DELETE FROM Manage WHERE User_id_User = ?")->execute([$userId]);
                // Puis supprimer l'utilisateur
                $this->PDO->prepare("DELETE FROM User WHERE id_User = ?")->execute([$userId]);
            } catch (Exception $e) {
                error_log('Cleanup error: ' . $e->getMessage());
            }
        }
        $this->testUserIds = [];
    }

    /**
     * Helper pour créer un utilisateur de test et tracker son ID
     */
    private function createTestUser($userData = [])
    {
        $defaultData = [
            'username' => 'test_user_' . time() . '_' . rand(1000, 9999),
            'email' => 'test_' . time() . '_' . rand(1000, 9999) . '@test.local',
            'password' => 'test123456',
            'role' => 'user'
        ];

        $userData = array_merge($defaultData, $userData);
        $result = $this->model->create($userData);

        if ($result['success'] && isset($result['user']['id_User'])) {
            $this->testUserIds[] = $result['user']['id_User'];
        }

        return $result;
    }

    /**
     * Test USR01: Création utilisateur valide
     */
    public function testCreateUserValid()
    {
        $userData = [
            'username' => 'validuser',
            'email' => 'valid@test.local',
            'password' => 'securepassword123',
            'role' => 'user'
        ];

        $result = $this->createTestUser($userData);

        $this->assertTrue($result['success'], 'User creation should succeed');
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($userData['username'], $result['user']['Username']);
        $this->assertEquals($userData['email'], $result['user']['Email_Unique']);
        $this->assertEquals($userData['role'], $result['user']['Role']);
        $this->assertArrayNotHasKey('Password_hash', $result['user'], 'Password hash should not be returned');
    }

    /**
     * Test USR02: Email dupliqué (doit échouer)
     */
    public function testCreateUserDuplicateEmail()
    {
        $email = 'duplicate@test.local';

        // Créer le premier utilisateur
        $result1 = $this->createTestUser(['email' => $email]);
        $this->assertTrue($result1['success']);

        // Tenter de créer un second avec le même email
        $result2 = $this->model->create([
            'username' => 'differentuser',
            'email' => $email,
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->assertFalse($result2['success'], 'Duplicate email should fail');
        $this->assertArrayHasKey('error', $result2);
        $this->assertStringContainsString('email est déjà utilisé', $result2['error']);
    }

    /**
     * Test USR03: Username dupliqué (doit échouer)
     */
    public function testCreateUserDuplicateUsername()
    {
        $username = 'duplicateuser';

        // Créer le premier utilisateur
        $result1 = $this->createTestUser(['username' => $username]);
        $this->assertTrue($result1['success']);

        // Tenter de créer un second avec le même username
        $result2 = $this->model->create([
            'username' => $username,
            'email' => 'different@test.local',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->assertFalse($result2['success'], 'Duplicate username should fail');
        $this->assertArrayHasKey('error', $result2);
        $this->assertStringContainsString('nom d\'utilisateur est déjà pris', $result2['error']);
    }

    /**
     * Test USR04: Authentification avec credentials valides
     */
    public function testAuthenticationValid()
    {
        $email = 'auth@test.local';
        $password = 'testpassword123';

        // Créer l'utilisateur
        $result = $this->createTestUser([
            'email' => $email,
            'password' => $password
        ]);
        $this->assertTrue($result['success']);

        // Récupérer l'utilisateur par email
        $user = $this->model->findByEmail($email);
        $this->assertNotFalse($user, 'User should be found by email');

        // Vérifier le mot de passe
        $isValidPassword = $this->model->verifyPassword($password, $user['Password_hash']);
        $this->assertTrue($isValidPassword, 'Password verification should succeed');
    }

    /**
     * Test USR05: Mot de passe incorrect
     */
    public function testAuthenticationInvalidPassword()
    {
        $email = 'authfail@test.local';
        $correctPassword = 'correctpassword';
        $wrongPassword = 'wrongpassword';

        // Créer l'utilisateur
        $result = $this->createTestUser([
            'email' => $email,
            'password' => $correctPassword
        ]);
        $this->assertTrue($result['success']);

        // Récupérer l'utilisateur
        $user = $this->model->findByEmail($email);
        $this->assertNotFalse($user);

        // Tenter avec un mauvais mot de passe
        $isValidPassword = $this->model->verifyPassword($wrongPassword, $user['Password_hash']);
        $this->assertFalse($isValidPassword, 'Wrong password should fail verification');
    }

    /**
     * Test USR06: Utilisateur inexistant
     */
    public function testFindNonExistentUser()
    {
        $user = $this->model->findByEmail('nonexistent@test.local');
        $this->assertFalse($user, 'Non-existent user should return false');

        $userById = $this->model->findById(999999);
        $this->assertFalse($userById, 'Non-existent user ID should return false');

        $userByUsername = $this->model->findByUsername('nonexistentuser');
        $this->assertFalse($userByUsername, 'Non-existent username should return false');
    }

    /**
     * Test USR07: Validation token JWT (simulation)
     */
    public function testFindUserById()
    {
        $result = $this->createTestUser();
        $this->assertTrue($result['success']);

        $userId = $result['user']['id_User'];
        $foundUser = $this->model->findById($userId);

        $this->assertNotFalse($foundUser, 'User should be found by ID');
        $this->assertEquals($userId, $foundUser['id_User']);
        $this->assertEquals($result['user']['Username'], $foundUser['Username']);
    }

    /**
     * Test: Validation des données - Champs requis
     */
    public function testCreateUserMissingFields()
    {
        // Test sans username
        $result1 = $this->model->create([
            'email' => 'test@test.local',
            'password' => 'password123'
        ]);
        $this->assertFalse($result1['success']);
        $this->assertStringContainsString('requis', $result1['error']);

        // Test sans email
        $result2 = $this->model->create([
            'username' => 'testuser',
            'password' => 'password123'
        ]);
        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('requis', $result2['error']);

        // Test sans password
        $result3 = $this->model->create([
            'username' => 'testuser',
            'email' => 'test@test.local'
        ]);
        $this->assertFalse($result3['success']);
        $this->assertStringContainsString('requis', $result3['error']);
    }

    /**
     * Test: Validation format email
     */
    public function testCreateUserInvalidEmail()
    {
        $result = $this->model->create([
            'username' => 'testuser',
            'email' => 'invalid-email-format',
            'password' => 'password123'
        ]);

        $this->assertFalse($result['success'], 'Invalid email should fail');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('email invalide', $result['error']);
    }

    /**
     * Test: Validation longueur mot de passe
     */
    public function testCreateUserPasswordTooShort()
    {
        $result = $this->model->create([
            'username' => 'testuser',
            'email' => 'test@test.local',
            'password' => '12345' // 5 caractères seulement
        ]);

        $this->assertFalse($result['success'], 'Short password should fail');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('6 caractères', $result['error']);
    }

    /**
     * Test: Validation rôle utilisateur
     */
    public function testCreateUserInvalidRole()
    {
        $result = $this->model->create([
            'username' => 'testuser',
            'email' => 'test@test.local',
            'password' => 'password123',
            'role' => 'invalid_role'
        ]);

        $this->assertFalse($result['success'], 'Invalid role should fail');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Rôle invalide', $result['error']);
    }

    /**
     * Test: Création d'admin
     */
    public function testCreateAdminUser()
    {
        $result = $this->createTestUser([
            'username' => 'testadmin',
            'role' => 'admin'
        ]);

        $this->assertTrue($result['success'], 'Admin user creation should succeed');
        $this->assertEquals('admin', $result['user']['Role']);
    }

    /**
     * Test: Récupération de tous les utilisateurs
     */
    public function testGetAllUsers()
    {
        // Créer quelques utilisateurs de test
        $this->createTestUser(['username' => 'user1']);
        $this->createTestUser(['username' => 'user2']);

        $allUsers = $this->model->getAll();

        $this->assertIsArray($allUsers);
        $this->assertGreaterThan(0, count($allUsers), 'Should return at least the test users');

        // Vérifier la structure des données
        if (count($allUsers) > 0) {
            $firstUser = $allUsers[0];
            $this->assertArrayHasKey('id_User', $firstUser);
            $this->assertArrayHasKey('Username', $firstUser);
            $this->assertArrayHasKey('Email_Unique', $firstUser);
            $this->assertArrayHasKey('Role', $firstUser);
            $this->assertArrayNotHasKey('Password_hash', $firstUser, 'Password hash should not be included');
        }
    }

    /**
     * Test: Mise à jour du mot de passe
     */
    public function testUpdatePassword()
    {
        $result = $this->createTestUser([
            'password' => 'oldpassword123'
        ]);
        $this->assertTrue($result['success']);

        $userId = $result['user']['id_User'];
        $newPassword = 'newpassword456';

        // Mettre à jour le mot de passe
        $updateResult = $this->model->updatePassword($userId, $newPassword);
        $this->assertTrue($updateResult, 'Password update should succeed');

        // Vérifier que l'ancien mot de passe ne fonctionne plus
        $user = $this->model->findById($userId);
        $oldPasswordValid = $this->model->verifyPassword('oldpassword123', $user['Password_hash']);
        $this->assertFalse($oldPasswordValid, 'Old password should not work');

        // Vérifier que le nouveau mot de passe fonctionne
        $newPasswordValid = $this->model->verifyPassword($newPassword, $user['Password_hash']);
        $this->assertTrue($newPasswordValid, 'New password should work');
    }

    /**
     * Test: Mise à jour mot de passe trop court
     */
    public function testUpdatePasswordTooShort()
    {
        $result = $this->createTestUser();
        $this->assertTrue($result['success']);

        $userId = $result['user']['id_User'];
        $updateResult = $this->model->updatePassword($userId, '123'); // Trop court

        $this->assertFalse($updateResult, 'Short password update should fail');
    }

    /**
     * Test: Suppression d'utilisateur
     */
    public function testDeleteUser()
    {
        $result = $this->createTestUser();
        $this->assertTrue($result['success']);

        $userId = $result['user']['id_User'];

        // Supprimer l'utilisateur
        $deleteResult = $this->model->delete($userId);
        $this->assertTrue($deleteResult, 'User deletion should succeed');

        // Vérifier que l'utilisateur n'existe plus
        $deletedUser = $this->model->findById($userId);
        $this->assertFalse($deletedUser, 'Deleted user should not be found');

        // Retirer de la liste de nettoyage puisqu'il est déjà supprimé
        $this->testUserIds = array_diff($this->testUserIds, [$userId]);
    }

    /**
     * Test: Protection contre suppression de l'admin principal
     */
    public function testCannotDeleteMainAdmin()
    {
        $deleteResult = $this->model->delete(1); // ID 1 = admin principal
        $this->assertFalse($deleteResult, 'Should not be able to delete main admin');

        // Vérifier que l'admin existe toujours
        $admin = $this->model->findById(1);
        $this->assertNotFalse($admin, 'Main admin should still exist');
    }

    /**
     * Test: Recherche par username
     */
    public function testFindByUsername()
    {
        $username = 'uniqueusername';
        $result = $this->createTestUser(['username' => $username]);
        $this->assertTrue($result['success']);

        $foundUser = $this->model->findByUsername($username);
        $this->assertNotFalse($foundUser, 'User should be found by username');
        $this->assertEquals($username, $foundUser['Username']);
        $this->assertEquals($result['user']['id_User'], $foundUser['id_User']);
    }

    /**
     * Test: Sécurité du hashage des mots de passe
     */
    public function testPasswordHashing()
    {
        $password = 'testpassword123';
        $result = $this->createTestUser(['password' => $password]);
        $this->assertTrue($result['success']);

        // Récupérer l'utilisateur avec le hash
        $user = $this->model->findById($result['user']['id_User']);

        // Vérifier que le mot de passe est bien hashé
        $this->assertNotEquals($password, $user['Password_hash'], 'Password should be hashed');
        $this->assertStringStartsWith('$2y$', $user['Password_hash'], 'Should use bcrypt hash');

        // Vérifier que le hash peut être validé
        $isValid = $this->model->verifyPassword($password, $user['Password_hash']);
        $this->assertTrue($isValid, 'Hashed password should be verifiable');
    }

    /**
     * Test de performance: Création multiple d'utilisateurs
     */
    public function testBulkUserCreation()
    {
        $startTime = microtime(true);
        $createdUsers = [];

        // Créer 10 utilisateurs
        for ($i = 1; $i <= 10; $i++) {
            $result = $this->createTestUser([
                'username' => "bulkuser$i",
                'email' => "bulk$i@test.local"
            ]);
            $this->assertTrue($result['success'], "Bulk user $i should be created successfully");
            $createdUsers[] = $result['user']['id_User'];
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Vérifier que tous les utilisateurs sont créés
        $this->assertEquals(10, count($createdUsers), 'Should create 10 users');

        // Performance : moins de 2 secondes pour 10 utilisateurs
        $this->assertLessThan(2.0, $executionTime, 'Bulk creation should be under 2 seconds');

        error_log("Bulk user creation time: " . round($executionTime * 1000, 2) . "ms for 10 users");
    }
}
?>