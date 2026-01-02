<template>
  <div class="team-layout">
    <!-- Sidebar -->
    <aside class="project-sidebar">
      <div class="sidebar-header">
        <router-link to="/" class="logo-link">
          <img src="/logo_memory.png" alt="Memory Logo" class="sidebar-logo">
          <span class="logo-text">Memory</span>
        </router-link>
      </div>

      <nav class="filter-nav">
        <router-link to="/projects" class="filter-btn">
          Projects
        </router-link>
        <router-link to="/team" class="filter-btn active">
          Team Management
        </router-link>
        <router-link to="/" class="filter-btn">
          Dashboard
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
    <main class="team-main">
      <!-- Header -->
      <div class="main-header">
        <div class="badge-page">👥 Team Management</div>
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input type="text" placeholder="Search...">
        </div>
      </div>

      <!-- Messages de feedback -->
      <div v-if="message" :class="['message', messageClass]">
        {{ message }}
      </div>

      <!-- Project Selection -->
      <div class="project-selection">
        <h2>Sélectionner un projet</h2>
        <select v-model="selectedProjectId" @change="loadProjectTeam" class="project-select">
          <option value="">-- Choisir un projet --</option>
          <option
            v-for="project in projects"
            :key="project.id_Project"
            :value="project.id_Project"
          >
            {{ project.Name_Unique }} ({{ project.Category_Name }})
          </option>
        </select>
      </div>

      <!-- Team Content -->
      <div v-if="selectedProjectId && !loading" class="team-content">
        <!-- Project Info Header -->
        <div class="project-info-header">
          <div class="project-title">
            <h1>📁 {{ currentProject.Name_Unique }}</h1>
            <p class="project-description">{{ currentProject.Description }}</p>
          </div>
          <div class="team-stats">
            <div class="stat-card">
              <span class="stat-number">{{ statistics.managers_count || 0 }}</span>
              <span class="stat-label">Chefs de projet</span>
            </div>
            <div class="stat-card">
              <span class="stat-number">{{ statistics.developers_count || 0 }}</span>
              <span class="stat-label">Développeurs</span>
            </div>
            <div class="stat-card">
              <span class="stat-number">{{ statistics.total_assignments || 0 }}</span>
              <span class="stat-label">Total équipe</span>
            </div>
          </div>
        </div>

        <!-- Team Actions -->
        <div class="team-actions">
          <button
            @click="openAssignmentModal"
            class="btn-primary"
            :disabled="!canAssign"
          >
            Ajouter un membre
          </button>
          <button
            @click="loadProjectTeam"
            class="btn-secondary"
          >
            Actualiser
          </button>
        </div>

        <!-- Loading team -->
        <div v-if="loadingTeam" class="loading">
          <div class="spinner"></div>
          <p>Chargement de l'équipe...</p>
        </div>

        <!-- Team Members -->
        <div v-else class="team-members">
          <!-- Managers Section -->
          <div v-if="managers.length > 0" class="team-section">
            <h3 class="section-title">
              👨‍💼 Chefs de Projet ({{ managers.length }})
            </h3>
            <div class="members-grid">
              <div
                v-for="member in managers"
                :key="`manager-${member.User_id_User}`"
                class="member-card manager"
              >
                <div class="member-info">
                  <div class="member-avatar">👨‍💼</div>
                  <div class="member-details">
                    <h4 class="member-name">{{ member.Username }}</h4>
                    <p class="member-email">{{ member.Email_Unique }}</p>
                    <small class="member-since">
                      Depuis le {{ formatDate(member.start_date) }}
                    </small>
                  </div>
                </div>
                <div class="member-actions" v-if="canManageTeam">
                  <button
                    @click="changeRole(member, 'developer')"
                    class="btn-action small"
                    title="Changer en développeur"
                  >
                    ⬇️
                  </button>
                  <button
                    @click="confirmRemoveMember(member)"
                    class="btn-action danger small"
                    title="Retirer du projet"
                  >
                    ❌
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Developers Section -->
          <div v-if="developers.length > 0" class="team-section">
            <h3 class="section-title">
              👨‍💻 Développeurs ({{ developers.length }})
            </h3>
            <div class="members-grid">
              <div
                v-for="member in developers"
                :key="`developer-${member.User_id_User}`"
                class="member-card developer"
              >
                <div class="member-info">
                  <div class="member-avatar">👨‍💻</div>
                  <div class="member-details">
                    <h4 class="member-name">{{ member.Username }}</h4>
                    <p class="member-email">{{ member.Email_Unique }}</p>
                    <small class="member-since">
                      Depuis le {{ formatDate(member.start_date) }}
                    </small>
                  </div>
                </div>
                <div class="member-actions" v-if="canManageTeam">
                  <button
                    @click="changeRole(member, 'manager')"
                    class="btn-action small"
                    title="Promouvoir chef de projet"
                  >
                    ⬆️
                  </button>
                  <button
                    @click="confirmRemoveMember(member)"
                    class="btn-action danger small"
                    title="Retirer du projet"
                  >
                    ❌
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="teamMembers.length === 0" class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>Aucun membre assigné</h3>
            <p>Ce projet n'a encore aucun membre assigné.</p>
            <button
              @click="openAssignmentModal"
              class="btn-primary"
              :disabled="!canAssign"
            >
              Ajouter le premier membre
            </button>
          </div>
        </div>
      </div>

      <!-- Selection prompt -->
      <div v-else-if="!selectedProjectId" class="selection-prompt">
        <div class="prompt-icon">📂</div>
        <h3>Sélectionnez un projet</h3>
        <p>Choisissez un projet dans la liste ci-dessus pour gérer son équipe.</p>
      </div>

      <!-- Loading projects -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Chargement des projets...</p>
      </div>
    </main>

    <!-- Assignment Modal -->
    <AssignmentModal
      :show="showAssignmentModal"
      :project-id="Number(selectedProjectId)"
      :project-name="currentProject.Name_Unique || ''"
      :project-description="currentProject.Description || ''"
      @success="handleAssignmentSuccess"
      @cancel="showAssignmentModal = false"
      @error="handleAssignmentError"
    />

    <!-- Confirm Remove Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      :title="confirmModalTitle"
      :message="confirmModalMessage"
      confirm-text="Retirer"
      @confirm="removeMember"
      @cancel="showConfirmModal = false"
    />
  </div>
</template>

<script>
import AssignmentModal from '@/components/ui/AssignmentModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'TeamManagement',

  components: {
    AssignmentModal,
    ConfirmModal
  },

  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  data() {
    return {
      loading: false,
      loadingTeam: false,
      message: '',
      messageClass: '',

      // Projects
      projects: [],
      selectedProjectId: '',
      currentProject: {},

      // Team
      teamMembers: [],
      statistics: {},

      // Modals
      showAssignmentModal: false,
      showConfirmModal: false,
      memberToRemove: null,

      // User
      currentUser: {},

      baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/'
    }
  },

  computed: {
    managers() {
      return this.teamMembers.filter(member => member.role_in_project === 'manager')
    },

    developers() {
      return this.teamMembers.filter(member => member.role_in_project === 'developer')
    },

    canAssign() {
      return this.currentUser.role === 'admin' ||
             this.managers.some(manager => manager.User_id_User === this.currentUser.id_User)
    },

    canManageTeam() {
      return this.canAssign
    },

    confirmModalTitle() {
      return this.memberToRemove ? `Retirer ${this.memberToRemove.Username}` : ''
    },

    confirmModalMessage() {
      if (!this.memberToRemove) return ''
      return `Êtes-vous sûr de vouloir retirer ${this.memberToRemove.Username} de ce projet ? Cette action peut être annulée en reassignant l'utilisateur.`
    }
  },

  async mounted() {
    await this.loadCurrentUser()
    await this.loadProjects()
  },

  methods: {
    async loadCurrentUser() {
      // Récupérer depuis le store auth
      if (this.authStore.isAuthenticated()) {
        const token = this.authStore.token
        try {
          // Décoder le token pour obtenir les infos utilisateur
          const payload = JSON.parse(atob(token.split('.')[1]))
          this.currentUser = {
            id_User: payload.user_id,
            email: payload.email,
            role: payload.role
          }
        } catch (error) {
          console.error('Erreur décodage token:', error)
        }
      } else {
        this.$router.push('/login')
      }
    },

    async loadProjects() {
      this.loading = true

      try {
        const token = this.authStore.token
        const response = await fetch(`${this.baseUrl}?loc=projects&action=list`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        const projects = await response.json()

        if (response.ok && Array.isArray(projects)) {
          // Filtrer les projets non archivés
          this.projects = projects.filter(project => !project.Archive_date)
        } else {
          throw new Error('Erreur lors du chargement')
        }

      } catch (error) {
        this.showMessage('Erreur lors du chargement des projets', 'error')
        console.error('Erreur:', error)
      } finally {
        this.loading = false
      }
    },

    async loadProjectTeam() {
      if (!this.selectedProjectId) return

      this.loadingTeam = true
      this.currentProject = this.projects.find(p => p.id_Project == this.selectedProjectId) || {}

      try {
        const token = this.authStore.token

        // Charger les assignations et les statistiques en parallèle
        const [assignmentsResponse, statsResponse] = await Promise.all([
          fetch(`${this.baseUrl}?loc=projects&action=assignments&project_id=${this.selectedProjectId}`, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          }),
          fetch(`${this.baseUrl}?loc=projects&action=assignment_stats&project_id=${this.selectedProjectId}`, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          })
        ])

        const [assignmentsData, statsData] = await Promise.all([
          assignmentsResponse.json(),
          statsResponse.json()
        ])

        if (assignmentsResponse.ok && assignmentsData.success) {
          this.teamMembers = assignmentsData.assignments
        } else {
          throw new Error('Erreur chargement équipe')
        }

        if (statsResponse.ok && statsData.success) {
          this.statistics = statsData.statistics
        }

      } catch (error) {
        this.showMessage('Erreur lors du chargement de l\'équipe', 'error')
        console.error('Erreur:', error)
      } finally {
        this.loadingTeam = false
      }
    },

    openAssignmentModal() {
      if (this.canAssign) {
        this.showAssignmentModal = true
      }
    },

    handleAssignmentSuccess(data) {
      this.showAssignmentModal = false
      this.showMessage(data.message, 'success')
      this.loadProjectTeam() // Recharger l'équipe
    },

    handleAssignmentError(error) {
      this.showMessage(error, 'error')
    },

    async changeRole(member, newRole) {
      try {
        const token = this.authStore.token
        const formData = new FormData()
        formData.append('user_id', member.User_id_User)
        formData.append('project_id', this.selectedProjectId)
        formData.append('new_role', newRole)

        const response = await fetch(`${this.baseUrl}?loc=projects&action=change_role`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`
          },
          body: formData
        })

        const data = await response.json()

        if (response.ok && data.success) {
          this.showMessage(data.message, 'success')
          this.loadProjectTeam()
        } else {
          throw new Error(data.error || 'Erreur changement de rôle')
        }

      } catch (error) {
        this.showMessage(error.message, 'error')
      }
    },

    confirmRemoveMember(member) {
      this.memberToRemove = member
      this.showConfirmModal = true
    },

    async removeMember() {
      if (!this.memberToRemove) return

      try {
        const token = this.authStore.token
        const formData = new FormData()
        formData.append('user_id', this.memberToRemove.User_id_User)
        formData.append('project_id', this.selectedProjectId)

        const response = await fetch(`${this.baseUrl}?loc=projects&action=remove_assignment`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`
          },
          body: formData
        })

        const data = await response.json()

        if (response.ok && data.success) {
          this.showMessage(data.message, 'success')
          this.loadProjectTeam()
        } else {
          throw new Error(data.error || 'Erreur lors de la suppression')
        }

      } catch (error) {
        this.showMessage(error.message, 'error')
      } finally {
        this.showConfirmModal = false
        this.memberToRemove = null
      }
    },

    showMessage(msg, type = 'info') {
      this.message = msg
      this.messageClass = type

      setTimeout(() => {
        this.message = ''
        this.messageClass = ''
      }, 5000)
    },

    formatDate(dateString) {
      if (!dateString) return 'Non définie'
      try {
        return new Date(dateString).toLocaleDateString('fr-FR')
      } catch {
        return dateString
      }
    }
  }
}
</script>

<style scoped>
/* =====================================
   LAYOUT PRINCIPAL - CHARTE MEMORY
   ===================================== */
.team-layout {
  display: flex;
  min-height: 100vh;
  width: 100vw;
}

/* =====================================
   SIDEBAR - STYLE MAQUETTE
   ===================================== */
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
  color: #fff;
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

/* =====================================
   CONTENU PRINCIPAL - STYLE MAQUETTE
   ===================================== */
.team-main {
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
  color: #000;
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

/* =====================================
   MESSAGES ET CARTES - STYLE MAQUETTE
   ===================================== */
.message {
  padding: 12px 20px;
  border-radius: 25px;
  margin-bottom: 25px;
  font-weight: 500;
  width: 100%;
  max-width: 700px;
}

.message.success {
  background-color: #d4edda;
  color: #155724;
  border: 2px solid #c3e6cb;
}

.message.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 2px solid #f5c6cb;
}

/* Project Selection Card */
.project-selection {
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 35px 40px;
  margin-bottom: 30px;
  width: 100%;
  max-width: 700px;
}

.project-selection h2 {
  margin: 0 0 20px 0;
  color: #000;
  font-size: 24px;
  font-weight: 600;
}

.project-select {
  width: 100%;
  padding: 15px 25px;
  border: 2px solid #000;
  border-radius: 25px;
  font-size: 16px;
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.project-select:focus {
  outline: none;
  border-color: #ff584a;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(255, 88, 74, 0.1);
}

/* Project Info Header - Style maquette */
.project-info-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 35px 40px;
  margin-bottom: 30px;
  width: 100%;
  max-width: 700px;
}

.project-title h1 {
  margin: 0 0 10px 0;
  color: #333;
  font-size: 28px;
}

.project-description {
  color: #666;
  line-height: 1.6;
  margin: 0;
}

.team-stats {
  display: flex;
  gap: 20px;
}

.stat-card {
  text-align: center;
  min-width: 80px;
}

.stat-number {
  display: block;
  font-size: 32px;
  font-weight: bold;
  color: #4CAF50;
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
}

/* Team Actions - Style maquette */
.team-actions {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin-bottom: 40px;
  width: 100%;
  max-width: 700px;
}

.btn-primary, .btn-secondary {
  background: #ff584a;
  color: #fff;
  border: none;
  border-radius: 25px;
  padding: 14px 40px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover:not(:disabled) {
  background: #ff4435;
  transform: translateY(-2px);
}

.btn-primary:disabled {
  background: #cccccc;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: #fff;
  color: #333;
  border: 2px solid #000;
}

.btn-secondary:hover {
  background: #f8f9fa;
  transform: translateY(-2px);
}

/* Loading */
.loading {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #4CAF50;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Team Content */
.team-content {
  width: 100%;
  max-width: 700px;
}

/* Team Members */
.team-section {
  margin-bottom: 40px;
}

.section-title {
  color: #333;
  margin-bottom: 20px;
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.member-card {
  background-color: #fff;
  border: 2px solid #e9ecef;
  border-radius: 15px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  transition: all 0.2s ease;
}

.member-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.member-card.manager {
  border-color: #4CAF50;
  background: linear-gradient(to right, #f8fff8, #ffffff);
}

.member-card.developer {
  border-color: #2196F3;
  background: linear-gradient(to right, #f8fbff, #ffffff);
}

.member-info {
  display: flex;
  align-items: center;
  gap: 15px;
  flex: 1;
}

.member-avatar {
  font-size: 32px;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8f9fa;
  border-radius: 50%;
}

.member-details {
  flex: 1;
}

.member-name {
  margin: 0 0 5px 0;
  color: #333;
  font-size: 16px;
}

.member-email {
  margin: 0 0 5px 0;
  color: #666;
  font-size: 14px;
}

.member-since {
  color: #999;
  font-size: 12px;
}

.member-actions {
  display: flex;
  gap: 10px;
}

.btn-action {
  border: none;
  background-color: transparent;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: all 0.2s ease;
  font-size: 16px;
}

.btn-action:hover {
  background-color: #f0f0f0;
}

.btn-action.danger:hover {
  background-color: #fee;
}

.btn-action.small {
  font-size: 14px;
  padding: 6px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.empty-state h3 {
  color: #333;
  margin-bottom: 10px;
}

.empty-state p {
  color: #666;
  margin-bottom: 30px;
}

/* Selection Prompt */
.selection-prompt {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  width: 100%;
  max-width: 700px;
}

.prompt-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.selection-prompt h3 {
  color: #333;
  margin-bottom: 10px;
}

.selection-prompt p {
  color: #666;
}

/* Responsive */
@media (max-width: 768px) {
  .team-layout {
    flex-direction: column;
  }

  .project-sidebar {
    width: 100%;
    padding: 15px;
  }

  .team-main {
    padding: 20px;
  }

  .project-info-header {
    flex-direction: column;
    gap: 20px;
  }

  .team-stats {
    justify-content: center;
  }

  .members-grid {
    grid-template-columns: 1fr;
  }
}
</style>