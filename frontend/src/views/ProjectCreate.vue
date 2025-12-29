<template>
  <div class="project-create-view">
    <ProjectForm @projectCreated="handleProjectCreated" />
  </div>
</template>

<script>
import ProjectForm from '../components/projects/ProjectForm.vue'

export default {
  name: 'ProjectCreate',
  
  components: {
    ProjectForm
  },
  
  methods: {
    handleProjectCreated(result) {
      console.log('Projet créé:', result)
    }
  }
}
</script>

<style scoped>
.project-create-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
</style>
