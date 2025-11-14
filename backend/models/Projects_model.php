<?php

class Projects_model {
    private $PDO;

    public function __construct($PDO)
    {
        $this->PDO = $PDO;
    }

    public function getAllProjects()
    {
        $stmt = $this->PDO->prepare("SELECT * FROM Project");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    public function getProjectById($id)
    {
        $stmt = $this->PDO->prepare("SELECT * FROM Project WHERE id_Project = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProjectsByCategory($category)
    {
        $stmt = $this->PDO->prepare("SELECT * FROM Project WHERE Category_id_Category = :category");
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
            return $stmt->rowCount() > 0;
        }
        
        return false;
    }
}