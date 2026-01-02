<?php
/**
 * Modèle User - Gestion des utilisateurs dans la base de données
 * 
 * Ce modèle gère toutes les opérations liées aux utilisateurs :
 * - Récupération d'utilisateur par email ou ID
 * - Création de nouveaux comptes (inscription)
 * - Vérification des mots de passe
 * - Gestion des rôles (admin/user)
 * 
 * SÉCURITÉ :
 * - Utilise des requêtes préparées (protection SQL injection)
 * - Hash des mots de passe avec password_hash() (bcrypt)
 * - Validation des données avant insertion
 */

class User_model {
    
    /**
     * Connexion à la base de données
     * @var PDO
     */
    private $db;
    
    /**
     * Constructeur - Injection de dépendance PDO
     * 
     * @param PDO $db - Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Récupère un utilisateur par son email
     * Utilisé principalement lors de la connexion
     * 
     * @param string $email - Email de l'utilisateur à rechercher
     * @return array|false Données utilisateur ou false si non trouvé
     * 
     * @example
     * $user = $userModel->findByEmail('admin@memory.local');
     * if ($user) {
     *     echo $user['Username']; // "admin"
     * }
     */
    public function findByEmail($email) {
        try {
            // Requête préparée pour éviter les injections SQL
            $query = "SELECT * FROM User WHERE Email_Unique = :email LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            // Retourne le premier résultat ou false
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("Erreur findByEmail : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un utilisateur par son ID
     * Utilisé pour récupérer les infos d'un utilisateur authentifié
     * 
     * @param int $userId - ID de l'utilisateur
     * @return array|false Données utilisateur ou false si non trouvé
     */
    public function findById($userId) {
        try {
            $query = "SELECT * FROM User WHERE id_User = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("Erreur findById : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un utilisateur par son username
     * Utilisé pour vérifier l'unicité du username lors de l'inscription
     * 
     * @param string $username - Nom d'utilisateur
     * @return array|false Données utilisateur ou false si non trouvé
     */
    public function findByUsername($username) {
        try {
            $query = "SELECT * FROM User WHERE Username = :username LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("Erreur findByUsername : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crée un nouvel utilisateur dans la base de données
     * Utilisé lors de l'inscription (accessible uniquement à l'admin)
     * 
     * @param array $userData - Données du nouvel utilisateur
     *        - username : Nom d'utilisateur unique
     *        - email : Email unique
     *        - password : Mot de passe en clair (sera hashé)
     *        - role : 'admin' ou 'user' (défaut: 'user')
     * 
     * @return array Résultat avec success et message/data
     * 
     * @example
     * $result = $userModel->create([
     *     'username' => 'johndoe',
     *     'email' => 'john@example.com',
     *     'password' => 'SecurePass123',
     *     'role' => 'user'
     * ]);
     */
    public function create($userData) {
        try {
            // Validation des données requises
            if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
                return [
                    'success' => false,
                    'error' => 'Tous les champs sont requis (username, email, password)'
                ];
            }
            
            // Vérifier que l'email n'existe pas déjà
            if ($this->findByEmail($userData['email'])) {
                return [
                    'success' => false,
                    'error' => 'Cet email est déjà utilisé'
                ];
            }
            
            // Vérifier que le username n'existe pas déjà
            if ($this->findByUsername($userData['username'])) {
                return [
                    'success' => false,
                    'error' => 'Ce nom d\'utilisateur est déjà pris'
                ];
            }
            
            // Validation du format email
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Format d\'email invalide'
                ];
            }
            
            // Validation de la longueur du mot de passe
            if (strlen($userData['password']) < 6) {
                return [
                    'success' => false,
                    'error' => 'Le mot de passe doit contenir au moins 6 caractères'
                ];
            }
            
            // Hash sécurisé du mot de passe avec bcrypt
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
            
            // Définir le rôle par défaut si non spécifié
            $role = $userData['role'] ?? 'user';
            
            // Validation du rôle
            if (!in_array($role, ['admin', 'user'])) {
                return [
                    'success' => false,
                    'error' => 'Rôle invalide (doit être admin ou user)'
                ];
            }
            
            // Insertion dans la base de données avec requête préparée
            $query = "INSERT INTO User (Username, Email_Unique, Password_hash, Role) 
                     VALUES (:username, :email, :password_hash, :role)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $userData['email'], PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            
            $stmt->execute();
            
            // Récupérer l'ID du nouvel utilisateur créé
            $newUserId = $this->db->lastInsertId();
            
            // Récupérer les données complètes du nouvel utilisateur
            $newUser = $this->findById($newUserId);
            
            // Supprimer le hash du mot de passe avant de renvoyer les données
            unset($newUser['Password_hash']);
            
            return [
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'user' => $newUser
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur création utilisateur : " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la création du compte'
            ];
        }
    }
    
    /**
     * Vérifie si un mot de passe correspond au hash stocké
     * Utilisé lors de la connexion pour vérifier les identifiants
     * 
     * @param string $password - Mot de passe en clair à vérifier
     * @param string $hash - Hash stocké dans la base de données
     * @return bool True si le mot de passe correspond
     * 
     * @example
     * $user = $userModel->findByEmail('user@example.com');
     * if ($userModel->verifyPassword('password123', $user['Password_hash'])) {
     *     // Mot de passe correct
     * }
     */
    public function verifyPassword($password, $hash) {
        // password_verify() compare de manière sécurisée le mot de passe avec son hash
        return password_verify($password, $hash);
    }
    
    /**
     * Récupère tous les utilisateurs (pour l'admin)
     * Utilisé dans l'espace d'administration
     * 
     * @return array Liste de tous les utilisateurs
     */
    public function getAll() {
        try {
            $query = "SELECT id_User, Username, Email_Unique, Role, Created_at, Updated_at 
                     FROM User 
                     ORDER BY Created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur getAll users : " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Met à jour le mot de passe d'un utilisateur
     * Utilisé pour la fonctionnalité "Mot de passe oublié"
     * 
     * @param int $userId - ID de l'utilisateur
     * @param string $newPassword - Nouveau mot de passe en clair
     * @return bool True si la mise à jour a réussi
     */
    public function updatePassword($userId, $newPassword) {
        try {
            // Validation de la longueur du mot de passe
            if (strlen($newPassword) < 6) {
                return false;
            }
            
            // Hash du nouveau mot de passe
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // Mise à jour dans la base
            $query = "UPDATE User SET Password_hash = :password_hash WHERE id_User = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erreur updatePassword : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un utilisateur (soft delete possible en ajoutant une colonne deleted_at)
     * Pour l'instant, suppression définitive
     * 
     * @param int $userId - ID de l'utilisateur à supprimer
     * @return bool True si la suppression a réussi
     */
    public function delete($userId) {
        try {
            // Empêcher la suppression de l'admin principal (id = 1)
            if ($userId == 1) {
                return false;
            }
            
            $query = "DELETE FROM User WHERE id_User = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erreur delete user : " . $e->getMessage());
            return false;
        }
    }
}
?>
