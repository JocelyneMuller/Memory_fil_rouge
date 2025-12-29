<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>🔐 Connexion</h1>
        <p>Accédez à votre espace Memory</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="admin@memory.local"
            required
            :disabled="authStore.loading"
          />
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="Votre mot de passe"
            required
            :disabled="authStore.loading"
          />
        </div>

        <div v-if="authStore.error" class="error-message">
          ❌ {{ authStore.error }}
        </div>

        <button type="submit" class="btn-login" :disabled="authStore.loading">
          <span v-if="!authStore.loading">Se connecter</span>
          <span v-else>Connexion en cours...</span>
        </button>
      </form>

      <div class="login-footer">
        <p class="hint">💡 Compte de test : admin@memory.local / admin123</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('admin@memory.local')
const password = ref('admin123')

async function handleLogin() {
  try {
    const result = await authStore.login(email.value, password.value)
    console.log('Login result:', result)
    
    // Le login a réussi, on a maintenant un token
    // Récupérons les infos utilisateur complètes
    try {
      await authStore.fetchMe()
      console.log('User info loaded:', authStore.user)
    } catch (fetchError) {
      console.log('FetchMe failed, but login succeeded. Using data from login:', result.data.user)
      // Si fetchMe échoue, utilisons les données du login
      authStore.user = result.data.user
    }
    
    // Redirection vers le dashboard
    router.push('/')
  } catch (error) {
    // L'erreur est déjà dans authStore.error
    console.error('Erreur de connexion:', error)
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 40px;
  width: 100%;
  max-width: 440px;
  animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.login-header h1 {
  font-size: 2rem;
  color: #333;
  margin: 0 0 8px 0;
}

.login-header p {
  color: #666;
  margin: 0;
  font-size: 0.95rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
}

.form-group input {
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.error-message {
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 8px;
  padding: 12px;
  color: #c33;
  font-size: 0.9rem;
}

.btn-login {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login-footer {
  margin-top: 24px;
  text-align: center;
}

.hint {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 8px;
  padding: 12px;
  color: #856404;
  font-size: 0.85rem;
  margin: 0;
}
</style>
