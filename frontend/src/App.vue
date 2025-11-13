<template>
  <div id="app">
    <header>
      <h1>Gestion des Projets Memory</h1>
    </header>
    
    <!-- Formulaire de création -->
    <ProjectForm @projectCreated="onProjectCreated" />
    
    <!-- Liste des projets -->
    <ProjectList :key="refreshKey" />
  </div>
</template>

<script>
import ProjectList from './components/projects/ProjectList.vue'
import ProjectForm from './components/projects/ProjectForm.vue'

export default {
  components: { 
    ProjectList,
    ProjectForm 
  },
  
  data() {
    return {
      refreshKey: 0
    }
  },
  
  methods: {
    onProjectCreated(result) {
      // Rafraîchit la liste des projets après création
      this.refreshKey++;
      console.log('Nouveau projet créé:', result);
    }
  }
}
</script>

<style>
#app {
  font-family: Arial, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

header {
  text-align: center;
  margin-bottom: 30px;
}

header h1 {
  color: #2c3e50;
  border-bottom: 2px solid #3498db;
  padding-bottom: 10px;
}
</style>