<template>
  <nav class="navigation">
    <div class="nav-container">
      <div class="nav-brand">
        <img src="/logo_memory.png" alt="Memory Logo" class="nav-logo">
        <span class="brand-name">Memory</span>
      </div>

      <ul class="nav-menu">
        <li>
          <router-link to="/" class="nav-link" :class="{ active: $route.path === '/' }">
            <span class="nav-icon">🏠</span>
            Dashboard
          </router-link>
        </li>
        <li>
          <router-link to="/projects" class="nav-link" :class="{ active: $route.path === '/projects' }">
            <span class="nav-icon">📁</span>
            Projets
          </router-link>
        </li>
        <li>
          <router-link to="/projects/create" class="nav-link" :class="{ active: $route.path === '/projects/create' }">
            <span class="nav-icon">➕</span>
            Créer un projet
          </router-link>
        </li>
        <li>
          <a href="#" class="nav-link disabled">
            <span class="nav-icon">🎯</span>
            Compétences
          </a>
        </li>
        <li>
          <a href="#" class="nav-link disabled">
            <span class="nav-icon">📝</span>
            Notes
          </a>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navigation'
}
</script>

<style scoped>
.navigation {
  background-color: #fff;
  border-bottom: 2px solid #000;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.nav-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 70px;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
}

.nav-logo {
  height: 40px;
  width: auto;
}

.brand-name {
  font-size: 24px;
  font-weight: bold;
  color: #000;
}

.nav-menu {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 10px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  text-decoration: none;
  color: #333;
  font-weight: 500;
  font-size: 16px;
  border-radius: 20px;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.nav-link:hover:not(.disabled) {
  background-color: #f9f9f9;
  border-color: #e0e0e0;
}

.nav-link.active {
  background-color: #ff584a;
  color: #fff;
  border-color: #ff584a;
}

.nav-link.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.nav-icon {
  font-size: 18px;
}

@media (max-width: 968px) {
  .nav-container {
    flex-direction: column;
    height: auto;
    padding: 15px 20px;
    gap: 15px;
  }
  
  .nav-menu {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .nav-link {
    font-size: 14px;
    padding: 8px 16px;
  }
}
</style>
