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
    fetch('http://localhost:8888/PFR/Memory/backend/?loc=projects&action=list') // ton API MAMP
      .then(response => response.json())
      .then(data => {
        this.projects = data;
        this.loading = false;
      })
      .catch(error => {
        console.error('Erreur :', error);
        this.loading = false;
      });
  }
}
</script>