import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Dashboard from '../views/Dashboard.vue'
import ProjectCreate from '../views/ProjectCreate.vue'
import ProjectsView from '../views/ProjectsView.vue'
import ProjectDetail from '../views/ProjectDetail.vue'
import TeamManagement from '../views/TeamManagement.vue'
import Login from '../views/Login.vue'
import Competences from '../views/Competences.vue'
import Notes from '../views/Notes.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { title: 'Connexion - Memory', requiresAuth: false }
  },
  {
    path: '/',
    name: 'Dashboard',
    component: Dashboard,
    meta: { title: 'Dashboard - Memory', requiresAuth: true }
  },
  {
    path: '/projects',
    name: 'Projects',
    component: ProjectsView,
    meta: { title: 'Projets - Memory', requiresAuth: true }
  },
  {
    path: '/projects/create',
    name: 'ProjectCreate',
    component: ProjectCreate,
    meta: { title: 'Créer un projet - Memory', requiresAuth: true }
  },
  {
    path: '/projects/:id',
    name: 'ProjectDetail',
    component: ProjectDetail,
    meta: { title: 'Détail projet - Memory', requiresAuth: true }
  },
  {
    path: '/team',
    name: 'TeamManagement',
    component: TeamManagement,
    meta: { title: 'Gestion d\'équipe - Memory', requiresAuth: true }
  },
  {
    path: '/competences',
    name: 'Competences',
    component: Competences,
    meta: { title: 'Compétences - Memory', requiresAuth: true }
  },
  {
    path: '/notes',
    name: 'Notes',
    component: Notes,
    meta: { title: 'Notes - Memory', requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guard - Protection des routes
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated()

  // Si la route nécessite l'authentification
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'Login' })
  }
  // Si l'utilisateur est connecté et tente d'accéder à /login
  else if (to.name === 'Login' && isAuthenticated) {
    next({ name: 'Dashboard' })
  }
  // Sinon, laisser passer
  else {
    next()
  }
})

router.afterEach((to) => {
  document.title = to.meta.title || 'Memory'
})

export default router
