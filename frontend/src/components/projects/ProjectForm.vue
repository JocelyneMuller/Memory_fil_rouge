<template>
  <div class="project-form">
    <h2>Créer un nouveau projet</h2>
    
    <form @submit.prevent="createProject">
      <div class="form-group">
        <label for="projectName">Nom du projet :</label>
        <input 
          id="projectName"
          v-model="formData.name" 
          type="text" 
          required 
          maxlength="50" 
          placeholder="Nom de votre projet"
        >
      </div>

      <div class="form-group">
        <label for="projectDescription">Description :</label>
        <textarea 
          id="projectDescription"
          v-model="formData.description" 
          required 
          maxlength="1000" 
          placeholder="Description détaillée du projet..."
          rows="4"
        ></textarea>
      </div>

      <div class="form-group">
        <label for="projectCategory">Catégorie :</label>
        <select 
          id="projectCategory"
          v-model="formData.category_id" 
          required
        >
          <option value="">-- Choisir une catégorie --</option>
          <option 
            v-for="category in categories" 
            :key="category.id_Category" 
            :value="category.id_Category"
          >
            {{ category.Name_Unique }}
          </option>
        </select>
      </div>

      <button type="submit" :disabled="loading" class="submit-btn">
        {{ loading ? 'Création...' : 'Créer le projet' }}
      </button>
    </form>

    <!-- Messages de retour -->
    <div v-if="message" :class="messageClass" class="message">
      {{ message }}
    </div>
  </div>
</template>

<script>
export default {
  name: "ProjectForm",
  
  data() {
    return {
      formData: {
        name: '',
        description: '',
        category_id: ''
      },
      categories: [],
      loading: false,
      message: '',
      messageClass: ''
    }
  },
  
  async mounted() {
    await this.loadCategories();
  },
  
  methods: {
    async loadCategories() {
      // Charger les catégories depuis l'API. L'URL de base vient de la variable d'environnement VITE_API_URL
      console.log('Chargement des catégories...');
      const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
      const endpoint = `${baseUrl}?loc=categories`;

      try {
        const response = await fetch(endpoint);
        console.log('Réponse catégories:', response.status);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const categories = await response.json();
        console.log('Catégories chargées:', categories);
        this.categories = categories;
      } catch (error) {
        console.error('Erreur chargement catégories:', error);
        this.showMessage('Erreur lors du chargement des catégories', 'error');
      }
    },
    
    async createProject() {
      this.loading = true;
      this.message = '';
      
      try {
        // Préparation des données
        const formData = new FormData();
        formData.append('name', this.formData.name);
        formData.append('description', this.formData.description);
        formData.append('category_id', this.formData.category_id);
        
        // Appel API (base URL configurable via VITE_API_URL)
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
        const endpoint = `${baseUrl}?loc=projects&action=create`;
        const response = await fetch(endpoint, {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          this.showMessage(`Projet "${this.formData.name}" créé avec succès !`, 'success');
          this.resetForm();
          
          // Émet un événement pour notifier le parent
          this.$emit('projectCreated', result);
        } else {
          this.showMessage('Erreur : ' + result.error, 'error');
        }
        
      } catch (error) {
        console.error('Erreur création projet:', error);
        this.showMessage('Erreur de communication avec le serveur', 'error');
      } finally {
        this.loading = false;
      }
    },
    
    resetForm() {
      this.formData = {
        name: '',
        description: '',
        category_id: ''
      };
    },
    
    showMessage(text, type) {
      this.message = text;
      this.messageClass = type;
      
      // Efface le message après 5 secondes
      setTimeout(() => {
        this.message = '';
      }, 5000);
    }
  }
}
</script>

<style scoped>
.project-form {
  max-width: 600px;
  margin: 20px auto;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f9f9f9;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

input, textarea, select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
}

textarea {
  resize: vertical;
}

.submit-btn {
  background-color: #007bff;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
}

.submit-btn:hover {
  background-color: #0056b3;
}

.submit-btn:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.message {
  margin-top: 15px;
  padding: 10px;
  border-radius: 4px;
  font-weight: bold;
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
</style>