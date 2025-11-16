<template>
  <div id="app">
    <!-- Formulaire de création en pleine page -->
    <ProjectForm @projectCreated="onProjectCreated" />
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

</style>