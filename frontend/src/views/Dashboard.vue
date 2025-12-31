<template>
  <div class="dashboard">
    <!-- Header avec titre et recherche -->
    <div class="dashboard-header">
      <div class="title-badge">Dashboard</div>
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="Search..." v-model="searchQuery">
      </div>
    </div>

    <!-- Section Competences -->
    <div class="dashboard-section">
      <div class="section-card">
        <div class="section-items">
          <p v-if="competences.length === 0">No competences yet</p>
          <p v-for="(comp, index) in displayedCompetences" :key="index">{{ comp.name || `Competence ${index + 1}` }}</p>
          <router-link to="/competences" class="view-all-link">View all</router-link>
        </div>
      </div>
      <router-link to="/competences" class="section-btn">View competences</router-link>
    </div>

    <!-- Section Projects -->
    <div class="dashboard-section">
      <div class="section-card">
        <div class="section-items">
          <p v-if="projects.length === 0">No projects yet</p>
          <p v-for="project in displayedProjects" :key="project.id_Project">{{ project.Name_Unique }}</p>
          <router-link to="/projects" class="view-all-link">View all</router-link>
        </div>
      </div>
      <router-link to="/projects" class="section-btn">View projects</router-link>
    </div>

    <!-- Section Notes -->
    <div class="dashboard-section">
      <div class="section-card">
        <div class="section-items">
          <p v-if="notes.length === 0">No notes yet</p>
          <p v-for="(note, index) in displayedNotes" :key="index">{{ note.name || `Note ${index + 1}` }}</p>
          <router-link to="/notes" class="view-all-link">View all</router-link>
        </div>
      </div>
      <router-link to="/notes" class="section-btn">View notes</router-link>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'Dashboard',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      searchQuery: '',
      competences: [],
      projects: [],
      notes: []
    }
  },
  
  computed: {
    displayedCompetences() {
      return this.competences.slice(0, 2)
    },
    displayedProjects() {
      return this.projects.slice(0, 2)
    },
    displayedNotes() {
      return this.notes.slice(0, 2)
    }
  },
  
  async mounted() {
    await this.loadProjects()
  },
  
  methods: {
    async loadProjects() {
      try {
        // Vérification de l'authentification
        if (!this.authStore.isAuthenticated()) {
          console.log('❌ Non authentifié')
          this.$router.push('/login')
          return
        }
        
        console.log('✅ Authentifié, token:', this.authStore.token?.substring(0, 30) + '...')
        
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/'
        const url = `${baseUrl}?loc=projects&action=list`
        console.log('📡 Appel API:', url)
        
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        console.log('📥 Réponse API status:', response.status)
        
        if (!response.ok) {
          if (response.status === 401) {
            console.log('❌ 401 Unauthorized - Déconnexion')
            this.authStore.logout()
            this.$router.push('/login')
            return
          }
          throw new Error('Erreur chargement projets')
        }
        
        const projects = await response.json()
        console.log('✅ Projets chargés:', projects.length)
        this.projects = projects.filter(p => !p.Archive_date)
        
      } catch (error) {
        console.error('❌ Erreur chargement projets:', error)
      }
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 40px 50px 40px 15px;
  width: 100%;
  max-width: 1100px;
  display: grid;
  grid-template-columns: 280px 1fr;
  column-gap: 100px;
  row-gap: 65px;
}

.dashboard-header {
  display: contents;
}

.title-badge {
  background: #fff;
  border: 2px solid #000;
  border-radius: 30px;
  padding: 12px 40px;
  font-size: 18px;
  font-weight: 600;
  color: #000;
  text-align: center;
  align-self: center;
}

.search-bar {
  display: flex;
  align-items: center;
  background: #fff;
  border: 2px solid #000;
  border-radius: 30px;
  padding: 8px 20px;
  max-width: 300px;
  justify-self: start;
  align-self: center;
}

.search-icon {
  font-size: 18px;
  margin-right: 10px;
  opacity: 0.5;
}

.search-bar input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  background: transparent;
}

/* Sections */
.dashboard-section {
  display: contents;
}

.section-card {
  background: #fff;
  border: 2px solid #000;
  border-radius: 20px;
  padding: 25px 35px;
  width: 280px;
}

.section-btn {
  background: #ff584a;
  color: #fff;
  border: none;
  border-radius: 30px;
  padding: 16px 35px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  transition: background 0.2s ease, transform 0.2s ease;
  align-self: center;
  justify-self: start;
}

.section-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-items p {
  margin: 0;
  font-size: 16px;
  color: #333;
}

.view-all-link {
  color: #333;
  text-decoration: none;
  font-size: 16px;
  margin-top: 5px;
}

.view-all-link:hover {
  text-decoration: underline;
}

.section-btn:hover {
  background: #ff4435;
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard {
    padding: 20px;
  }
  
  .dashboard-header {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
  }
  
  .search-bar {
    max-width: none;
  }
  
  .dashboard-section {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
  }
  
  .section-card {
    min-width: auto;
  }
  
  .section-btn {
    text-align: center;
  }
}
</style>
