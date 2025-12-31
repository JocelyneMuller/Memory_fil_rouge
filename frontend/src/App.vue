<template>
  <div id="app">
    <!-- Sidebar bordeaux (caché sur page de connexion) -->
    <Sidebar v-if="showAnySidebar && !isLoginPage" />
    
    <!-- Contenu principal -->
    <main :class="mainClass">
      <router-view />
    </main>
  </div>
</template>

<script>
import Sidebar from './components/layout/Sidebar.vue'

export default {
  components: { 
    Sidebar
  },
  
  computed: {
    isLoginPage() {
      return this.$route.path === '/login'
    },
    
    isDashboard() {
      return this.$route.path === '/'
    },
    
    isProjectsListPage() {
      return this.$route.path === '/projects'
    },
    
    isProjectDetailPage() {
      const path = this.$route.path
      return path.startsWith('/projects/') && path !== '/projects/create'
    },
    
    isContentPage() {
      const path = this.$route.path
      return path.startsWith('/competences') || 
             path.startsWith('/notes')
    },
    
    isCreatePage() {
      return this.$route.path === '/projects/create'
    },
    
    showDarkSidebar() {
      return this.isDashboard || this.isContentPage
    },
    
    showLightSidebar() {
      return false
    },
    
    showAnySidebar() {
      return this.showDarkSidebar && !this.isProjectsListPage && !this.isProjectDetailPage
    },
    
    mainClass() {
      if (this.isProjectsListPage || this.isProjectDetailPage) return 'main-content'
      if (this.showDarkSidebar) return 'main-content with-dark-sidebar'
      return 'main-content'
    }
  }
}
</script>

<style>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background-color: #f5f5f5;
}

#app {
  min-height: 100vh;
  display: flex;
}

.main-content {
  flex: 1;
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}

.main-content.with-dark-sidebar {
  margin-left: 350px;
}

.main-content.with-light-sidebar {
  margin-left: 320px;
}

/* Responsive */
@media (max-width: 768px) {
  .main-content.with-dark-sidebar,
  .main-content.with-light-sidebar {
    margin-left: 70px;
  }
}
</style>