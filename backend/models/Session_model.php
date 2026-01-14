<?php
require_once __DIR__ . '/Cache_model.php';

/**
 * Gestion des sessions utilisateur avec Redis (composant NoSQL)
 * Stockage sécurisé des données de session et tokens JWT
 */
class Session_model {
    private $cache;
    private $sessionTTL = 86400; // 24 heures

    public function __construct() {
        $this->cache = new Cache_model();
    }

    /**
     * Crée une nouvelle session utilisateur
     * @param string $userId ID de l'utilisateur
     * @param string $jwtToken Token JWT généré
     * @param array $userData Données utilisateur (nom, email, rôle)
     * @return string Session ID généré
     */
    public function createSession($userId, $jwtToken, $userData) {
        // Génération d'un ID de session unique
        $sessionId = $this->generateSessionId();

        // Données de session à stocker
        $sessionData = [
            'user_id' => $userId,
            'jwt_token' => $jwtToken,
            'user_data' => $userData,
            'created_at' => time(),
            'last_activity' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        // Stockage en Redis avec TTL
        $this->cache->setSession($sessionId, $sessionData, $this->sessionTTL);

        // Index par user_id pour retrouver facilement les sessions d'un user
        $this->addToUserSessions($userId, $sessionId);

        return $sessionId;
    }

    /**
     * Récupère les données d'une session
     * @param string $sessionId ID de session
     * @return array|null Données de session
     */
    public function getSession($sessionId) {
        $sessionData = $this->cache->getSession($sessionId);

        if ($sessionData) {
            // Mise à jour de la dernière activité
            $sessionData['last_activity'] = time();
            $this->cache->setSession($sessionId, $sessionData, $this->sessionTTL);
        }

        return $sessionData;
    }

    /**
     * Valide un token JWT via la session Redis
     * @param string $jwtToken Token à valider
     * @return array|null Données utilisateur si token valide
     */
    public function validateTokenSession($jwtToken) {
        // Recherche du token dans toutes les sessions (optimisation possible avec index)
        $tokenKey = 'token:' . md5($jwtToken);
        $sessionId = $this->cache->get($tokenKey);

        if (!$sessionId) {
            return null;
        }

        $sessionData = $this->getSession($sessionId);

        if (!$sessionData || $sessionData['jwt_token'] !== $jwtToken) {
            return null;
        }

        return $sessionData;
    }

    /**
     * Stocke une relation token -> session pour la validation rapide
     * @param string $jwtToken Token JWT
     * @param string $sessionId ID de session
     */
    public function linkTokenToSession($jwtToken, $sessionId) {
        $tokenKey = 'token:' . md5($jwtToken);
        $this->cache->set($tokenKey, $sessionId, $this->sessionTTL);
    }

    /**
     * Supprime une session (déconnexion)
     * @param string $sessionId ID de session
     * @return bool Succès de l'opération
     */
    public function destroySession($sessionId) {
        $sessionData = $this->cache->getSession($sessionId);

        if ($sessionData) {
            // Suppression du lien token -> session
            $tokenKey = 'token:' . md5($sessionData['jwt_token']);
            $this->cache->delete($tokenKey);

            // Suppression de la session des sessions utilisateur
            $this->removeFromUserSessions($sessionData['user_id'], $sessionId);
        }

        return $this->cache->deleteSession($sessionId);
    }

    /**
     * Supprime toutes les sessions d'un utilisateur
     * @param string $userId ID utilisateur
     * @return bool Succès de l'opération
     */
    public function destroyAllUserSessions($userId) {
        $userSessionsKey = "user_sessions:$userId";
        $sessionIds = $this->cache->get($userSessionsKey);

        if (!$sessionIds || !is_array($sessionIds)) {
            return true;
        }

        foreach ($sessionIds as $sessionId) {
            $this->destroySession($sessionId);
        }

        // Suppression de l'index des sessions utilisateur
        $this->cache->delete($userSessionsKey);

        return true;
    }

    /**
     * Récupère toutes les sessions actives d'un utilisateur
     * @param string $userId ID utilisateur
     * @return array Sessions actives
     */
    public function getUserActiveSessions($userId) {
        $userSessionsKey = "user_sessions:$userId";
        $sessionIds = $this->cache->get($userSessionsKey);

        if (!$sessionIds || !is_array($sessionIds)) {
            return [];
        }

        $activeSessions = [];
        foreach ($sessionIds as $sessionId) {
            $sessionData = $this->cache->getSession($sessionId);
            if ($sessionData) {
                $activeSessions[] = [
                    'session_id' => $sessionId,
                    'created_at' => $sessionData['created_at'],
                    'last_activity' => $sessionData['last_activity'],
                    'ip_address' => $sessionData['ip_address']
                ];
            }
        }

        return $activeSessions;
    }

    /**
     * Nettoie les sessions expirées
     * @return int Nombre de sessions supprimées
     */
    public function cleanExpiredSessions() {
        // Cette méthode pourrait être appelée par un cron job
        // Redis gère automatiquement l'expiration avec TTL
        // Mais on peut nettoyer les index orphelins

        if (!$this->cache->isAvailable()) {
            return 0;
        }

        $cleaned = 0;
        // Implémentation de nettoyage manuel si nécessaire
        return $cleaned;
    }

    /**
     * Génère un ID de session unique
     * @return string Session ID
     */
    private function generateSessionId() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Ajoute une session à l'index des sessions utilisateur
     * @param string $userId ID utilisateur
     * @param string $sessionId ID session
     */
    private function addToUserSessions($userId, $sessionId) {
        $userSessionsKey = "user_sessions:$userId";
        $sessions = $this->cache->get($userSessionsKey) ?: [];

        if (!in_array($sessionId, $sessions)) {
            $sessions[] = $sessionId;
            $this->cache->set($userSessionsKey, $sessions, $this->sessionTTL + 3600); // TTL plus long pour l'index
        }
    }

    /**
     * Supprime une session de l'index utilisateur
     * @param string $userId ID utilisateur
     * @param string $sessionId ID session
     */
    private function removeFromUserSessions($userId, $sessionId) {
        $userSessionsKey = "user_sessions:$userId";
        $sessions = $this->cache->get($userSessionsKey) ?: [];

        $sessions = array_filter($sessions, function($id) use ($sessionId) {
            return $id !== $sessionId;
        });

        if (empty($sessions)) {
            $this->cache->delete($userSessionsKey);
        } else {
            $this->cache->set($userSessionsKey, array_values($sessions), $this->sessionTTL + 3600);
        }
    }

    /**
     * Stocke des données temporaires de l'utilisateur (panier, préférences, etc.)
     * @param string $userId ID utilisateur
     * @param string $key Clé des données
     * @param mixed $data Données à stocker
     * @param int $ttl Durée de vie (défaut: 1 heure)
     * @return bool Succès de l'opération
     */
    public function setUserData($userId, $key, $data, $ttl = 3600) {
        $dataKey = "user_data:{$userId}:{$key}";
        return $this->cache->set($dataKey, $data, $ttl);
    }

    /**
     * Récupère des données temporaires de l'utilisateur
     * @param string $userId ID utilisateur
     * @param string $key Clé des données
     * @return mixed|null Données ou null si non trouvées
     */
    public function getUserData($userId, $key) {
        $dataKey = "user_data:{$userId}:{$key}";
        return $this->cache->get($dataKey);
    }

    /**
     * Supprime des données temporaires de l'utilisateur
     * @param string $userId ID utilisateur
     * @param string $key Clé des données
     * @return bool Succès de l'opération
     */
    public function deleteUserData($userId, $key) {
        $dataKey = "user_data:{$userId}:{$key}";
        return $this->cache->delete($dataKey);
    }
}
?>