# Memory - Gestionnaire de Projets

Application full-stack de gestion de projets avec Vue.js 3 et PHP 8.

## 🚀 Démarrage Rapide

### Prérequis
- Node.js ^20.19.0 ou >=22.12.0
- PHP 8.0+
- MySQL 8.0+
- MAMP (recommandé pour le développement)

### Installation

1. **Frontend**
```bash
cd frontend
npm install
npm run dev
```

2. **Backend**
- Configurer MAMP avec PHP 8.0+
- Créer la base de données avec `conception/BDD/Script_SQL.sql`
- Configurer les variables d'environnement (voir Configuration)

## 📖 Documentation

### Documentation Principale
- **[DOCUMENTATION_TECHNIQUE.md](./DOCUMENTATION_TECHNIQUE.md)** - Documentation technique complète
- **[DOCUMENTATION_ARCHIVAGE_PROJETS.md](./DOCUMENTATION_ARCHIVAGE_PROJETS.md)** - Système d'archivage
- **[PROBLEMES_SOLUTIONS.md](./PROBLEMES_SOLUTIONS.md)** - Problèmes rencontrés et solutions

### Documentation Spécialisée
- **[docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md)** - Architecture du projet
- **[docs/CONFIGURATION_ENV.md](./docs/CONFIGURATION_ENV.md)** - Configuration des variables d'environnement
- **[docs/PHASE1_NETTOYAGE.md](./docs/PHASE1_NETTOYAGE.md)** - Phase 1 : Nettoyage et configuration
- **[docs/JWT_DOCUMENTATION.md](./docs/JWT_DOCUMENTATION.md)** - Documentation JWT
- **[docs/04_FRONTEND_INTERFACE_ROUTER.md](./docs/04_FRONTEND_INTERFACE_ROUTER.md)** - Interface et routing

## 🏗️ Architecture

```
Memory/
├── backend/                 # API PHP avec architecture MVC
│   ├── controllers/         # Contrôleurs (auth, projects)
│   ├── models/             # Modèles de données
│   ├── middleware/         # Middleware d'authentification
│   ├── debug/              # Fichiers de débogage
│   └── scripts/            # Scripts utilitaires
├── frontend/               # Application Vue.js 3
│   ├── src/
│   │   ├── components/     # Composants réutilisables
│   │   ├── views/          # Vues principales
│   │   ├── stores/         # État global (Pinia)
│   │   └── router/         # Configuration du routeur
├── conception/             # Conception et base de données
│   ├── BDD/               # Scripts SQL
│   └── Maquette/          # Maquettes UI
└── docs/                  # Documentation spécialisée
```

## ⚙️ Configuration

### Variables d'Environnement

**Frontend (.env)**
```env
VITE_API_URL=http://localhost:8888/PFR/Memory/backend/
VITE_AUTH_API_URL=http://localhost:8888/PFR/Memory/backend/?loc=auth&action=
```

**Backend (.env)**
```env
DB_HOST=localhost
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
DB_NAME=memory_db
DB_USER=root
DB_PASS=root
JWT_SECRET=your_jwt_secret_key
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:5174
```

Voir [CONFIGURATION_ENV.md](./docs/CONFIGURATION_ENV.md) pour plus de détails.

## 🚧 État du Développement

### ✅ Phase 1 Terminée (Décembre 2025)
- Nettoyage du code (suppression composants inutilisés)
- Configuration environnement (.env)
- Correction bugs d'affichage projets
- Optimisation authentification JWT
- Documentation mise à jour

### 🔄 Phase 2 En Cours
- **Prochaine étape** : Attribution de projets (chef de projet → développeur)

### 📋 Fonctionnalités Actuelles
- ✅ Authentification JWT
- ✅ Gestion des projets (CRUD)
- ✅ Gestion des catégories
- ✅ Archivage des projets
- ✅ Interface responsive
- ⏳ Attribution des projets

## 🛠️ Technologies

### Frontend
- **Vue.js 3** (Composition API)
- **Pinia** (Gestion d'état)
- **Vue Router 4** (Routage)
- **Vite** (Build tool)

### Backend
- **PHP 8.0+** (MVC)
- **MySQL** (Base de données)
- **PDO** (Accès base de données)
- **JWT** (Authentification)

## 🐛 Résolution de Problèmes

Pour les problèmes courants et leurs solutions, consultez [PROBLEMES_SOLUTIONS.md](./PROBLEMES_SOLUTIONS.md).

## 📝 Changelog
