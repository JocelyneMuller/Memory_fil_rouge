<template>
  <div class="login-layout">
    <!-- Colonne gauche - Slogan -->
    <div class="slogan-section">
      <div class="slogan-card">
        <h1 class="slogan-text">Share.</h1>
        <h1 class="slogan-text">Collaborate.</h1>
        <h1 class="slogan-text">Memorise.</h1>
      </div>
    </div>

    <!-- Colonne droite - Formulaire -->
    <div class="form-section">
      <div class="form-container">
        <!-- Logo Memory -->
        <div class="logo-section">
          <div class="logo-icon">📦</div>
          <h2 class="logo-text">Memory</h2>
        </div>

        <!-- Formulaire de connexion -->
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="input-group">
            <input
              v-model="email"
              type="email"
              placeholder="Email"
              required
              :disabled="authStore.loading"
              class="form-input"
            />
          </div>

          <div class="input-group">
            <input
              v-model="password"
              type="password"
              placeholder="Password"
              required
              :disabled="authStore.loading"
              class="form-input"
            />
          </div>

          <!-- Message d'erreur -->
          <div v-if="authStore.error" class="error-message">
            ⚠️ {{ authStore.error }}
          </div>

          <!-- Bouton de connexion -->
          <button type="submit" class="login-btn" :disabled="authStore.loading">
            <span v-if="!authStore.loading">Login</span>
            <span v-else>Connexion...</span>
          </button>
        </form>

        <!-- Liens -->
        <div class="form-links">
          <a href="#" class="link-forgot">Forgot password ?</a>
        </div>

        <div class="form-footer">
          <p class="register-text">Don't have an account ?</p>
          <a href="#" class="link-register">Register !</a>
        </div>

        <!-- Aide test -->
        <div class="test-hint">
          💡 Test : admin@memory.local / admin123
        </div>
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
/* Layout principal - deux colonnes */
.login-layout {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(135deg, #6d1b7b 0%, #3d1f1f 100%);
}

/* =====================================
   COLONNE GAUCHE - SLOGAN
   ===================================== */
.slogan-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.slogan-card {
  background: #f5f5f5;
  border-radius: 30px;
  padding: 80px 60px;
  max-width: 400px;
  width: 100%;
  text-align: left;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.slogan-text {
  font-size: 48px;
  font-weight: 700;
  color: #000;
  margin: 20px 0;
  line-height: 1.1;
}

/* =====================================
   COLONNE DROITE - FORMULAIRE
   ===================================== */
.form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  background: #f8f9fa;
}

.form-container {
  background: white;
  border-radius: 30px;
  padding: 60px 50px;
  width: 100%;
  max-width: 450px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  text-align: center;
}

/* Logo Memory */
.logo-section {
  margin-bottom: 50px;
}

.logo-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.logo-text {
  font-size: 32px;
  font-weight: 700;
  color: #3d1f1f;
  margin: 0;
}

/* Formulaire */
.login-form {
  margin-bottom: 30px;
}

.input-group {
  margin-bottom: 25px;
}

.form-input {
  width: 100%;
  padding: 18px 25px;
  border: 2px solid #e9ecef;
  border-radius: 25px;
  font-size: 16px;
  background: #f8f9fa;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #ff584a;
  background: white;
  box-shadow: 0 0 0 3px rgba(255, 88, 74, 0.1);
}

.form-input:disabled {
  background: #e9ecef;
  cursor: not-allowed;
  opacity: 0.7;
}

.form-input::placeholder {
  color: #6c757d;
  font-weight: 500;
}

/* Message d'erreur */
.error-message {
  background: #ffe6e6;
  border: 1px solid #ff9999;
  border-radius: 15px;
  padding: 15px 20px;
  color: #d63031;
  font-size: 14px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Bouton de connexion */
.login-btn {
  width: 100%;
  background: #ff584a;
  color: white;
  border: none;
  border-radius: 25px;
  padding: 18px 25px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin: 20px 0;
}

.login-btn:hover:not(:disabled) {
  background: #ff4435;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 88, 74, 0.3);
}

.login-btn:active:not(:disabled) {
  transform: translateY(0);
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Liens */
.form-links {
  margin: 20px 0;
}

.link-forgot {
  color: #ff584a;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.3s ease;
}

.link-forgot:hover {
  color: #ff4435;
  text-decoration: underline;
}

/* Footer formulaire */
.form-footer {
  margin: 40px 0 20px 0;
  text-align: center;
}

.register-text {
  color: #333;
  margin: 0 0 8px 0;
  font-size: 14px;
}

.link-register {
  color: #ff584a;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  transition: color 0.3s ease;
}

.link-register:hover {
  color: #ff4435;
  text-decoration: underline;
}

/* Aide pour les tests */
.test-hint {
  background: #e8f4f8;
  border: 1px solid #bee5eb;
  border-radius: 15px;
  padding: 12px;
  color: #0c5460;
  font-size: 12px;
  margin-top: 20px;
}

/* =====================================
   RESPONSIVE
   ===================================== */
@media (max-width: 768px) {
  .login-layout {
    flex-direction: column;
  }

  .slogan-section {
    padding: 20px;
    flex: none;
  }

  .slogan-card {
    padding: 40px 30px;
    max-width: none;
  }

  .slogan-text {
    font-size: 36px;
    margin: 15px 0;
  }

  .form-section {
    padding: 20px;
    background: #f8f9fa;
  }

  .form-container {
    padding: 40px 30px;
    border-radius: 20px;
  }

  .logo-icon {
    font-size: 40px;
  }

  .logo-text {
    font-size: 28px;
  }
}

@media (max-width: 480px) {
  .slogan-card {
    padding: 30px 20px;
    border-radius: 20px;
  }

  .slogan-text {
    font-size: 28px;
  }

  .form-container {
    padding: 30px 20px;
    border-radius: 15px;
  }

  .form-input, .login-btn {
    padding: 15px 20px;
    border-radius: 20px;
  }
}

/* Animation d'entrée */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.form-container, .slogan-card {
  animation: fadeInUp 0.6s ease-out;
}
</style>