<template>
  <div class="projects">
    <h1>Liste des projets</h1>

    <div v-if="loading">Chargement...</div>

    <ul v-else>
      <li v-for="project in projects" :key="project.id">
        <strong>{{ project.Name_Unique }}</strong> — {{ project.Description }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: "ProjectList",
  data() {
    return {
      projects: [],
      loading: true
    }
  },
  mounted() {
    this.loadProjects();
  },

  methods: {
    async loadProjects() {
      console.log('Chargement des projets...');
      this.loading = true;
      const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
      const endpoint = `${baseUrl}?loc=projects&action=list`;
      try {
        const response = await fetch(endpoint);
        console.log('Réponse projets:', response.status);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log('Projets chargés:', data);
        if (!Array.isArray(data)) {
          throw new Error('Format inattendu: la réponse doit être un tableau de projets');
        }
        this.projects = data;
      } catch (error) {
        console.error('Erreur chargement projets:', error);
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>