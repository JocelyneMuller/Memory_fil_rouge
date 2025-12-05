<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <router-link to="/" class="logo-link">
        <img src="/logo_memory.png" alt="Memory Logo" class="sidebar-logo">
      </router-link>
    </div>

    <nav class="sidebar-nav">
      <!-- Dashboard -->
      <router-link 
        to="/" 
        class="nav-item"
        :class="{ active: $route.path === '/' }"
      >
        Dashboard
      </router-link>

      <!-- Competences -->
      <router-link 
        to="/competences" 
        class="nav-item"
        :class="{ active: $route.path.startsWith('/competences') }"
      >
        Competences
      </router-link>

      <!-- Projects -->
      <router-link 
        to="/projects" 
        class="nav-item"
        :class="{ active: $route.path.startsWith('/projects') }"
      >
        Projects
      </router-link>

      <!-- Notes -->
      <router-link 
        to="/notes" 
        class="nav-item"
        :class="{ active: $route.path.startsWith('/notes') }"
      >
        Notes
      </router-link>
    </nav>

    <div class="sidebar-footer">
      <div class="user-login">
        <span class="user-icon">👤</span>
        <span class="user-text">Log in</span>
      </div>
    </div>
  </aside>
</template>

<script>
export default {
  name: 'Sidebar'
}
</script>

<style scoped>
.sidebar {
  width: 350px;
  height: 100vh;
  background-color: #3d1f1f;
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 1000;
}

.sidebar-header {
  padding: 30px 20px;
}

.logo-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
}

.sidebar-logo {
  width: 60px;
  height: 60px;
}

.sidebar-header {
  margin-bottom: 40px;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 15px;
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.nav-item {
  display: block;
  padding: 16px 24px;
  text-decoration: none;
  font-weight: 600;
  font-size: 16px;
  border-radius: 12px;
  transition: all 0.2s ease;
  text-align: center;
  background-color: #ff584a;
  color: #fff;
}

.nav-item:hover {
  background-color: #ff4435;
  transform: translateY(-2px);
}

.nav-item.active {
  background-color: #d0d0d0;
  color: #333;
}

.sidebar-footer {
  padding: 20px 15px;
}

.user-login {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
}

.user-icon {
  font-size: 32px;
  background: #ff584a;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-text {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
}

@media (max-width: 768px) {
  .sidebar {
    width: 80px;
  }
  
  .brand-name,
  .user-text {
    display: none;
  }
  
  .nav-item {
    padding: 12px;
    font-size: 0;
  }
}
</style>
