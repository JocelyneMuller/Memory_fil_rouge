<template>
  <div class="project-layout">
    <!-- Sidebar contextuelle (identique à la maquette) -->
    <aside class="project-sidebar">
      <div class="sidebar-header">
        <router-link to="/" class="logo-link">
          <img src="/logo_memory.png" alt="Memory Logo" class="sidebar-logo">
          <span class="logo-text">Memory</span>
        </router-link>
      </div>

      <nav class="filter-nav">
        <router-link to="/projects" class="filter-btn active">
          All ({{ allProjectsCount }})
        </router-link>
        <router-link to="/projects?filter=active" class="filter-btn">
          Actives ({{ activeProjectsCount }})
        </router-link>
        <router-link to="/projects?filter=archived" class="filter-btn">
          Archived ({{ archivedProjectsCount }})
        </router-link>
        <router-link to="/projects/create" class="filter-btn">
          Create
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-login">
          <span class="user-icon">👤</span>
          <span class="user-text">Log in</span>
        </div>
      </div>
    </aside>

    <!-- Contenu principal -->
    <main class="project-main">
      <!-- Header avec badge et recherche -->
      <div class="main-header">
        <div class="badge-page">Project 1</div>
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input type="text" placeholder="Search...">
        </div>
      </div>

      <!-- Message de feedback -->
      <div v-if="message" :class="['message', messageClass]">
        {{ message }}
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="loading">
        Chargement du projet...
      </div>

      <!-- Contenu du projet -->
      <div v-else-if="project.id_Project" class="project-content">
        <!-- Carte avec nom du projet -->
        <div class="project-name-card">
          <span class="project-name">{{ project.Name_Unique }}</span>
        </div>

        <!-- Badges -->
        <div class="project-badges">
          <span class="badge">{{ project.Category_Name || 'Sans catégorie' }}</span>
          <span class="badge">{{ formatDate(project.Date_of_creation) }}</span>
        </div>

        <!-- Description Card -->
        <div class="description-card">
          <p class="description-text">{{ project.Description || 'Aucune description disponible.' }}</p>
        </div>

        <!-- Actions sous la carte -->
        <div class="project-actions">
          <button class="btn-action" @click="updateProject">Update</button>
          <button class="btn-action" @click="attributeTo">Attribute to</button>
          <button 
            class="btn-action" 
            @click="archiveProject"
          >
            {{ project.Archive_date ? 'Archived' : 'Archivate' }}
          </button>
        </div>
      </div>

      <!-- Erreur si projet non trouvé -->
      <div v-else class="not-found">
        <p>Projet non trouvé.</p>
        <router-link to="/projects" class="back-link">← Retour aux projets</router-link>
      </div>
    </main>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'ProjectDetail',

  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      project: {},
      allProjects: [],
      loading: true,
      message: '',
      messageClass: ''
    }
  },
  
  computed: {
    allProjectsCount() {
      return this.allProjects.length
    },
    activeProjectsCount() {
      return this.allProjects.filter(p => !p.Archive_date).length
    },
    archivedProjectsCount() {
      return this.allProjects.filter(p => p.Archive_date).length
    }
  },
  
  async mounted() {
    await Promise.all([this.loadProject(), this.loadAllProjects()])
  },
  
  methods: {
    async loadAllProjects() {
      try {
        // Vérification de l'authentification
        if (!this.authStore.isAuthenticated()) {
          this.$router.push('/login')
          return
        }

        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/'
        const response = await fetch(`${baseUrl}?loc=projects&action=list`, {
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) {
          if (response.status === 401) {
            this.authStore.logout()
            this.$router.push('/login')
            return
          }
          throw new Error(`HTTP ${response.status}`)
        }

        this.allProjects = await response.json()
      } catch (error) {
        console.error('Error loading all projects:', error)
      }
    },
    
    async loadProject() {
      const projectId = this.$route.params.id
      this.loading = true

      try {
        // Vérification de l'authentification
        if (!this.authStore.isAuthenticated()) {
          this.$router.push('/login')
          return
        }

        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/'
        const response = await fetch(`${baseUrl}?loc=projects&action=show&id=${projectId}`, {
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) {
          if (response.status === 401) {
            this.authStore.logout()
            this.$router.push('/login')
            return
          }
          throw new Error(`HTTP ${response.status}`)
        }

        const data = await response.json()

        if (data.error) {
          throw new Error(data.error)
        }

        // Vérification si le projet existe (false/null depuis le backend)
        if (!data || !data.id_Project) {
          throw new Error('Projet non trouvé')
        }

        // Plus besoin de charger les catégories séparément
        // car getProjectById() retourne maintenant Category_Name via LEFT JOIN
        this.project = data

      } catch (error) {
        console.error('Error loading project:', error)
        this.showMessage('Erreur lors du chargement du projet : ' + error.message, 'error')
      } finally {
        this.loading = false
      }
    },
    
    updateProject() {
      this.showMessage('Fonctionnalité à venir !', 'info')
    },
    
    attributeTo() {
      this.showMessage('Fonctionnalité à venir !', 'info')
    },
    
    async archiveProject() {
      if (this.project.Archive_date) {
        this.showMessage('Ce projet est déjà archivé.', 'info')
        return
      }

      try {
        // Vérification de l'authentification
        if (!this.authStore.isAuthenticated()) {
          this.$router.push('/login')
          return
        }

        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/'
        const response = await fetch(`${baseUrl}?loc=projects&action=archivate&id=${this.project.id_Project}`, {
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) {
          if (response.status === 401) {
            this.authStore.logout()
            this.$router.push('/login')
            return
          }
          throw new Error(`HTTP ${response.status}`)
        }

        const result = await response.json()

        if (result.error) {
          throw new Error(result.error)
        }

        this.showMessage(`Projet "${this.project.Name_Unique}" archivé avec succès !`, 'success')
        this.project.Archive_date = result.archived_date || new Date().toISOString().split('T')[0]

      } catch (error) {
        console.error('Archive error:', error)
        this.showMessage('Erreur lors de l\'archivage : ' + error.message, 'error')
      }
    },
    
    formatDate(sqlDate) {
      if (!sqlDate) return ''
      const [year, month, day] = sqlDate.split('-')
      return `${day}/${month}/${year}`
    },
    
    showMessage(text, type) {
      this.message = text
      this.messageClass = type
      setTimeout(() => { this.message = '' }, 5000)
    }
  }
}
</script>

<style scoped>
.project-layout {
  display: flex;
  min-height: 100vh;
  width: 100vw;
}

/* Sidebar */
.project-sidebar {
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
.project-main {
  flex: 1;
  padding: 30px 40px;
  background: #f5f5f5;
  margin-left: 280px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  width: 100%;
  max-width: 700px;
}

.badge-page {
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
  padding: 12px 20px;
  border-radius: 10px;
  margin-bottom: 25px;
  font-weight: 500;
}

.message.success { background-color: #d4edda; color: #155724; }
.message.error { background-color: #f8d7da; color: #721c24; }
.message.info { background-color: #d1ecf1; color: #0c5460; }

.loading {
  text-align: center;
  padding: 60px;
  font-size: 18px;
  color: #666;
}

/* Contenu du projet */
.project-content {
  width: 100%;
  max-width: 700px;
}

.project-name-card {
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 15px 30px;
  margin-bottom: 25px;
  text-align: center;
}

.project-name {
  font-size: 18px;
  font-weight: 600;
  color: #000;
}

.project-badges {
  display: flex;
  justify-content: space-between;
  margin-bottom: 25px;
}

.badge {
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 10px 25px;
  font-size: 14px;
  font-weight: 500;
}

.description-card {
  background: #e8e8e8;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 35px 40px;
  min-height: 180px;
  margin-bottom: 30px;
}

.description-text {
  font-size: 15px;
  line-height: 1.8;
  color: #333;
  margin: 0;
}

/* Actions sous la carte */
.project-actions {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.btn-action {
  background: #ff584a;
  color: #fff;
  border: none;
  border-radius: 25px;
  padding: 14px 40px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
  text-align: center;
}

.btn-action:hover {
  background: #ff4435;
  transform: translateY(-2px);
}

/* Projet non trouvé */
.not-found {
  text-align: center;
  padding: 80px 20px;
}

.not-found p {
  font-size: 18px;
  color: #666;
  margin: 0 0 20px 0;
}

.back-link {
  color: #ff584a;
  text-decoration: none;
  font-weight: 600;
}

.back-link:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
  .project-layout {
    flex-direction: column;
  }
  
  .project-sidebar {
    position: relative;
    width: 100%;
    min-height: auto;
    padding: 20px;
  }
  
  .project-main {
    margin-left: 0;
    padding: 20px;
  }
  
  .main-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .project-actions {
    flex-direction: column;
  }
}
</style>
