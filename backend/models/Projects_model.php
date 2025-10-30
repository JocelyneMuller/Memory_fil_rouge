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
}