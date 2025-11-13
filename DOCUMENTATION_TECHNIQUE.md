# Documentation Technique - Projet Memory
## Journal de développement et résolution des problèmes

**Période :** Octobre-Novembre 2025  
**Contexte :** Développement de la fonctionnalité de création de projets  
**Étudiant(e) :** Jocelyne  
**Projet académique :** Application Memory (Portfolio de projets)

---

## 📋 **Table des matières**
1. [Architecture du projet](#architecture)
2. [Fonctionnalité développée](#fonctionnalité)
3. [Problèmes rencontrés et solutions](#problèmes)
4. [Code développé](#code)
5. [Apprentissages techniques](#apprentissages)

---

## 🏗️ **Architecture du projet** {#architecture}

### **Structure générale :**
```
Memory/
├── backend/           # API PHP (MVC)
│   ├── index.php     # Router principal
│   ├── controllers/  # Logique métier
│   ├── models/       # Accès données
│   └── config/       # Configuration BDD
├── frontend/         # SPA Vue.js
│   └── src/
│       ├── App.vue   # Composant principal
│       └── components/projects/
└── conception/       # Base de données
```

### **Technologies utilisées :**
- **Backend :** PHP 8.x, MySQL, PDO
- **Frontend :** Vue.js 3, Vite
- **Serveur :** MAMP (Apache + MySQL)
- **Architecture :** REST API + SPA

---

## 🎯 **Fonctionnalité développée** {#fonctionnalité}

### **Objectif :**
Permettre la création de nouveaux projets via une interface Vue.js

### **Composants créés :**
1. **ProjectForm.vue** - Interface de création
2. **Endpoint backend** - API de création
3. **Validation** - Côté client et serveur

---

## 🐛 **Problèmes rencontrés et solutions** {#problèmes}

### **PROBLÈME 1 : CORS (Cross-Origin Resource Sharing)**

#### **Symptômes :**
- Catégories ne se chargent pas
- Projets ne s'affichent pas
- Erreurs de réseau dans la console

#### **Cause :**
```
Frontend Vue.js : http://localhost:5173
Backend PHP     : http://localhost:8888
→ Origines différentes = Blocage CORS
```

#### **Solution appliquée :**
```php
// Dans backend/index.php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
```

#### **Apprentissage :**
CORS = mécanisme de sécurité des navigateurs qui bloque les requêtes entre domaines différents sans autorisation explicite.

---

### **PROBLÈME 2 : Contrainte de base de données NOT NULL**

#### **Symptômes :**
```
SQLSTATE[23000]: Integrity constraint violation: 1048 
Column 'Archive_date' cannot be null
```

#### **Cause technique :**
La colonne `Archive_date` est définie `NOT NULL` dans la BDD mais on essaie d'insérer `NULL`.

#### **Code problématique :**
```php
// Dans Projects_model.php
VALUES (:name, :description, CURDATE(), NULL, :category_id)  // ← NULL refusé
```

#### **Structure BDD problématique :**
```sql
-- Dans Script_SQL.sql
Archive_date DATE NOT NULL  -- ← Interdit NULL
```

#### **Solutions explorées :**

**Solution temporaire appliquée :**
```php
// Date future = projet non archivé
VALUES (:name, :description, CURDATE(), '2099-12-31', :category_id)
```

**Solution recommandée (à faire via phpMyAdmin) :**
```sql
-- Autoriser NULL pour la logique métier
ALTER TABLE Project MODIFY Archive_date DATE NULL;
```

#### **Apprentissage :**
- **Contraintes BDD :** NOT NULL impose une valeur obligatoire
- **Logique métier :** NULL = "pas de valeur" plus logique que date artificielle
- **Évolution :** Parfois il faut adapter la structure BDD aux besoins métier

---

### **PROBLÈME 3 : Filtres PHP dépréciés**

#### **Symptômes :**
```
Warning: Constant FILTER_SANITIZE_STRING is deprecated
```

#### **Cause :**
`FILTER_SANITIZE_STRING` supprimé en PHP 8.1

#### **Solution :**
```php
// Avant (déprécié)
filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

// Après (moderne)
$name = filter_input(INPUT_POST, 'name');
$name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
```

---

## 💻 **Code développé** {#code}

### **1. Composant Vue.js - ProjectForm.vue**

#### **Fonctionnalités :**
- Formulaire réactif avec validation
- Chargement dynamique des catégories
- Gestion d'erreurs utilisateur
- Interface responsive

#### **Code clé :**
```vue
<template>
  <form @submit.prevent="createProject">
    <input v-model="formData.name" required maxlength="50">
    <textarea v-model="formData.description" required maxlength="1000">
    <select v-model="formData.category_id" required>
      <option v-for="category in categories" :value="category.id_Category">
        {{ category.Name_Unique }}
      </option>
    </select>
  </form>
</template>
```

#### **Méthodes importantes :**
```javascript
async createProject() {
  // FormData pour envoi POST
  const formData = new FormData();
  formData.append('name', this.formData.name);
  
  // Appel API
  const response = await fetch('backend/?loc=projects&action=create', {
    method: 'POST',
    body: formData
  });
}
```

### **2. Contrôleur PHP - ProjectsController**

#### **Architecture MVC :**
```php
public function run() {
    $action = filter_input(INPUT_GET, 'action');
    switch ($action) {
        case 'create': return $this->createProject();
        case 'list':   return $this->listProjects();
    }
}
```

#### **Validation des données :**
```php
// Récupération sécurisée
$name = filter_input(INPUT_POST, 'name');
$name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');

// Validation métier
if (empty($name) || strlen($name) > 50) {
    return ['error' => 'Invalid name'];
}
```

### **3. Modèle de données - Projects_model**

#### **Requêtes sécurisées :**
```php
// Vérification catégorie
$checkCategory = $this->PDO->prepare("SELECT id_Category FROM Category WHERE id_Category = :category_id");

// Vérification unicité
$checkName = $this->PDO->prepare("SELECT id_Project FROM Project WHERE Name_Unique = :name");

// Insertion sécurisée
$stmt = $this->PDO->prepare("INSERT INTO Project (...) VALUES (:name, :description, CURDATE(), NULL, :category_id)");
```

---

## 🎓 **Apprentissages techniques** {#apprentissages}

### **1. Architecture MVC en PHP**
- **Model :** Gestion des données (SQL, validation métier)
- **View :** Interface utilisateur (Vue.js)
- **Controller :** Logique métier (validation, orchestration)

### **2. Communication Frontend/Backend**
- **REST API :** Endpoints structurés (`/backend/?loc=projects&action=create`)
- **FormData :** Envoi de données POST depuis JavaScript
- **CORS :** Configuration pour autoriser les appels cross-origin

### **3. Sécurité**
- **Requêtes préparées :** Protection contre injection SQL
- **Validation entrées :** htmlspecialchars(), filter_input()
- **Gestion erreurs :** Messages utilisateur vs logs techniques

### **4. Base de données**
- **Contraintes :** Clés étrangères, unicité
- **Types de données :** DATE, NULL vs valeurs par défaut
- **SQL strict :** Modes MySQL modernes plus rigoureux

### **5. Debug et résolution de problèmes**
- **Mode debug temporaire :** Afficher erreurs techniques
- **Console navigateur :** Inspecter requêtes/réponses
- **Logs serveur :** Tracer les erreurs backend

---

## 🔧 **Techniques de debugging utilisées**

### **1. Debug backend :**
```php
// Mode temporaire pour voir erreurs SQL
return ['error' => 'Debug: ' . $e->getMessage()];

// Logs serveur
error_log('Project creation error: ' . $e->getMessage());
```

### **2. Debug frontend :**
```javascript
// Console pour tracer les appels
console.log('Chargement des catégories...');
console.log('Réponse:', response.status);
console.log('Données reçues:', data);
```

### **3. Test des endpoints :**
```bash
# Test direct via curl
curl "http://localhost:8888/PFR/Memory/backend/?loc=categories"
curl -X POST "backend/?loc=projects&action=create" -d "name=Test&description=Test&category_id=1"
```

---

## 📊 **Résultats obtenus**

### **✅ Fonctionnalités opérationnelles :**
1. Interface de création de projets intuitive
2. Validation côté client et serveur
3. Gestion d'erreurs robuste
4. Rafraîchissement automatique de la liste
5. Architecture propre et maintenable

### **📈 Compétences développées :**
1. **Architecture full-stack** (PHP + Vue.js)
2. **Résolution de problèmes** méthodique
3. **Debugging** multi-couches
4. **Sécurité** web (CORS, injection SQL)
5. **Standards modernes** (PHP 8, Vue 3)

---

## 🆕 **Nouvelles fonctionnalités développées** (Nov 2025)

### **Fonctionnalité : Archivage des projets**

#### **Contexte :**
Permettre de marquer des projets comme "archivés" sans les supprimer, avec conservation de la date d'archivage.

#### **Implémentation technique :**
- **Logique métier :** `Archive_date = NULL` → projet actif | `Archive_date = DATE` → projet archivé
- **Endpoint API :** `GET /backend/?loc=projects&action=archivate&id={id}`
- **Protection :** Impossible d'archiver un projet déjà archivé (exception levée)

#### **Code backend ajouté :**

**Modèle (`Projects_model.php`)** :
```php
public function archiveProject($id) {
    // Vérification existence + statut
    $checkStmt = $this->PDO->prepare("SELECT Archive_date FROM Project WHERE id_Project = :id");
    
    // Protection double archivage
    if ($project['Archive_date'] !== null) {
        throw new Exception('Project is already archived');
    }
    
    // Archivage avec date réelle
    $stmt = $this->PDO->prepare("UPDATE Project SET Archive_date = CURDATE() WHERE id_Project = :id");
}
```

**Contrôleur (`projects.php`)** :
```php
public function archiveProject() {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $success = $model->archiveProject($id);
    return ['success' => true, 'archived_date' => date('Y-m-d')];
}
```

#### **Documentation détaillée :**
→ Voir `DOCUMENTATION_ARCHIVAGE_PROJETS.md` (40 pages)

---

### **Tests unitaires PHPUnit**

#### **Contexte :**
Mise en place de tests automatisés pour valider la fonctionnalité d'archivage.

#### **Configuration :**
```bash
# Installation
cd backend
composer install

# Exécution des tests
vendor/bin/phpunit tests/ProjectsModelTest.php
```

#### **Tests implémentés :**
1. **testNewProjectHasNullArchiveDate** - Vérifie que nouveaux projets ont `Archive_date = NULL`
2. **testArchiveActiveProject** - Teste l'archivage d'un projet actif
3. **testArchiveNonExistentProject** - Gestion des erreurs (projet inexistant)
4. **testArchiveAlreadyArchivedProject** - Protection double archivage (exception)
5. **testGetAllProjectsIncludesArchivedProjects** - Persistance des données

#### **Résultats :**
```
✅ 5 tests passés
✅ 8 assertions validées
⚡ Temps : 63ms
💾 Mémoire : 6 MB
```

---

## 🔧 **Problèmes techniques avancés résolus**

### **PROBLÈME 4 : Configuration MySQL pour PHPUnit (CLI)**

#### **Symptômes :**
```
Database connection failed. Check config/database.php
Tests: 5, Assertions: 0, Failures: 5
```

#### **Causes identifiées :**

**Cause #1 : Variable globale non accessible**
```php
// Problème : $PDO global non chargé dans le contexte du test
global $PDO;  // Ne suffit pas
```

**Solution :**
```php
protected function setUp(): void {
    require_once __DIR__ . '/../config/database.php';
    global $PDO;
    if (!$PDO) {
        $this->fail('Database connection failed');
    }
}
```

**Cause #2 : Port MySQL incorrect**
```php
// MAMP utilise le port 8889, pas 3306
$PDO = new PDO("mysql:host=localhost;port=8889;dbname=Memory", $user, $pass);
```

**Cause #3 : PHP CLI vs PHP Web**
- **PHP Web (Apache)** : Connexion TCP/IP fonctionne
- **PHP CLI (PHPUnit)** : Préfère les sockets Unix

#### **Solution finale : Socket Unix**

**Qu'est-ce qu'un socket Unix ?**
Un fichier système (`/Applications/MAMP/tmp/mysql/mysql.sock`) permettant la communication inter-processus directe, plus rapide et fiable que TCP/IP pour les connexions locales.

**Configuration finale (`config/database.php`)** :
```php
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$PDO = new PDO("mysql:unix_socket=$socket;dbname=$db;charset=utf8", $user, $pass);
```

#### **Comparaison TCP/IP vs Socket Unix :**

| Critère | TCP/IP (`localhost:8889`) | Socket Unix |
|---------|--------------------------|-------------|
| Type | Connexion réseau (stack TCP) | Fichier système |
| Performance | Lent (overhead réseau) | Rapide (direct) |
| Fiabilité CLI | ⚠️ Peut échouer | ✅ Toujours fiable |
| Usage | Connexions distantes | Connexions locales |

#### **Apprentissage :**
- **Environnement CLI ≠ Web** : Comportements différents de PHP
- **Sockets Unix** : Méthode privilégiée pour MySQL local sur macOS/Linux
- **Debugging méthodique** : 4 erreurs résolues en 35 minutes via itération

---

### **PROBLÈME 5 : Migration de données existantes**

#### **Symptômes :**
Tous les projets marqués "ARCHIVÉ" alors qu'ils devraient être actifs.

#### **Cause :**
Anciens projets créés avec `Archive_date = '2099-12-31'` (valeur fictive) considérés comme archivés selon la nouvelle logique (`NULL` = actif).

#### **Solution :**
```sql
-- Réinitialisation des projets actifs
UPDATE Project 
SET Archive_date = NULL 
WHERE Archive_date IS NOT NULL;
```

#### **Apprentissage :**
Lors d'un changement de logique métier, **toujours migrer les données existantes** pour assurer la cohérence.

---

## 📚 **Compétences techniques approfondies**

### **1. Tests automatisés (PHPUnit)**
- **Setup/Teardown** : Préparation et nettoyage des tests
- **Assertions** : `assertTrue()`, `assertEquals()`, `assertNull()`
- **Exceptions** : `expectException()`, `expectExceptionMessage()`
- **Mock/Stub** : Isolation des dépendances (bases de données de test)

### **2. Configuration environnement de développement**
- **MAMP** : Spécificités ports (8888/8889), sockets Unix
- **Composer** : Gestion dépendances PHP (`composer.json`, `vendor/`)
- **CLI vs Web** : Différences de comportement PHP
- **Gitignore** : Exclusion `vendor/`, `node_modules/`, images lourdes

### **3. Conception de base de données**
- **Valeurs NULL** : Utilisation sémantique (absence de valeur vs valeur par défaut)
- **Contraintes** : `NOT NULL`, `UNIQUE`, `FOREIGN KEY`
- **Migration** : `ALTER TABLE MODIFY COLUMN` pour ajuster le schéma
- **Requêtes conditionnelles** : `CASE WHEN ... THEN ... END`

### **4. Architecture REST API**
- **Endpoints cohérents** : `?loc=resource&action=verb&id=value`
- **Codes HTTP** : 200 (OK), 400 (Bad Request), 500 (Server Error)
- **Réponses JSON standardisées** : `{"success": bool, "message": string, "data": object}`
- **Gestion d'erreurs** : Messages utilisateur vs logs techniques

### **5. Méthodologie de debugging**
1. **Identifier** : Lire attentivement le message d'erreur
2. **Comprendre** : Analyser la cause racine (pas juste le symptôme)
3. **Hypothèse** : Formuler une solution potentielle basée sur la théorie
4. **Tester** : Appliquer la solution et vérifier le résultat
5. **Itérer** : Si échec, nouvelle hypothèse avec nouvelles informations
6. **Documenter** : Noter chaque étape pour référence future

---

## 📁 **Organisation de la documentation**

Le projet dispose de plusieurs documentations :

1. **`DOCUMENTATION_TECHNIQUE.md`** (ce fichier)
   - Vue d'ensemble technique du projet
   - Problèmes résolus et solutions
   - Apprentissages généraux

2. **`DOCUMENTATION_ARCHIVAGE_PROJETS.md`** (~40 pages)
   - Documentation complète de la fonctionnalité d'archivage
   - Code détaillé avec explications ligne par ligne
   - Tests PHPUnit avec debugging complet
   - Captures d'écran des erreurs et résultats

3. **`RESTORED_DISCUSSION.md`** (gitignore)
   - Synthèse des discussions techniques
   - Décisions d'architecture
   - Recommandations de sécurité

4. **`RESTORED_CHAT_LOG.md`** (gitignore)
   - Log chronologique des sessions de développement
   - Historique des modifications

5. **`docs/GUIDE_CAPTURES.md`**
   - Instructions pour prendre les captures d'écran
   - Nomenclature et placement des images

---

## 🎯 **Prochaines étapes recommandées**

### **Court terme :**
1. ✅ Archivage des projets (FAIT)
2. ✅ Tests PHPUnit (FAIT)
3. ⏳ Interface frontend pour archiver (bouton dans `ProjectList.vue`)
4. ⏳ Filtrage projets actifs/archivés (endpoints `&status=active|archived`)

### **Moyen terme :**
5. ⏳ Désarchivage (remettre `Archive_date` à `NULL`)
6. ⏳ Implémentation controllers vides (`competences.php`, `notes.php`)
7. ⏳ Tests frontend (Vitest pour `ProjectForm`, `ProjectList`)

### **Long terme :**
8. ⏳ Authentification JWT + sessions
9. ⏳ Variables d'environnement (`.env` pour credentials DB)
10. ⏳ CI/CD (GitHub Actions pour tests automatiques)
11. ⏳ Déploiement production (OVH/Ionos)

---

**Date de dernière mise à jour :** 14 novembre 2025  
**Statut :** 
- ✅ Création de projets opérationnelle
- ✅ Archivage de projets fonctionnel
- ✅ Tests unitaires PHPUnit validés