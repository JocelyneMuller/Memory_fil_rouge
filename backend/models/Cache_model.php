<?php
require_once __DIR__ . '/../config/redis.php';

/**
 * Composant NoSQL pour la gestion du cache avec Redis
 * Implémente les opérations CRUD sur le cache Redis
 */
class Cache_model {
    private $redis;
    private $defaultTTL = 3600; // 1 heure par défaut

    public function __construct() {
        $this->redis = getRedisConnection();
    }

    /**
     * Vérifie si Redis est disponible
     */
    public function isAvailable() {
        return $this->redis !== null;
    }

    /**
     * Stocke une valeur dans le cache
     * @param string $key Clé du cache
     * @param mixed $value Valeur à stocker
     * @param int $ttl Durée de vie en secondes (optionnel)
     * @return bool Succès de l'opération
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $serializedValue = json_encode($value);
            $ttl = $ttl ?? $this->defaultTTL;

            return $this->redis->setex($key, $ttl, $serializedValue);
        } catch (Exception $e) {
            error_log('Redis SET error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère une valeur du cache
     * @param string $key Clé du cache
     * @return mixed|null Valeur ou null si non trouvée
     */
    public function get($key) {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $value = $this->redis->get($key);
            if ($value === false) {
                return null;
            }

            return json_decode($value, true);
        } catch (Exception $e) {
            error_log('Redis GET error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprime une clé du cache
     * @param string $key Clé à supprimer
     * @return bool Succès de l'opération
     */
    public function delete($key) {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->redis->del($key) > 0;
        } catch (Exception $e) {
            error_log('Redis DELETE error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si une clé existe dans le cache
     * @param string $key Clé à vérifier
     * @return bool Existence de la clé
     */
    public function exists($key) {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->redis->exists($key) > 0;
        } catch (Exception $e) {
            error_log('Redis EXISTS error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vide tout le cache
     * @return bool Succès de l'opération
     */
    public function flush() {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->redis->flushDB();
        } catch (Exception $e) {
            error_log('Redis FLUSH error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Stocke un hash (objet associatif)
     * @param string $key Clé du hash
     * @param array $data Données associatives
     * @param int $ttl Durée de vie en secondes
     * @return bool Succès de l'opération
     */
    public function setHash($key, $data, $ttl = null) {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $result = $this->redis->hMSet($key, $data);
            if ($result && $ttl) {
                $this->redis->expire($key, $ttl);
            }
            return $result;
        } catch (Exception $e) {
            error_log('Redis HMSET error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un hash complet
     * @param string $key Clé du hash
     * @return array|null Hash ou null si non trouvé
     */
    public function getHash($key) {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $result = $this->redis->hGetAll($key);
            return empty($result) ? null : $result;
        } catch (Exception $e) {
            error_log('Redis HGETALL error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Stocke des données de session
     * @param string $sessionId ID de session
     * @param array $sessionData Données de session
     * @param int $ttl Durée de vie (par défaut 24h)
     * @return bool Succès de l'opération
     */
    public function setSession($sessionId, $sessionData, $ttl = 86400) {
        return $this->set("session:$sessionId", $sessionData, $ttl);
    }

    /**
     * Récupère des données de session
     * @param string $sessionId ID de session
     * @return array|null Données de session
     */
    public function getSession($sessionId) {
        return $this->get("session:$sessionId");
    }

    /**
     * Supprime une session
     * @param string $sessionId ID de session
     * @return bool Succès de l'opération
     */
    public function deleteSession($sessionId) {
        return $this->delete("session:$sessionId");
    }

    /**
     * Met en cache le résultat d'une requête SQL
     * @param string $query Requête SQL (utilisée comme clé)
     * @param array $params Paramètres de la requête
     * @param array $result Résultat à mettre en cache
     * @param int $ttl Durée de vie du cache
     * @return bool Succès de l'opération
     */
    public function cacheQueryResult($query, $params, $result, $ttl = 1800) {
        $cacheKey = 'query:' . md5($query . serialize($params));
        return $this->set($cacheKey, $result, $ttl);
    }

    /**
     * Récupère le résultat mis en cache d'une requête
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|null Résultat mis en cache
     */
    public function getCachedQueryResult($query, $params) {
        $cacheKey = 'query:' . md5($query . serialize($params));
        return $this->get($cacheKey);
    }

    /**
     * Invalide le cache des requêtes liées à une table
     * @param string $tableName Nom de la table
     * @return bool Succès de l'opération
     */
    public function invalidateTableCache($tableName) {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $pattern = "query:*$tableName*";
            $keys = $this->redis->keys($pattern);

            if (empty($keys)) {
                return true;
            }

            return $this->redis->del($keys) > 0;
        } catch (Exception $e) {
            error_log('Redis INVALIDATE error: ' . $e->getMessage());
            return false;
        }
    }
}
?>