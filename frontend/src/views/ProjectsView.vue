<template>
  <div class="projects-view">
    <ProjectList :key="refreshKey" />
  </div>
</template>

<script>
import ProjectList from '../components/projects/ProjectList.vue'

export default {
  name: 'ProjectsView',
  
  components: {
    ProjectList
  },
  
  data() {
    return {
      refreshKey: 0
    }
  }
}
</script>

<style scoped>
.projects-view {
  min-height: 100vh;
}
</style>
