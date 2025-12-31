<template>
  <div class="project-form">
    <!-- Logo Memory -->
    <div class="logo-container">
      <img src="/logo_memory.png" alt="Memory Logo" class="logo">
    </div>

    <h2>Create a new project</h2>

    <form @submit.prevent="createProject">
      <div class="form-group">
        <label for="projectName">Name of project :</label>
        <input 
          id="projectName"
          v-model="formData.name" 
          type="text" 
          required 
          maxlength="50" 
          placeholder="Name of your project"
        >
      </div>

      <div class="form-group">
        <label for="projectDescription">Description :</label>
        <textarea 
          id="projectDescription"
          v-model="formData.description" 
          required 
          maxlength="1000" 
          placeholder="Detailed description of the project..."
          rows="4"
        ></textarea>
      </div>

      <div class="form-group">
        <label for="projectCategory">Category :</label>
        <select 
          id="projectCategory"
          v-model="formData.category_id" 
          required
        >
          <option value="">-- Choose a category --</option>
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
        {{ loading ? 'Creating...' : 'Create the project' }}
      </button>
    </form>

    <!-- Messages de retour -->
    <div v-if="message" :class="messageClass" class="message">
      {{ message }}
      
      <!-- Bouton "Voir tous les projets" après succès -->
      <router-link 
        v-if="showViewAllButton" 
        to="/projects" 
        class="view-all-btn"
      >
        Voir tous les projets
      </router-link>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'ProjectForm',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
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
      messageType: '',
      showViewAllButton: false
    };
  },
  async mounted() {
    await this.loadCategories();
  },
  methods: {
    async loadCategories() {
      // Vérification de l'authentification
      if (!this.authStore.isAuthenticated()) {
        this.$router.push('/login')
        return
      }
      
      const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
      const endpoint = `${baseUrl}?loc=categories`;
      try {
        const response = await fetch(endpoint, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (!response.ok) {
          if (response.status === 401) {
            this.authStore.logout()
            this.$router.push('/login')
            return
          }
          throw new Error(`HTTP ${response.status}`);
        }
        
        const categories = await response.json();
        this.categories = categories;
      } catch (error) {
        console.error('Error loading categories:', error);
      }
    },
    async createProject() {
      this.loading = true;
      this.message = '';
      this.showViewAllButton = false;
      
      // Vérification de l'authentification
      if (!this.authStore.isAuthenticated()) {
        this.$router.push('/login')
        return
      }
      
      try {
        const formData = new FormData();
        formData.append('name', this.formData.name);
        formData.append('description', this.formData.description);
        formData.append('category_id', this.formData.category_id);
        
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/';
        const endpoint = `${baseUrl}?loc=projects&action=create`;
        
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.authStore.token}`
          },
          body: formData
        });
        
        if (!response.ok && response.status === 401) {
          this.authStore.logout()
          this.$router.push('/login')
          return
        }
        
        const result = await response.json();
        if (result.success) {
          this.showMessage(`Projet "${this.formData.name}" créé avec succès !`, 'success');
          this.showViewAllButton = true;
          this.resetForm();
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
      this.formData.name = '';
      this.formData.description = '';
      this.formData.category_id = '';
    },
    showMessage(message, type) {
      this.message = message;
      this.messageClass = type === 'success' ? 'message success' : 'message error';
    }
  }
}
</script>

<style scoped>
.project-form {
  width: 1000px;
  margin: 0 auto;
  padding: 40px;
  background-color: #fff9ef;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.logo-container {
  text-align: left;
  margin-bottom: 30px;
}

.logo {
  max-width: 100px;
}

h2 {
  text-align: center;
  margin-bottom: 30px;
  color: #333;
  font-size: 28px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: bold;
  color: #555;
  font-size: 16px;
}

input, textarea, select {
  width: 100%;
  padding: 15px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 16px;
}

textarea {
  resize: vertical;
  min-height: 120px;
}

button.submit-btn {
  background-color: #ff584a;
  color: white;
  padding: 15px 30px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 18px;
  width: 100%;
  margin-top: 20px;
}

button.submit-btn:hover:not(:disabled) {
  background-color: #0056b3;
}

button.submit-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.message {
  margin-top: 20px;
  padding: 15px;
  border-radius: 8px;
  text-align: center;
  font-weight: bold;
}

.message.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.message.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.view-all-btn {
  display: inline-block;
  margin-top: 15px;
  background-color: #007bff;
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  text-decoration: none;
  font-size: 16px;
  font-weight: 600;
  transition: background-color 0.2s ease;
}

.view-all-btn:hover {
  background-color: #0056b3;
}
</style>