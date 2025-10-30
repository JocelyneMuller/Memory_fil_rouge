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
            $id = filter_input(INPUT_GET, 'id');
            return $this->show($id);
        case 'create':
            // Implement create logic here
            return ['message' => 'Create action not implemented yet'];
        case 'archivate':
            // Implement archive logic here
            return ['message' => 'Archive action not implemented yet'];
        default:
            return ['error' => 'Invalid action'];
    }
}

public function show($id)
{
    if ($id) {
                $model = new Projects_model($this->PDO);
                return $model->getProjectById($id);
            } else {
                return ['error' => 'Project ID is required'];
            }

}
}