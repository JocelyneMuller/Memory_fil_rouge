<template>
  <div class="projects-layout">
    <!-- Sidebar contextuelle -->
    <aside class="projects-sidebar">
      <div class="sidebar-header">
        <router-link to="/" class="logo-link">
          <img src="/logo_memory.png" alt="Memory Logo" class="sidebar-logo">
          <span class="logo-text">Memory</span>
        </router-link>
      </div>

      <nav class="filter-nav">
        <button 
          @click="filterStatus = 'all'"
          :class="['filter-btn', { active: filterStatus === 'all' }]"
        >
          All ({{ allProjectsCount }})
        </button>
        <button 
          @click="filterStatus = 'active'"
          :class="['filter-btn', { active: filterStatus === 'active' }]"
        >
          Actives ({{ activeProjectsCount }})
        </button>
        <button 
          @click="filterStatus = 'archived'"
          :class="['filter-btn', { active: filterStatus === 'archived' }]"
        >
          Archived ({{ archivedProjectsCount }})
        </button>
        <router-link to="/projects/create" class="filter-btn">
          Create
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info" v-if="authStore.user">
          <div class="user-avatar">
            {{ (authStore.user.email || authStore.user.Username || '?').charAt(0).toUpperCase() }}
          </div>
          <div class="user-details">
            <span class="user-email">{{ authStore.user.email || authStore.user.Username }}</span>
            <span class="user-role">{{ authStore.user.role || authStore.user.Role }}</span>
          </div>
          <button @click="logout" class="logout-btn" title="Déconnexion">
            🚪
          </button>
        </div>
      </div>
    </aside>

    <!-- Contenu principal -->
    <main class="projects-main">
      <!-- Header -->
      <div class="main-header">
        <div class="badge-projects">Projects</div>
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input type="text" placeholder="Search..." v-model="searchQuery">
        </div>
      </div>

      <!-- Messages de feedback -->
      <div v-if="message" :class="['message', messageClass]">
        {{ message }}
      </div>

      <!-- État de chargement -->
      <div v-if="loading" class="loading">Chargement des projets...</div>

      <!-- Grille de projets -->
      <div v-else-if="filteredProjects.length > 0" class="projects-grid">
        <div 
          v-for="project in filteredProjects" 
          :key="project.id_Project" 
          class="project-item"
        >
          <div class="project-card" :class="{ archived: project.Archive_date }">
            <h3 class="card-title">{{ project.Name_Unique }}</h3>
            <div class="card-badges">
              <span class="badge">{{ project.Category_Name || 'Sans catégorie' }}</span>
              <span class="badge">{{ formatDate(project.Date_of_creation) }}</span>
            </div>
            <p class="card-description">{{ project.Description }}</p>
          </div>
          <router-link :to="`/projects/${project.id_Project}`" class="btn-view">
            View project
          </router-link>
        </div>
      </div>

      <!-- Message si aucun projet -->
      <div v-else class="no-projects">
        <p v-if="filterStatus === 'all'">Aucun projet disponible.</p>
        <p v-else-if="filterStatus === 'active'">Aucun projet actif.</p>
        <p v-else>Aucun projet archivé.</p>
      </div>
    </main>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'ProjectList',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      projects: [],
      loading: true,
      message: '',
      messageClass: '',
      filterStatus: 'all',
      searchQuery: ''
    }
  },
  
  computed: {
    filteredProjects() {
      let result = this.projects;
      
      if (this.filterStatus === 'active') {
        result = result.filter(p => !p.Archive_date);
      } else if (this.filterStatus === 'archived') {
        result = result.filter(p => p.Archive_date);
      }
      
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        result = result.filter(p => 
          p.Name_Unique.toLowerCase().includes(query) ||
          (p.Description && p.Description.toLowerCase().includes(query))
        );
      }
      
      return result;
    },
    
    allProjectsCount() {
      return this.projects.length;
    },
    
    activeProjectsCount() {
      return this.projects.filter(p => !p.Archive_date).length;
    },
    
    archivedProjectsCount() {
      return this.projects.filter(p => p.Archive_date).length;
    }
  },
  
  mounted() {
    this.loadProjects();
  },

  methods: {
    async loadProjects() {
      this.loading = true;
      this.message = '';
      
      const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
      
      // Vérification de l'authentification
      if (!this.authStore.isAuthenticated()) {
        this.$router.push('/login')
        return
      }
      
      try {
        const response = await fetch(`${baseUrl}?loc=projects&action=list`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`,
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          // Si 401 Unauthorized, rediriger vers login
          if (response.status === 401) {
            this.authStore.logout()
            this.$router.push('/login')
            return
          }
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        
        if (!Array.isArray(data)) {
          throw new Error('Format inattendu');
        }
        
        this.projects = data;
      } catch (error) {
        console.error('Erreur chargement projets:', error);
        this.showMessage('Erreur lors du chargement des projets', 'error');
      } finally {
        this.loading = false;
      }
    },

    formatDate(sqlDate) {
      if (!sqlDate) return '';
      const [year, month, day] = sqlDate.split('-');
      return `${day}/${month}/${year}`;
    },

    showMessage(text, type) {
      this.message = text;
      this.messageClass = type;
      setTimeout(() => { this.message = ''; }, 5000);
    },
    
    async logout() {
      try {
        await this.authStore.logout()
        this.$router.push('/login')
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error)
      }
    }
  }
}
</script>

<style scoped>
.projects-layout {
  display: flex;
  min-height: 100vh;
  width: 100vw;
}

/* Sidebar contextuelle */
.projects-sidebar {
  width: 280px;
  min-height: 100vh;
  background-color: #3d1f1f;
  display: flex;
  flex-direction: column;
  padding: 25px 20px;
  flex-shrink: 0;
  position: fixed;
  left: 0;
  top: 0;
}

.sidebar-header {
  margin-bottom: 40px;
}

.logo-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  gap: 8px;
}

.sidebar-logo {
  width: 50px;
  height: 50px;
}

.logo-text {
  color: #3d1f1f;
  font-size: 16px;
  font-weight: 600;
}

.filter-nav {
  display: flex;
  flex-direction: column;
  gap: 15px;
  flex: 1;
}

.filter-btn {
  background: #ff584a;
  color: #fff;
  border: none;
  border-radius: 25px;
  padding: 12px 25px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  background: #ff4435;
}

.filter-btn.active {
  background: #d0d0d0;
  color: #333;
}

.sidebar-footer {
  padding-top: 20px;
}

.user-login {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.user-icon {
  font-size: 28px;
  background: #ff584a;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-text {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
}

/* Contenu principal */
.projects-main {
  flex: 1;
  padding: 30px 40px;
  background: #f5f5f5;
  margin-left: 280px;
  min-height: 100vh;
}

.main-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 40px;
  max-width: calc(100% - 40px);
}

.badge-projects {
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 10px 30px;
  font-size: 16px;
  font-weight: 600;
}

.search-bar {
  display: flex;
  align-items: center;
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 8px 20px;
  width: 200px;
}

.search-icon {
  font-size: 16px;
  margin-right: 10px;
  opacity: 0.5;
}

.search-bar input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
}

/* Messages */
.message {
  margin-bottom: 20px;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 500;
}

.success { background-color: #d4edda; color: #155724; }
.error { background-color: #f8d7da; color: #721c24; }

.loading {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  color: #666;
}

/* Grille projets 2x2 */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 25px;
  max-width: calc(100% - 40px);
}

.project-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.project-card {
  background: #fff;
  border: 2px solid #000;
  border-radius: 15px;
  padding: 18px 22px;
  width: 100%;
}

.project-card.archived {
  opacity: 0.7;
  background: #f0f0f0;
}

.card-title {
  font-size: 15px;
  font-weight: bold;
  color: #000;
  margin: 0 0 10px 0;
  text-align: center;
}

.card-badges {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 15px;
}

.badge {
  background: #fff;
  border: 1px solid #000;
  border-radius: 15px;
  padding: 4px 12px;
  font-size: 11px;
}

.card-description {
  font-size: 12px;
  color: #333;
  line-height: 1.4;
  margin: 0;
  text-align: center;
}

.btn-view {
  background: #ff584a;
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 10px 25px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-view:hover {
  background: #ff4435;
  transform: translateY(-2px);
}

/* Aucun projet */
.no-projects {
  text-align: center;
  padding: 60px 20px;
}

.no-projects p {
  font-size: 18px;
  color: #666;
  font-style: italic;
}

/* Responsive */
@media (max-width: 900px) {
  .projects-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .projects-layout {
    flex-direction: column;
    margin-left: 0;
  }
  
  .projects-sidebar {
    position: relative;
    width: 100%;
    min-height: auto;
    padding: 20px;
  }
  
  .projects-main {
    margin-left: 0;
    padding: 20px;
  }
}
</style>