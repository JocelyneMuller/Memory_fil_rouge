<template>
  <Transition name="modal">
    <div v-if="show" class="modal-overlay" @click.self="cancel">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div class="icon-assignment">👥</div>
          <h3 class="modal-title">Assigner un utilisateur</h3>
          <button @click="cancel" class="close-btn">✕</button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Loading state -->
          <div v-if="loading" class="loading">
            <div class="spinner"></div>
            <p>Chargement des utilisateurs...</p>
          </div>

          <!-- Error state -->
          <div v-if="error" class="error-message">
            <span class="error-icon">⚠️</span>
            <p>{{ error }}</p>
          </div>

          <!-- Assignment form -->
          <form v-if="!loading && !error" @submit.prevent="submitAssignment" class="assignment-form">
            <!-- User selection -->
            <div class="form-group">
              <label for="user-select" class="form-label">
                Utilisateur <span class="required">*</span>
              </label>
              <select
                id="user-select"
                v-model="selectedUserId"
                :disabled="submitting"
                class="form-select"
                required
              >
                <option value="">-- Sélectionner un utilisateur --</option>
                <option
                  v-for="user in availableUsers"
                  :key="user.id_User"
                  :value="user.id_User"
                >
                  {{ user.Username }} ({{ user.Email_Unique }})
                  <span v-if="user.Role === 'admin'" class="admin-badge">Admin</span>
                </option>
              </select>
            </div>

            <!-- Role selection -->
            <div class="form-group">
              <label for="role-select" class="form-label">
                Rôle <span class="required">*</span>
              </label>
              <div class="role-options">
                <label class="radio-option">
                  <input
                    type="radio"
                    v-model="selectedRole"
                    value="manager"
                    :disabled="submitting"
                    name="role"
                  />
                  <div class="radio-custom">
                    <span class="role-icon">👨‍💼</span>
                    <div class="role-info">
                      <span class="role-name">Chef de Projet</span>
                      <small class="role-description">Peut assigner d'autres utilisateurs</small>
                    </div>
                  </div>
                </label>

                <label class="radio-option">
                  <input
                    type="radio"
                    v-model="selectedRole"
                    value="developer"
                    :disabled="submitting"
                    name="role"
                  />
                  <div class="radio-custom">
                    <span class="role-icon">👨‍💻</span>
                    <div class="role-info">
                      <span class="role-name">Développeur</span>
                      <small class="role-description">Travaille sur le projet</small>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Start date -->
            <div class="form-group">
              <label for="start-date" class="form-label">
                Date de début
              </label>
              <input
                id="start-date"
                type="date"
                v-model="startDate"
                :disabled="submitting"
                class="form-input"
                :min="today"
              />
              <small class="help-text">Optionnel - par défaut aujourd'hui</small>
            </div>

            <!-- Project info -->
            <div class="project-info">
              <h4>📁 {{ projectName }}</h4>
              <p class="project-description">{{ projectDescription }}</p>
            </div>
          </form>
        </div>

        <!-- Footer avec boutons -->
        <div class="modal-footer" v-if="!loading">
          <button @click="cancel" class="btn-cancel" :disabled="submitting">
            Annuler
          </button>
          <button
            @click="submitAssignment"
            class="btn-confirm"
            :disabled="!canSubmit || submitting"
          >
            <span v-if="submitting" class="btn-spinner"></span>
            {{ submitting ? 'Attribution...' : 'Assigner' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: "AssignmentModal",

  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  props: {
    show: {
      type: Boolean,
      required: true
    },
    projectId: {
      type: Number,
      required: true
    },
    projectName: {
      type: String,
      required: true
    },
    projectDescription: {
      type: String,
      default: ''
    }
  },

  emits: ['success', 'cancel', 'error'],

  data() {
    return {
      loading: false,
      submitting: false,
      error: null,
      availableUsers: [],
      selectedUserId: '',
      selectedRole: 'developer',
      startDate: this.today,
      baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8888/PFR/Memory/backend/'
    }
  },

  computed: {
    today() {
      return new Date().toISOString().split('T')[0]
    },

    canSubmit() {
      return this.selectedUserId && this.selectedRole && !this.submitting
    }
  },

  watch: {
    show(newValue) {
      if (newValue) {
        this.loadAvailableUsers()
        this.resetForm()
      }
    }
  },

  methods: {
    resetForm() {
      this.selectedUserId = ''
      this.selectedRole = 'developer'
      this.startDate = this.today
      this.error = null
    },

    async loadAvailableUsers() {
      this.loading = true
      this.error = null

      try {
        if (!this.authStore.isAuthenticated()) {
          throw new Error('Non authentifié')
        }
        const token = this.authStore.token

        const response = await fetch(
          `${this.baseUrl}?loc=projects&action=available_users&project_id=${this.projectId}`,
          {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Erreur lors du chargement')
        }

        if (data.success) {
          this.availableUsers = data.available_users
        } else {
          throw new Error(data.error || 'Réponse invalide')
        }

      } catch (error) {
        console.error('Erreur chargement utilisateurs:', error)
        this.error = error.message || 'Erreur lors du chargement des utilisateurs'
      } finally {
        this.loading = false
      }
    },

    async submitAssignment() {
      if (!this.canSubmit) return

      this.submitting = true
      this.error = null

      try {
        if (!this.authStore.isAuthenticated()) {
          throw new Error('Non authentifié')
        }
        const token = this.authStore.token

        const formData = new FormData()
        formData.append('user_id', this.selectedUserId)
        formData.append('project_id', this.projectId)
        formData.append('role', this.selectedRole)
        if (this.startDate !== this.today) {
          formData.append('start_date', this.startDate)
        }

        const response = await fetch(
          `${this.baseUrl}?loc=projects&action=assign`,
          {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: formData
          }
        )

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error || 'Erreur lors de l\'attribution')
        }

        if (data.success) {
          this.$emit('success', {
            message: data.message,
            assignment: data.assignment
          })
        } else {
          throw new Error(data.error || 'Attribution échouée')
        }

      } catch (error) {
        console.error('Erreur attribution:', error)
        this.error = error.message || 'Erreur lors de l\'attribution'
        this.$emit('error', error.message)
      } finally {
        this.submitting = false
      }
    },

    cancel() {
      if (!this.submitting) {
        this.$emit('cancel')
      }
    },

    handleEscape(event) {
      if (event.key === 'Escape' && this.show && !this.submitting) {
        this.cancel()
      }
    }
  },

  mounted() {
    document.addEventListener('keydown', this.handleEscape)
  },

  unmounted() {
    document.removeEventListener('keydown', this.handleEscape)
  }
}
</script>

<style scoped>
/* Base modal styles (hérite de ConfirmModal) */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  backdrop-filter: blur(2px);
}

.modal-container {
  background-color: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  max-width: 600px;
  width: 95%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 40px;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
  animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
  position: relative;
}

.icon-assignment {
  font-size: 40px;
  line-height: 1;
}

.modal-title {
  font-size: 24px;
  font-weight: bold;
  color: #000;
  margin: 0;
  flex: 1;
}

.close-btn {
  position: absolute;
  right: 0;
  top: -5px;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
  padding: 5px;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background-color: #f0f0f0;
  color: #333;
}

/* Loading & Error states */
.loading {
  text-align: center;
  padding: 40px 20px;
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

.error-message {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
  background-color: #ffe6e6;
  border: 1px solid #ff9999;
  border-radius: 8px;
  color: #d63031;
  margin-bottom: 20px;
}

.error-icon {
  font-size: 20px;
}

/* Form styles */
.assignment-form {
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 25px;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
  font-size: 16px;
}

.required {
  color: #e74c3c;
}

.form-select, .form-input {
  width: 100%;
  padding: 15px 20px;
  border: 2px solid #000;
  border-radius: 25px;
  font-size: 16px;
  transition: all 0.3s ease;
  background-color: #f8f9fa;
}

.form-select:focus, .form-input:focus {
  outline: none;
  border-color: #ff584a;
  background-color: #fff;
  box-shadow: 0 0 0 3px rgba(255, 88, 74, 0.1);
}

.form-select:disabled, .form-input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.help-text {
  display: block;
  color: #666;
  font-size: 14px;
  margin-top: 5px;
}

.admin-badge {
  color: #9b59b6;
  font-weight: 600;
}

/* Role selection */
.role-options {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.radio-option {
  cursor: pointer;
  display: block;
}

.radio-option input[type="radio"] {
  position: absolute;
  opacity: 0;
}

.radio-custom {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  border: 2px solid #ddd;
  border-radius: 12px;
  transition: all 0.2s ease;
  background-color: #fff;
}

.radio-option input[type="radio"]:checked + .radio-custom {
  border-color: #4CAF50;
  background-color: #f0fff0;
}

.radio-custom:hover {
  border-color: #4CAF50;
}

.role-icon {
  font-size: 32px;
}

.role-info {
  flex: 1;
}

.role-name {
  font-weight: 600;
  color: #333;
  display: block;
  font-size: 16px;
}

.role-description {
  color: #666;
  font-size: 14px;
}

/* Project info */
.project-info {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 20px;
  margin-top: 20px;
}

.project-info h4 {
  margin: 0 0 10px 0;
  color: #333;
  font-size: 18px;
}

.project-description {
  margin: 0;
  color: #666;
  line-height: 1.5;
}

/* Footer buttons */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  border-top: 1px solid #eee;
  padding-top: 20px;
  margin-top: 20px;
}

.btn-cancel, .btn-confirm {
  padding: 14px 40px;
  border-radius: 25px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-cancel {
  background-color: #fff;
  color: #333;
  border: 2px solid #000;
}

.btn-cancel:hover:not(:disabled) {
  background-color: #f8f9fa;
  transform: translateY(-2px);
}

.btn-confirm {
  background-color: #ff584a;
  color: #fff;
  border: 2px solid #ff584a;
}

.btn-confirm:hover:not(:disabled) {
  background-color: #ff4435;
  transform: translateY(-2px);
}

.btn-confirm:disabled {
  background-color: #cccccc;
  border-color: #cccccc;
  cursor: not-allowed;
  transform: none;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid #ffffff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>