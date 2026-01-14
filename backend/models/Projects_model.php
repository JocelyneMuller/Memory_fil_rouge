<?php
require_once __DIR__ . '/Cache_model.php';

class Projects_model {
    private $PDO;
    private $cache;

    public function __construct($PDO)
    {
        $this->PDO = $PDO;
        $this->cache = new Cache_model();
    }

    public function getAllProjects()
    {
        // Tentative de récupération depuis le cache Redis
        $cacheKey = 'all_projects';
        $cachedResult = $this->cache->get($cacheKey);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Si pas en cache, exécution de la requête SQL
        $stmt = $this->PDO->prepare("
            SELECT
                p.*,
                c.Name_Unique as Category_Name
            FROM Project p
            LEFT JOIN Category c ON p.Category_id_Category = c.id_Category
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mise en cache du résultat pour 30 minutes
        $this->cache->set($cacheKey, $result, 1800);

        return $result;
    } 
    public function getProjectById($id)
    {
        // Cache spécifique au projet par ID
        $cacheKey = "project:$id";
        $cachedResult = $this->cache->get($cacheKey);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $stmt = $this->PDO->prepare("
            SELECT
                p.*,
                c.Name_Unique as Category_Name
            FROM Project p
            LEFT JOIN Category c ON p.Category_id_Category = c.id_Category
            WHERE p.id_Project = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mise en cache pour 1 heure si projet trouvé
        if ($result) {
            $this->cache->set($cacheKey, $result, 3600);
        }

        return $result;
    }

    public function getProjectsByCategory($category)
    {
        $stmt = $this->PDO->prepare("
            SELECT 
                p.*,
                c.Name_Unique as Category_Name
            FROM Project p
            LEFT JOIN Category c ON p.Category_id_Category = c.id_Category
            WHERE p.Category_id_Category = :category
        ");
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un nouveau projet en base de données
     * Vérifie l'existence de la catégorie et l'unicité du nom avant insertion
     */
    public function createProject($name, $description, $categoryId)
    {
        // ============================================
        // ÉTAPE 1 : VÉRIFICATION DE LA CATÉGORIE
        // ============================================
        
        // Avant de créer un projet, on vérifie que la catégorie existe vraiment
        // Ceci évite les erreurs de clé étrangère (Foreign Key)
        $checkCategory = $this->PDO->prepare("SELECT id_Category FROM Category WHERE id_Category = :category_id");
        $checkCategory->bindParam(':category_id', $categoryId);
        $checkCategory->execute();
        
        // Si aucune ligne trouvée, la catégorie n'existe pas
        if ($checkCategory->rowCount() === 0) {
            throw new Exception('Category does not exist');
        }

        // ============================================
        // ÉTAPE 2 : VÉRIFICATION DE L'UNICITÉ DU NOM
        // ============================================
        
        // Dans votre BDD, Name_Unique doit être unique (contrainte UNIQUE)
        // On vérifie manuellement avant l'insertion pour donner un message clair
        $checkName = $this->PDO->prepare("SELECT id_Project FROM Project WHERE Name_Unique = :name");
        $checkName->bindParam(':name', $name);
        $checkName->execute();
        
        // Si une ligne est trouvée, le nom existe déjà
        if ($checkName->rowCount() > 0) {
            throw new Exception('Project name already exists');
        }

        // ============================================
        // ÉTAPE 3 : INSERTION DU NOUVEAU PROJET
        // ============================================
        
        // Requête préparée pour insérer le projet
        // CURDATE() = fonction MySQL qui insère la date du jour automatiquement
        // NULL pour Archive_date = projet actif (non archivé)
        $stmt = $this->PDO->prepare("
            INSERT INTO Project (Name_Unique, Description, Date_of_creation, Archive_date, Category_id_Category) 
            VALUES (:name, :description, CURDATE(), NULL, :category_id)
        ");
        
        // Liaison des paramètres (sécurité contre l'injection SQL)
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category_id', $categoryId);
        
        // Exécution de la requête
        if ($stmt->execute()) {
            // Invalidation du cache après création d'un projet
            $this->cache->delete('all_projects');
            $this->cache->invalidateTableCache('Project');

            // Succès : retourne l'ID du projet qui vient d'être créé
            // lastInsertId() donne l'ID auto-généré par MySQL
            return $this->PDO->lastInsertId();
        }

        // Échec de l'insertion (cas rare)
        return false;
    }

    /**
     * Archiver un projet en mettant Archive_date à la date du jour
     * 
     * @param int $id ID du projet à archiver
     * @return bool true si l'archivage a réussi, false si le projet n'existe pas
     */
    public function archiveProject($id)
    {
        $checkStmt = $this->PDO->prepare("
            SELECT id_Project, Archive_date 
            FROM Project 
            WHERE id_Project = :id
        ");
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $project = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            return false;
        }
        
        if ($project['Archive_date'] !== null) {
            throw new Exception('Project is already archived');
        }
        
        $stmt = $this->PDO->prepare("
            UPDATE Project 
            SET Archive_date = CURDATE() 
            WHERE id_Project = :id
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Invalidation du cache après archivage
            $this->cache->delete('all_projects');
            $this->cache->delete("project:$id");
            $this->cache->invalidateTableCache('Project');

            return $stmt->rowCount() > 0;
        }

        return false;
    }
}