<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Projects_model.php';
require_once __DIR__ . '/../config/database.php';

class ProjectsModelTest extends TestCase
{
    private $PDO;
    private $model;
    private $testProjectId;
    private $testCategoryId;

    protected function setUp(): void
    {
        // Charger la connexion à la base de données
        require_once __DIR__ . '/../config/database.php';
        global $PDO;
        
        if (!$PDO instanceof PDO) {
            $this->fail('Database connection failed. Check config/database.php');
        }
        
        $this->PDO = $PDO;
        $this->model = new Projects_model($this->PDO);
        
        $categoryStmt = $this->PDO->query("SELECT id_Category FROM Category LIMIT 1");
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            $this->testCategoryId = $category['id_Category'];
        } else {
            $insertCat = $this->PDO->prepare("INSERT INTO Category (Name_Unique) VALUES (?)");
            $insertCat->execute(['Test Category ' . time()]);
            $this->testCategoryId = $this->PDO->lastInsertId();
        }
        
        $this->testProjectId = $this->model->createProject(
            'Test Project ' . time(),
            'Description for testing archive functionality',
            $this->testCategoryId
        );
    }

    /**
     * Test : un projet créé doit avoir Archive_date = NULL (actif)
     */
    public function testNewProjectHasNullArchiveDate()
    {
        $project = $this->model->getProjectById($this->testProjectId);
        
        $this->assertNull(
            $project['Archive_date'],
            'Un nouveau projet devrait avoir Archive_date à NULL'
        );
    }

    /**
     * Test : archiver un projet actif doit fonctionner
     */
    public function testArchiveActiveProject()
    {
        $result = $this->model->archiveProject($this->testProjectId);
        
        $this->assertTrue($result, 'L\'archivage devrait retourner true');
        
        $project = $this->model->getProjectById($this->testProjectId);
        $this->assertEquals(
            date('Y-m-d'),
            $project['Archive_date'],
            'Archive_date devrait être la date du jour après archivage'
        );
    }

    /**
     * Test : archiver un projet inexistant doit retourner false
     */
    public function testArchiveNonExistentProject()
    {
        $result = $this->model->archiveProject(999999);
        
        $this->assertFalse(
            $result,
            'Archiver un projet inexistant devrait retourner false'
        );
    }

    /**
     * Test : archiver un projet déjà archivé doit lancer une exception
     */
    public function testArchiveAlreadyArchivedProject()
    {
        $this->model->archiveProject($this->testProjectId);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already archived');
        
        $this->model->archiveProject($this->testProjectId);
    }

    /**
     * Test : vérifier que getAllProjects retourne aussi les projets archivés
     */
    public function testGetAllProjectsIncludesArchivedProjects()
    {
        $this->model->archiveProject($this->testProjectId);
        
        $projects = $this->model->getAllProjects();
        
        $found = false;
        foreach ($projects as $project) {
            if ($project['id_Project'] == $this->testProjectId) {
                $found = true;
                $this->assertNotNull(
                    $project['Archive_date'],
                    'Le projet archivé devrait avoir une Archive_date'
                );
                break;
            }
        }
        
        $this->assertTrue($found, 'Le projet archivé devrait être dans getAllProjects()');
    }

    protected function tearDown(): void
    {
        if ($this->testProjectId) {
            $stmt = $this->PDO->prepare("DELETE FROM Project WHERE id_Project = :id");
            $stmt->bindParam(':id', $this->testProjectId);
            $stmt->execute();
        }
    }
}
