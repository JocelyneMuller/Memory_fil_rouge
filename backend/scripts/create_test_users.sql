-- =====================================================
-- SCRIPT : Création d'utilisateurs de test
-- =====================================================
-- Date: 31 Décembre 2025
-- Objectif: Créer des utilisateurs pour tester l'attribution de projets
-- Mot de passe pour tous: admin123
-- =====================================================

USE Memory;

-- =====================================================
-- 2 CHEFS DE PROJET
-- =====================================================

INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at) VALUES
('chef_sarah', 'sarah.martin@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('chef_alex', 'alex.durand@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW());

-- =====================================================
-- 3 DÉVELOPPEURS SENIOR
-- =====================================================

INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at) VALUES
('senior_marie', 'marie.bernard@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('senior_tom', 'tom.rousseau@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('senior_lea', 'lea.moreau@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW());

-- =====================================================
-- 2 DÉVELOPPEURS JUNIOR
-- =====================================================

INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at) VALUES
('junior_paul', 'paul.petit@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('junior_julie', 'julie.simon@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW());

-- =====================================================
-- 2 ALTERNANTS
-- =====================================================

INSERT INTO User (Username, Email_Unique, Password_hash, Role, Created_at) VALUES
('alternant_max', 'max.garcia@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('alternant_emma', 'emma.roux@memory.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW());

-- =====================================================
-- VÉRIFICATION
-- =====================================================

-- Afficher tous les nouveaux utilisateurs
SELECT
    id_User,
    Username,
    Email_Unique,
    Role,
    Created_at
FROM User
WHERE Username IN (
    'chef_sarah', 'chef_alex',
    'senior_marie', 'senior_tom', 'senior_lea',
    'junior_paul', 'junior_julie',
    'alternant_max', 'alternant_emma'
)
ORDER BY Username;

-- Compter par type
SELECT
    'Total nouveaux utilisateurs' as Type,
    COUNT(*) as Nombre
FROM User
WHERE Username LIKE 'chef_%'
   OR Username LIKE 'senior_%'
   OR Username LIKE 'junior_%'
   OR Username LIKE 'alternant_%'

UNION ALL

SELECT 'Chefs de projet', COUNT(*)
FROM User WHERE Username LIKE 'chef_%'

UNION ALL

SELECT 'Développeurs senior', COUNT(*)
FROM User WHERE Username LIKE 'senior_%'

UNION ALL

SELECT 'Développeurs junior', COUNT(*)
FROM User WHERE Username LIKE 'junior_%'

UNION ALL

SELECT 'Alternants', COUNT(*)
FROM User WHERE Username LIKE 'alternant_%';

-- =====================================================
-- ASSIGNATIONS DE TEST (OPTIONNEL)
-- =====================================================

-- Assigner les chefs comme managers du premier projet (si il existe)
INSERT IGNORE INTO Manage (User_id_User, Project_id_Project, role_in_project, assigned_by_user_id, status)
SELECT u.id_User, 1, 'manager', 1, 'active'
FROM User u
WHERE u.Username IN ('chef_sarah', 'chef_alex')
AND EXISTS (SELECT 1 FROM Project WHERE id_Project = 1);

-- Assigner quelques développeurs au premier projet (si il existe)
INSERT IGNORE INTO Manage (User_id_User, Project_id_Project, role_in_project, assigned_by_user_id, status)
SELECT u.id_User, 1, 'developer', 1, 'active'
FROM User u
WHERE u.Username IN ('senior_marie', 'junior_paul')
AND EXISTS (SELECT 1 FROM Project WHERE id_Project = 1);

-- Afficher les assignations créées
SELECT
    m.*,
    u.Username,
    p.Name_Unique as Project_Name
FROM Manage m
LEFT JOIN User u ON m.User_id_User = u.id_User
LEFT JOIN Project p ON m.Project_id_Project = p.id_Project
WHERE u.Username IN (
    'chef_sarah', 'chef_alex', 'senior_marie', 'junior_paul'
);

-- =====================================================
-- RÉSULTATS ATTENDUS
-- =====================================================
-- ✅ 9 nouveaux utilisateurs créés
-- ✅ 2 chefs de projet
-- ✅ 3 développeurs senior
-- ✅ 2 développeurs junior
-- ✅ 2 alternants
-- ✅ Assignations de test optionnelles
-- ✅ Tous avec mot de passe: admin123
-- =====================================================