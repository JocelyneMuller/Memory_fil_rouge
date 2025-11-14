<template>
  <div id="app">
    <header>
      <h1>Gestion des Projets Memory</h1>
    </header>
    
    <div class="content-wrapper">
      <!-- Formulaire de création -->
      <aside class="sidebar">
        <ProjectForm @projectCreated="onProjectCreated" />
      </aside>
      
      <!-- Liste des projets -->
      <main class="main-content">
        <ProjectList :key="refreshKey" />
      </main>
    </div>
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
      this.refreshKey++;
      console.log('Nouveau projet créé:', result);
    }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

#app {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  min-height: 100vh;
  background-color: #f5f5f5;
}

header {
  background-color: #fff;
  border-bottom: 2px solid #000;
  padding: 20px;
  text-align: center;
}

header h1 {
  font-size: 28px;
  font-weight: bold;
  color: #000;
}

.content-wrapper {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 30px;
  max-width: 1600px;
  margin: 0 auto;
  padding: 30px;
}

.sidebar {
  position: sticky;
  top: 30px;
  height: fit-content;
}

.main-content {
  min-width: 0;
}

/* Responsive : empiler verticalement sur mobile */
@media (max-width: 1024px) {
  .content-wrapper {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .sidebar {
    position: static;
  }
}
</style>