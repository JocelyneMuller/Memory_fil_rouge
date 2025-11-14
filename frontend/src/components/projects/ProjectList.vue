<template>
  <div class="projects-container">
    <!-- Header avec titre et filtre -->
    <div class="header">
      <h1>Liste des projets</h1>
      
      <!-- Filtre actifs/archivés -->
      <div class="filter-buttons">
        <button 
          @click="filterStatus = 'all'"
          :class="['filter-btn', { active: filterStatus === 'all' }]"
        >
          Tous ({{ allProjectsCount }})
        </button>
        <button 
          @click="filterStatus = 'active'"
          :class="['filter-btn', { active: filterStatus === 'active' }]"
        >
          Actifs ({{ activeProjectsCount }})
        </button>
        <button 
          @click="filterStatus = 'archived'"
          :class="['filter-btn', { active: filterStatus === 'archived' }]"
        >
          Archivés ({{ archivedProjectsCount }})
        </button>
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
        class="project-card"
        :class="{ 'archived': project.Archive_date }"
      >
        <!-- Header de la carte avec badges -->
        <div class="card-header">
          <span class="badge badge-category">
            {{ project.Category_Name || 'Sans catégorie' }}
          </span>
          <span class="badge badge-date">
            {{ formatDate(project.Date_of_creation) }}
          </span>
        </div>

        <!-- Titre du projet -->
        <h3 class="project-title">{{ project.Name_Unique }}</h3>

        <!-- Description -->
        <p class="project-description">{{ project.Description }}</p>

        <!-- Informations supplémentaires (Rôle, Compétences) -->
        <!-- TODO: Activer quand le backend retournera ces données -->
        <!-- 
        <div class="project-meta">
          <div v-if="project.Role_Name" class="meta-item">
            <span class="meta-label">Rôle :</span>
            <span class="meta-value">{{ project.Role_Name }}</span>
          </div>
          <div v-if="project.Competences && project.Competences.length" class="meta-item">
            <span class="meta-label">Compétences :</span>
            <div class="competences-tags">
              <span 
                v-for="competence in project.Competences" 
                :key="competence.id_Competence"
                class="competence-tag"
              >
                {{ competence.Wording_Unique }}
              </span>
            </div>
          </div>
        </div>
        -->

        <!-- Footer avec actions -->
        <div class="card-footer">
          <!-- Badge "Archivé" si le projet est archivé -->
          <span v-if="project.Archive_date" class="badge badge-archived">
            Archivé le {{ formatDate(project.Archive_date) }}
          </span>

          <!-- Bouton "Archiver" si le projet est actif -->
          <button 
            v-else
            @click="showArchiveConfirm(project)"
            class="btn-archive"
            title="Archiver ce projet"
          >
            Archiver
          </button>
        </div>
      </div>
    </div>

    <!-- Message si aucun projet -->
    <div v-else class="no-projects">
      <p v-if="filterStatus === 'all'">
        Aucun projet disponible pour le moment.
      </p>
      <p v-else-if="filterStatus === 'active'">
        Aucun projet actif pour le moment.
      </p>
      <p v-else>
        Aucun projet archivé pour le moment.
      </p>
    </div>

    <!-- Modal de confirmation d'archivage -->
    <ConfirmModal
      :show="showModal"
      :title="modalTitle"
      :message="modalMessage"
      confirmText="Archiver"
      @confirm="confirmArchive"
      @cancel="cancelArchive"
    />
  </div>
</template>

<script>
import ConfirmModal from '../ui/ConfirmModal.vue';

export default {
  name: "ProjectList",
  
  components: {
    ConfirmModal
  },
  
  data() {
    return {
      projects: [],
      loading: true,
      message: '',
      messageClass: '',
      filterStatus: 'all', // 'all', 'active', 'archived'
      
      // Modal state
      showModal: false,
      modalTitle: '',
      modalMessage: '',
      projectToArchive: null
    }
  },
  
  computed: {
    // Filtre les projets selon le statut sélectionné
    filteredProjects() {
      if (this.filterStatus === 'active') {
        return this.projects.filter(p => !p.Archive_date);
      } else if (this.filterStatus === 'archived') {
        return this.projects.filter(p => p.Archive_date);
      }
      return this.projects; // 'all'
    },
    
    // Compteurs pour les badges de filtre
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
      console.log('Chargement des projets...');
      this.loading = true;
      this.message = '';
      
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
        this.showMessage('Erreur lors du chargement des projets', 'error');
      } finally {
        this.loading = false;
      }
    },

    showArchiveConfirm(project) {
      this.projectToArchive = project;
      this.modalTitle = 'Archiver le projet ?';
      this.modalMessage = `Êtes-vous sûr de vouloir archiver le projet "${project.Name_Unique}" ?\n\nCette action marquera le projet comme archivé.`;
      this.showModal = true;
    },

    async confirmArchive() {
      this.showModal = false;
      
      if (!this.projectToArchive) return;

      const projectId = this.projectToArchive.id_Project;
      const projectName = this.projectToArchive.Name_Unique;

      try {
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
        const endpoint = `${baseUrl}?loc=projects&action=archivate&id=${projectId}`;
        
        const response = await fetch(endpoint, {
          method: 'GET'
        });

        const result = await response.json();

        if (result.success) {
          this.showMessage(
            `Le projet "${projectName}" a été archivé avec succès.`,
            'success'
          );
          
          // Recharger la liste des projets après archivage
          await this.loadProjects();
        } else {
          this.showMessage(
            `Erreur lors de l'archivage : ${result.error}`,
            'error'
          );
        }
      } catch (error) {
        console.error('Erreur archivage projet:', error);
        this.showMessage(
          'Erreur de communication avec le serveur',
          'error'
        );
      } finally {
        this.projectToArchive = null;
      }
    },

    cancelArchive() {
      this.showModal = false;
      this.projectToArchive = null;
    },

    formatDate(sqlDate) {
      if (!sqlDate) return '';
      
      const [year, month, day] = sqlDate.split('-');
      return `${day}/${month}/${year}`;
    },

    showMessage(text, type) {
      this.message = text;
      this.messageClass = type;
      
      setTimeout(() => {
        this.message = '';
      }, 5000);
    }
  }
}
</script>

<style scoped>
/* Container principal */
.projects-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 30px 20px;
}

/* Header avec titre et filtres */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

h1 {
  font-size: 32px;
  font-weight: bold;
  margin: 0;
  color: #000;
}

/* Boutons de filtre */
.filter-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-btn {
  background-color: #fff;
  color: #333;
  border: 2px solid #ddd;
  border-radius: 20px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  border-color: #FF6B5B;
  color: #FF6B5B;
}

.filter-btn.active {
  background-color: #FF6B5B;
  color: #fff;
  border-color: #FF6B5B;
}

/* Messages de feedback */
.message {
  margin-bottom: 20px;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 500;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* État de chargement */
.loading {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  color: #666;
}

/* Grille de projets (2 colonnes sur desktop) */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 30px;
  margin-top: 20px;
}

/* Responsive : 1 colonne sur mobile/tablette */
@media (max-width: 968px) {
  .projects-grid {
    grid-template-columns: 1fr;
  }
  
  .header {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* Carte individuelle de projet */
.project-card {
  background-color: #fff;
  border: 2px solid #000;
  border-radius: 20px;
  padding: 25px;
  display: flex;
  flex-direction: column;
  gap: 15px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
}

.project-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

/* Carte archivée avec style atténué */
.project-card.archived {
  opacity: 0.7;
  background-color: #f5f5f5;
}

/* Header de la carte avec badges */
.card-header {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* Badges style pill */
.badge {
  display: inline-block;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid #000;
  background-color: #fff;
}

.badge-category {
  color: #000;
}

.badge-date {
  color: #666;
}

.badge-archived {
  background-color: #e0e0e0;
  color: #555;
  border-color: #999;
}

/* Titre du projet */
.project-title {
  font-size: 22px;
  font-weight: bold;
  color: #000;
  margin: 0;
  line-height: 1.3;
}

/* Description du projet */
.project-description {
  font-size: 15px;
  color: #333;
  line-height: 1.6;
  margin: 0;
  flex-grow: 1;
}

/* Métadonnées (Rôle, Compétences) - À activer plus tard */
.project-meta {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px solid #e0e0e0;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.meta-label {
  font-size: 13px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.meta-value {
  font-size: 15px;
  color: #333;
}

.competences-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.competence-tag {
  display: inline-block;
  padding: 4px 12px;
  background-color: #f0f0f0;
  border: 1px solid #ccc;
  border-radius: 12px;
  font-size: 13px;
  color: #555;
}

/* Footer de la carte */
.card-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 10px;
}

/* Bouton Archiver (style rouge-corail comme la navigation) */
.btn-archive {
  background-color: #FF6B5B;
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 10px 24px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-archive:hover {
  background-color: #ff5545;
  transform: scale(1.05);
}

.btn-archive:active {
  transform: scale(0.98);
}

/* Message si aucun projet */
.no-projects {
  text-align: center;
  padding: 60px 20px;
}

.no-projects p {
  font-size: 18px;
  color: #666;
  font-style: italic;
  margin: 0;
}
</style>