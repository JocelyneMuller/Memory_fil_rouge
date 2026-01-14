<?php
/**
 * Configuration Redis pour le composant NoSQL
 * Utilisé pour le cache des requêtes et la gestion des sessions
 */

class RedisConfig {
    private static $redis = null;
    private static $host = '127.0.0.1';
    private static $port = 6379;
    private static $timeout = 2.0;
    private static $database = 0;

    /**
     * Obtient une instance Redis singleton
     */
    public static function getInstance() {
        if (self::$redis === null) {
            try {
                if (!extension_loaded('redis')) {
                    throw new Exception('Extension Redis non disponible');
                }

                self::$redis = new Redis();
                self::$redis->connect(self::$host, self::$port, self::$timeout);
                self::$redis->select(self::$database);

                // Test de connexion
                self::$redis->ping();

            } catch (Exception $e) {
                error_log('Redis connection failed: ' . $e->getMessage());
                if (php_sapi_name() === 'cli') {
                    echo 'Redis connection failed: ' . $e->getMessage() . "\n";
                    echo 'Make sure Redis server is running on ' . self::$host . ':' . self::$port . "\n";
                }
                // Retourne null si Redis n'est pas disponible (mode dégradé)
                return null;
            }
        }

        return self::$redis;
    }

    /**
     * Teste si Redis est disponible
     */
    public static function isAvailable() {
        $redis = self::getInstance();
        return $redis !== null;
    }

    /**
     * Ferme la connexion Redis
     */
    public static function close() {
        if (self::$redis !== null) {
            self::$redis->close();
            self::$redis = null;
        }
    }
}

/**
 * Fonction helper pour obtenir Redis
 */
function getRedisConnection() {
    return RedisConfig::getInstance();
}
?>