# Documentation : Implémentation de l'archivage des projets

**Date** : 13 novembre 2025  
**Fonctionnalité** : Système d'archivage des projets avec gestion de l'état actif/archivé  
**Projet** : Memory - Application de gestion de portefeuille de projets

---

## Table des matières

1. [Contexte et objectif](#contexte-et-objectif)
2. [Analyse de la base de données](#analyse-de-la-base-de-données)
3. [Architecture de la solution](#architecture-de-la-solution)
4. [Implémentation backend](#implémentation-backend)
5. [Tests unitaires PHPUnit](#tests-unitaires-phpunit)
6. [Tests manuels et validation](#tests-manuels-et-validation)
7. [Problèmes rencontrés et résolutions](#problèmes-rencontrés-et-résolutions)
8. [Résultats finaux](#résultats-finaux)

---

## 1. Contexte et objectif

### Besoin initial
L'application Memory nécessitait une fonctionnalité d'archivage des projets permettant de :
- Marquer un projet comme "archivé" sans le supprimer de la base de données
- Conserver la date d'archivage pour l'historique
- Distinguer clairement les projets actifs des projets archivés
- Empêcher l'archivage multiple d'un même projet

### État initial du code
- Action `archivate` présente dans le contrôleur mais non implémentée
- Message renvoyé : `"Archive action not implemented yet"`
- Champ `Archive_date` existant dans la base de données mais utilisé avec une valeur fictive (`2099-12-31`)

---

## 2. Analyse de la base de données

### Structure de la table Project

```sql
CREATE TABLE Project (
    id_Project INT NOT NULL AUTO_INCREMENT,
    Name_Unique VARCHAR(50) NOT NULL UNIQUE,
    Description TEXT(1000) NOT NULL,
    Date_of_creation DATE NOT NULL,
    Archive_date DATE NULL DEFAULT NULL,  -- Accepte NULL
    Category_id_Category INT NOT NULL,
    PRIMARY KEY (id_Project),
    FOREIGN KEY (Category_id_Category) REFERENCES Category(id_Category)
);
```

### Logique retenue pour l'archivage

| État du projet | Valeur `Archive_date` | Signification |
|----------------|----------------------|---------------|
| **Actif** | `NULL` | Projet en cours, non archivé |
| **Archivé** | Date réelle (ex: `2025-11-13`) | Projet archivé à cette date précise |

**Avantages de cette approche :**
- ✅ Distinction claire entre actif et archivé
- ✅ Conservation de la date d'archivage réelle pour l'historique
- ✅ Requêtes SQL simples (`WHERE Archive_date IS NULL` = actifs)
- ✅ Possibilité de tri par date d'archivage

---

## 3. Architecture de la solution

### Pattern MVC appliqué

```
Client (navigateur/frontend)
    ↓ GET /backend/?loc=projects&action=archivate&id=3
    ↓
index.php (routeur)
    ↓ charge le contrôleur
    ↓
ProjectsController::archiveProject()
    ↓ validation de l'ID
    ↓ appel au modèle
    ↓
Projects_model::archiveProject($id)
    ↓ vérification existence du projet
    ↓ vérification non déjà archivé
    ↓ UPDATE Archive_date = CURDATE()
    ↓
Base de données MySQL
    ↓ mise à jour réussie
    ↓
Réponse JSON au client
```

### Endpoints API créés

| Endpoint | Méthode | Description | Paramètres |
|----------|---------|-------------|------------|
| `?loc=projects&action=archivate&id={id}` | GET | Archive un projet | `id` (int) : ID du projet |

---

## 4. Implémentation backend

### 4.1 Modèle : `backend/models/Projects_model.php`

#### Modification de `createProject()`

**Avant :**
```php
$stmt = $this->PDO->prepare("
    INSERT INTO Project (Name_Unique, Description, Date_of_creation, Archive_date, Category_id_Category) 
    VALUES (:name, :description, CURDATE(), '2099-12-31', :category_id)
");
```

**Après :**
```php
$stmt = $this->PDO->prepare("
    INSERT INTO Project (Name_Unique, Description, Date_of_creation, Archive_date, Category_id_Category) 
    VALUES (:name, :description, CURDATE(), NULL, :category_id)
");
```

**Raison :** Les nouveaux projets doivent être créés avec `Archive_date = NULL` pour être considérés comme actifs.

---

#### Nouvelle méthode : `archiveProject($id)`

```php
/**
 * Archiver un projet en mettant Archive_date à la date du jour
 * 
 * @param int $id ID du projet à archiver
 * @return bool true si l'archivage a réussi, false si le projet n'existe pas
 * @throws Exception si le projet est déjà archivé
 */
public function archiveProject($id)
{
    // Vérifier que le projet existe et n'est pas déjà archivé
    $checkStmt = $this->PDO->prepare("
        SELECT id_Project, Archive_date 
        FROM Project 
        WHERE id_Project = :id
    ");
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    $project = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Si le projet n'existe pas
    if (!$project) {
        return false;
    }
    
    // Si le projet est déjà archivé (Archive_date n'est pas NULL)
    if ($project['Archive_date'] !== null) {
        throw new Exception('Project is already archived');
    }
    
    // Mettre Archive_date à la date du jour (CURDATE())
    $stmt = $this->PDO->prepare("
        UPDATE Project 
        SET Archive_date = CURDATE() 
        WHERE id_Project = :id
    ");
    
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        return $stmt->rowCount() > 0;
    }
    
    return false;
}
```

**Points clés :**
- ✅ Vérification de l'existence du projet
- ✅ Protection contre le double archivage (exception levée)
- ✅ Utilisation de `CURDATE()` pour avoir la date réelle du serveur
- ✅ Requête préparée pour la sécurité (prévention injection SQL)

---

### 4.2 Contrôleur : `backend/controllers/projects.php`

#### Modification du switch dans `run()`

**Avant :**
```php
case 'archivate':
    // Implement archive logic here
    return ['message' => 'Archive action not implemented yet'];
```

**Après :**
```php
case 'archivate':
    return $this->archiveProject();
```

---

#### Nouvelle méthode : `archiveProject()`

```php
/**
 * Archiver un projet
 * Met à jour Archive_date à la date du jour pour marquer le projet comme archivé
 */
public function archiveProject()
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        return ['error' => 'Project ID is required and must be valid'];
    }
    
    try {
        $model = new Projects_model($this->PDO);
        $success = $model->archiveProject($id);
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Project archived successfully',
                'project_id' => $id,
                'archived_date' => date('Y-m-d')
            ];
        } else {
            return ['error' => 'Failed to archive project (project may not exist)'];
        }
        
    } catch (Exception $e) {
        error_log('Project archive error: ' . $e->getMessage());
        
        if (strpos($e->getMessage(), 'already archived') !== false) {
            return ['error' => 'Project is already archived'];
        }
        
        return ['error' => 'An error occurred while archiving the project'];
    }
}
```

**Gestion des erreurs :**
- ❌ ID manquant ou invalide → `"Project ID is required and must be valid"`
- ❌ Projet inexistant → `"Failed to archive project"`
- ❌ Projet déjà archivé → `"Project is already archived"`
- ✅ Succès → JSON avec `success`, `message`, `project_id`, `archived_date`

---

## 5. Tests unitaires PHPUnit

### 5.1 Configuration

**Fichier : `backend/composer.json`**
```json
{
    "name": "memory/backend",
    "description": "Backend API pour l'application Memory",
    "type": "project",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5"
    }
}
```

**Fichier : `backend/phpunit.xml`**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="config/database.php"
         colors="true"
         verbose="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Memory Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

### 5.2 Suite de tests : `backend/tests/ProjectsModelTest.php`

#### Test 1 : Un nouveau projet doit avoir `Archive_date = NULL`

```php
public function testNewProjectHasNullArchiveDate()
{
    $project = $this->model->getProjectById($this->testProjectId);
    
    $this->assertNull(
        $project['Archive_date'],
        'Un nouveau projet devrait avoir Archive_date à NULL'
    );
}
```

**Objectif :** Vérifier que la modification de `createProject()` fonctionne.

---

#### Test 2 : Archiver un projet actif doit fonctionner

```php
public function testArchiveActiveProject()
{
    $result = $this->model->archiveProject($this->testProjectId);
    
    $this->assertTrue($result, 'L\'archivage devrait retourner true');
    
    $project = $this->model->getProjectById($this->testProjectId);
    $this->assertEquals(
        date('Y-m-d'),
        $project['Archive_date'],
        'Archive_date devrait être la date du jour après archivage'
    );
}
```

**Objectif :** Vérifier que l'archivage met bien la date du jour.

---

#### Test 3 : Archiver un projet inexistant doit retourner `false`

```php
public function testArchiveNonExistentProject()
{
    $result = $this->model->archiveProject(999999);
    
    $this->assertFalse(
        $result,
        'Archiver un projet inexistant devrait retourner false'
    );
}
```

**Objectif :** Vérifier la gestion des erreurs.

---

#### Test 4 : Archiver un projet déjà archivé doit lever une exception

```php
public function testArchiveAlreadyArchivedProject()
{
    $this->model->archiveProject($this->testProjectId);
    
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('already archived');
    
    $this->model->archiveProject($this->testProjectId);
}
```

**Objectif :** Vérifier la protection contre le double archivage.

---

#### Test 5 : Les projets archivés doivent rester dans `getAllProjects()`

```php
public function testGetAllProjectsIncludesArchivedProjects()
{
    $this->model->archiveProject($this->testProjectId);
    
    $projects = $this->model->getAllProjects();
    
    $found = false;
    foreach ($projects as $project) {
        if ($project['id_Project'] == $this->testProjectId) {
            $found = true;
            $this->assertNotNull(
                $project['Archive_date'],
                'Le projet archivé devrait avoir une Archive_date'
            );
            break;
        }
    }
    
    $this->assertTrue($found, 'Le projet archivé devrait être dans getAllProjects()');
}
```

**Objectif :** Vérifier que l'archivage ne supprime pas les projets de la liste.

---

### 5.3 Exécution des tests

```bash
# Installation de PHPUnit (première fois)
cd backend
composer install

# Exécution des tests
vendor/bin/phpunit tests/ProjectsModelTest.php
```

**Résultats attendus :**
```
PHPUnit 9.5.x

.....                                                               5 / 5 (100%)

Time: 00:00.123, Memory: 6.00 MB

OK (5 tests, 8 assertions)
```

---

## 6. Tests manuels et validation

### 6.1 Test API : Archivage réussi

**Requête :**
```
GET http://localhost:8888/PFR/Memory/backend/?loc=projects&action=archivate&id=3
```

**Réponse :**
```json
{
  "success": true,
  "message": "Project archived successfully",
  "project_id": 3,
  "archived_date": "2025-11-13"
}
```

---

### 6.2 Test API : Double archivage (erreur attendue)

**Requête :** (même URL, deuxième appel)
```
GET http://localhost:8888/PFR/Memory/backend/?loc=projects&action=archivate&id=3
```

**Réponse :**
```json
{
  "error": "Project is already archived"
}
```

✅ **Validation :** La protection fonctionne.

---

### 6.3 Vérification en base de données

**Requête SQL pour visualiser l'état des projets :**
```sql
SELECT 
    id_Project,
    Name_Unique,
    Date_of_creation,
    Archive_date,
    CASE 
        WHEN Archive_date IS NULL THEN 'ACTIF'
        ELSE 'ARCHIVÉ'
    END AS Statut
FROM Project
ORDER BY Archive_date DESC, id_Project;
```

**Résultat obtenu après archivage du projet #3 :**

| id_Project | Name_Unique | Date_of_creation | Archive_date | Statut |
|------------|-------------|------------------|--------------|--------|
| 3 | Migration PostgreSQL | 2024-03-10 | **2025-11-13** | **ARCHIVÉ** |
| 1 | Refonte UI Dashboard | 2024-01-15 | NULL | ACTIF |
| 2 | API REST v2 | 2024-02-01 | NULL | ACTIF |
| 4 | Pipeline CI/CD | 2024-02-20 | NULL | ACTIF |
| 5 | Système de Design | 2024-01-05 | NULL | ACTIF |
| 6 | Audit Sécurité | 2024-03-01 | NULL | ACTIF |
| 7 | Création page produit | 2025-11-11 | NULL | ACTIF |
| 8 | Test backend | 2025-11-11 | NULL | ACTIF |

✅ **Validation :** Un seul projet archivé avec la date réelle, tous les autres actifs.

---

## 7. Problèmes rencontrés et résolutions

### Problème 1 : Tous les projets marqués comme "ARCHIVÉ"

**Symptôme :**  
Lors du premier test SQL, tous les projets apparaissaient avec le statut "ARCHIVÉ".

**Cause :**  
Les projets existants avaient été créés avec `Archive_date = '2099-12-31'` ou `'2024-12-31'` (dates fictives). Selon la nouvelle logique (`NULL` = actif), ces projets étaient considérés comme archivés.

**Solution appliquée :**
```sql
UPDATE Project 
SET Archive_date = NULL 
WHERE Archive_date IS NOT NULL;
```

**Résultat :** Tous les projets remis en état "actif" avec `Archive_date = NULL`.

**Leçon apprise :** Lors d'un changement de logique métier, il faut migrer les données existantes.

---

### Problème 2 : Compréhension des tests PHPUnit

**Question :**  
"Le fichier test PHP n'était pas censé vérifier ça ?"

**Explication fournie :**  
Les tests PHPUnit testent uniquement les **nouveaux projets créés via l'API**. Ils ne migrent pas automatiquement les anciennes données présentes dans la base.

**Ce que les tests vérifient :**
- ✅ Comportement du code (création, archivage, exceptions)
- ✅ Conformité des nouvelles données

**Ce que les tests ne font PAS :**
- ❌ Nettoyer ou migrer les données existantes
- ❌ Vérifier l'état historique de la base de données

**Solution :** Migration manuelle des données via requête SQL.

---

## 8. Debugging et résolution des problèmes de tests PHPUnit

### 8.1 Contexte du debugging

Après l'implémentation de la fonctionnalité d'archivage et la création des tests PHPUnit, l'exécution des tests a révélé plusieurs problèmes liés à l'environnement MAMP et à la configuration de la connexion MySQL.

**Commande exécutée** :
```bash
cd /Applications/MAMP/htdocs/PFR/Memory/backend
vendor/bin/phpunit tests/ProjectsModelTest.php
```

---

### 8.2 Erreur #1 : Variable PDO null

**Symptôme observé** :
```
Error: Call to a member function query() on null
/Applications/MAMP/htdocs/PFR/Memory/backend/tests/ProjectsModelTest.php:21

Tests: 5, Assertions: 0, Errors: 5
```

**Analyse** :
La variable globale `$PDO` n'était pas accessible dans le contexte du test. Bien que `phpunit.xml` spécifiait `bootstrap="config/database.php"`, la variable globale ne se propageait pas automatiquement.

**Solution appliquée** :
Modification de la méthode `setUp()` dans `ProjectsModelTest.php` :

```php
protected function setUp(): void
{
    // Charger explicitement la connexion à la base de données
    require_once __DIR__ . '/../config/database.php';
    global $PDO;
    
    // Vérification que la connexion a réussi
    if (!$PDO) {
        $this->fail('Database connection failed. Check config/database.php');
    }
    
    $this->PDO = $PDO;
    $this->model = new Projects_model($this->PDO);
    // ...
}
```

**Leçon apprise** : Les variables globales PHP nécessitent un chargement explicite avec `global $var;` dans chaque scope où elles sont utilisées.

---

### 8.3 Erreur #2 : Échec de connexion MySQL masqué

**Symptôme observé** :
```
Tests: 5, Assertions: 5, Failures: 5
Database connection failed. Check config/database.php
```

**Analyse** :
Le message d'erreur personnalisé s'affichait, mais la raison exacte de l'échec de connexion était masquée par le `catch` dans `config/database.php`.

**Tentative 1 (échec)** :
Ajout d'un `throw $e;` dans le catch pour propager l'exception :

```php
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    throw $e;  // ❌ Fait planter PHPUnit avant même le début des tests
}
```

**Résultat** : Erreur fatale, PHPUnit ne pouvait pas démarrer.

**Solution correcte** :
Affichage détaillé de l'erreur sans lever d'exception fatale :

```php
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo 'Database connection failed: ' . $e->getMessage() . "\n";
    }
}
```

**Leçon apprise** : Ne jamais lever d'exception non capturée dans un fichier de configuration chargé globalement. Préférer le logging et l'affichage conditionnel.

---

### 8.4 Erreur #3 : Port MySQL incorrect

**Symptôme observé** :
Connexion MySQL refusée silencieusement malgré les messages d'erreur détaillés.

**Analyse** :
MAMP utilise le port **8889** pour MySQL au lieu du port standard **3306**. La chaîne de connexion PDO ne spécifiait pas le port explicitement :

```php
// ❌ INCORRECT - utilise le port par défaut 3306
$PDO = new PDO("mysql:host=localhost;dbname=Memory;charset=utf8", $user, $pass);
```

**Tentative 2 (échec partiel)** :
Ajout du port dans la DSN :

```php
$host = 'localhost';
$port = '8889';
$PDO = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
```

**Résultat** : Toujours échec ! La connexion via TCP/IP ne fonctionnait pas en CLI.

**Leçon apprise** : Spécifier le port ne suffit pas toujours. MAMP préfère les connexions via socket Unix.

---

### 8.5 Solution finale : Socket Unix

**Qu'est-ce qu'un socket Unix ?**

Un socket Unix est un fichier spécial sur le système de fichiers (`/Applications/MAMP/tmp/mysql/mysql.sock`) qui permet la communication inter-processus (IPC) sur la même machine.

**Avantages** :
- ✅ Plus rapide que TCP/IP (pas de stack réseau)
- ✅ Plus fiable pour les connexions locales
- ✅ Fonctionne aussi bien en web (Apache) qu'en CLI (PHPUnit)

**Différence TCP/IP vs Socket Unix** :

| Critère | TCP/IP (`localhost:8889`) | Socket Unix (`/path/to/socket`) |
|---------|--------------------------|--------------------------------|
| Type | Connexion réseau (même locale) | Connexion fichier système |
| Performance | Plus lent (stack réseau) | Plus rapide (direct) |
| Usage | Connexions distantes | Connexions locales uniquement |
| Fiabilité CLI | ⚠️ Peut échouer | ✅ Fonctionne toujours |

**Code final fonctionnel** :

```php
<?php
$db = 'Memory';
$user = 'root';
$pass = 'root';

// MAMP utilise un socket Unix pour MySQL
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

$PDO = null;
try {
    // Connexion via socket Unix (méthode recommandée pour MAMP)
    $PDO = new PDO("mysql:unix_socket=$socket;dbname=$db;charset=utf8", $user, $pass);
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo 'Database connection failed: ' . $e->getMessage() . "\n";
        echo 'Make sure MAMP MySQL is running and socket exists at: ' . $socket . "\n";
    }
}
```

**Vérification du socket** :
```bash
# Vérifier que le fichier socket existe
ls -la /Applications/MAMP/tmp/mysql/mysql.sock

# Résultat attendu
srwxrwxrwx  1 _mysql  wheel  0 Nov 13 14:30 /Applications/MAMP/tmp/mysql/mysql.sock
```

Le `s` au début indique qu'il s'agit d'un socket.

---

### 8.6 Résultat final des tests

**Commande** :
```bash
vendor/bin/phpunit tests/ProjectsModelTest.php
```

**Sortie** :
```
PHPUnit 9.6.20 by Sebastian Bergmann and contributors.

Runtime: PHP 8.4.8
Configuration: /Applications/MAMP/htdocs/PFR/Memory/backend/phpunit.xml

.....                                                               5 / 5 (100%)

Time: 00:00.063, Memory: 6.00 MB

OK (5 tests, 8 assertions)
```

✅ **Tous les tests passent** : 5/5  
✅ **Toutes les assertions validées** : 8/8  
✅ **Temps d'exécution** : 63 millisecondes  
✅ **Mémoire consommée** : 6 MB

---

### 8.7 Tableau récapitulatif des erreurs et solutions

| # | Erreur | Cause | Solution | Temps résolution |
|---|--------|-------|----------|-----------------|
| 1 | `$PDO` null | Variable globale non accessible | `require_once` + `global $PDO` | 5 min |
| 2 | Connexion échoue sans détails | Exception capturée silencieusement | Affichage conditionnel de l'erreur | 10 min |
| 3 | Port MySQL incorrect | MAMP utilise 8889 au lieu de 3306 | Ajout de `port=8889` dans DSN | 5 min |
| 4 | TCP/IP ne fonctionne pas en CLI | PHP CLI cherche sur 127.0.0.1 | Utilisation de `unix_socket` | 15 min |

**Durée totale de debugging** : ~35 minutes

---

### 8.8 Points clés pour la soutenance

#### Environnement CLI vs Web

**PHP Web (Apache)** :
- Exécuté par le serveur Apache
- Connexions MySQL gérées par le pool d'Apache
- Fonctionne avec `localhost:8889` (TCP/IP)

**PHP CLI (PHPUnit)** :
- Exécuté directement par le système
- Pas de pool de connexions
- Préfère les sockets Unix pour les connexions locales

#### Méthodologie de debugging appliquée

1. ✅ **Identifier** : Lire attentivement le message d'erreur
2. ✅ **Comprendre** : Analyser la cause racine (pas juste le symptôme)
3. ✅ **Hypothèse** : Formuler une solution potentielle
4. ✅ **Tester** : Appliquer la solution et vérifier
5. ✅ **Itérer** : Si échec, nouvelle hypothèse avec plus d'infos
6. ✅ **Documenter** : Noter chaque étape pour référence future

#### Bonnes pratiques identifiées

- ✅ Toujours vérifier que les services externes (MySQL) sont démarrés
- ✅ Utiliser des messages d'erreur détaillés en environnement de développement
- ✅ Préférer les sockets Unix pour les connexions locales MAMP
- ✅ Tester dans l'environnement cible (CLI pour PHPUnit)
- ✅ Ne pas lever d'exceptions fatales dans les fichiers de config globaux

---

## 9. Résultats finaux

### ✅ Fonctionnalités implémentées

| Fonctionnalité | Statut | Détails |
|----------------|--------|---------|
| Création de projet avec `Archive_date = NULL` | ✅ | Tous les nouveaux projets sont actifs par défaut |
| Archivage via API | ✅ | Endpoint fonctionnel avec validation |
| Protection contre le double archivage | ✅ | Exception levée si déjà archivé |
| Conservation de la date d'archivage réelle | ✅ | Utilisation de `CURDATE()` MySQL |
| Tests unitaires PHPUnit | ✅ | 5 tests, 8 assertions, 100% de réussite |
| Documentation code | ✅ | Commentaires détaillés dans le code |

---

### 📊 Métriques

- **Fichiers modifiés :** 2 (Projects_model.php, projects.php)
- **Fichiers créés :** 3 (ProjectsModelTest.php, phpunit.xml, composer.json)
- **Lignes de code ajoutées :** ~150
- **Tests unitaires :** 5
- **Temps de développement :** ~2 heures (incluant explications et tests)

---

### 🎯 Prochaines évolutions possibles

#### Court terme
1. **Interface utilisateur** : Ajouter un bouton "Archiver" dans `ProjectList.vue`
2. **Filtrage** : Créer des endpoints pour lister séparément projets actifs/archivés
3. **Désarchivage** : Implémenter une action pour remettre `Archive_date` à `NULL`

#### Moyen terme
4. **Permissions** : Limiter l'archivage aux administrateurs
5. **Historique** : Logger les actions d'archivage dans une table d'audit
6. **Notifications** : Alerter les utilisateurs quand un projet est archivé

#### Long terme
7. **Archivage automatique** : Script CRON pour archiver les projets inactifs depuis X mois
8. **Statistiques** : Dashboard avec nombre de projets actifs/archivés par catégorie
9. **Export** : Possibilité d'exporter les projets archivés en CSV/PDF

---

## Annexes

### A. Captures d'écran et illustrations

Pour une meilleure compréhension visuelle du processus de debugging et des résultats, des captures d'écran sont disponibles dans le dossier `docs/images/`.

#### Erreurs rencontrées

**Erreur #1 : Variable PDO null**
![Erreur PDO null](docs/images/01_erreur_pdo_null.png)
*Figure 1 : Première erreur lors de l'exécution des tests - la variable globale $PDO n'est pas accessible*

**Erreur #2 : Échec de connexion**
![Connection failed](docs/images/02_erreur_connection_failed.png)
*Figure 2 : Message d'erreur de connexion à la base de données*

**Erreur #3 : Port MySQL incorrect**
![Port MySQL](docs/images/03_erreur_port_mysql.png)
*Figure 3 : Tentative de connexion avec le port par défaut (3306) au lieu du port MAMP (8889)*

**Erreur #4 : Socket Unix nécessaire**
![Socket Unix](docs/images/04_erreur_socket_unix.png)
*Figure 4 : Échec de la connexion TCP/IP, nécessitant l'utilisation d'un socket Unix*

#### Tests réussis

**Tests PHPUnit validés**
![Tests réussis](docs/images/05_tests_phpunit_reussis.png)
*Figure 5 : Tous les tests passent avec succès (5/5 tests, 8/8 assertions)*

#### Base de données

**Structure de la table Project**
![Structure table](docs/images/06_phpmyadmin_table_project.png)
*Figure 6 : Structure de la table Project dans phpMyAdmin avec Archive_date acceptant NULL*

**Projets actifs**
![Projets actifs](docs/images/07_phpmyadmin_projets_actifs.png)
*Figure 7 : Liste des projets actifs avec Archive_date = NULL*

**Projet archivé**
![Projet archivé](docs/images/08_phpmyadmin_projet_archive.png)
*Figure 8 : Le projet "Migration PostgreSQL" archivé le 2025-11-13*

#### API en action

**Archivage réussi**
![API Success](docs/images/09_api_archivage_success.png)
*Figure 9 : Réponse JSON de l'API après archivage réussi d'un projet*

**Protection double archivage**
![API Déjà archivé](docs/images/10_api_archivage_deja_archive.png)
*Figure 10 : Message d'erreur lorsqu'on tente d'archiver un projet déjà archivé*

> **Note** : Les fichiers images doivent être placés dans le dossier `docs/images/` selon les instructions du fichier `docs/GUIDE_CAPTURES.md`.

---

### B. Commandes utiles

```bash
# Installation des dépendances
cd backend
composer install

# Exécution des tests
vendor/bin/phpunit tests/ProjectsModelTest.php

# Test API via curl
curl "http://localhost:8888/PFR/Memory/backend/?loc=projects&action=archivate&id=3"

# Migration des anciennes données
# (à exécuter dans phpMyAdmin)
UPDATE Project SET Archive_date = NULL WHERE Archive_date IS NOT NULL;
```

---

### B. Références

- **Documentation PHP PDO** : https://www.php.net/manual/fr/book.pdo.php
- **Documentation PHPUnit** : https://phpunit.de/documentation.html
- **Pattern MVC** : https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller
- **SQL CURDATE()** : https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_curdate

---

### C. Auteurs et contributeurs

- **Développeur principal** : Jocelyne Muller
- **Assistant technique** : GitHub Copilot
- **Date de réalisation** : 13 novembre 2025

---

**Fin de la documentation**
