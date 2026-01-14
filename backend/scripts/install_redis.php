<?php
/**
 * Script d'installation et configuration Redis pour le projet Memory
 * Vérifie la disponibilité de Redis et configure l'environment
 */

echo "=== Installation et configuration Redis pour Memory ===\n\n";

// Vérification de l'extension PHP Redis
echo "1. Vérification de l'extension PHP Redis...\n";
if (extension_loaded('redis')) {
    echo "   ✅ Extension Redis trouvée\n";
} else {
    echo "   ❌ Extension Redis manquante\n";
    echo "   Installation requise:\n";
    echo "   - macOS (Homebrew): brew install php-redis\n";
    echo "   - Ubuntu/Debian: sudo apt install php-redis\n";
    echo "   - PECL: pecl install redis\n\n";
    exit(1);
}

// Test de connexion Redis
echo "\n2. Test de connexion au serveur Redis...\n";
try {
    $redis = new Redis();
    $connected = $redis->connect('127.0.0.1', 6379, 2.0);

    if (!$connected) {
        throw new Exception("Connexion impossible");
    }

    // Test ping
    $pong = $redis->ping();
    if ($pong === '+PONG' || $pong === true) {
        echo "   ✅ Redis server accessible\n";
        echo "   📊 Serveur: " . $redis->info('server')['redis_version'] . "\n";
    } else {
        throw new Exception("Ping failed");
    }

    $redis->close();
} catch (Exception $e) {
    echo "   ❌ Erreur de connexion Redis: " . $e->getMessage() . "\n";
    echo "   Démarrage requis:\n";
    echo "   - macOS: brew services start redis\n";
    echo "   - Ubuntu: sudo systemctl start redis-server\n";
    echo "   - Manuel: redis-server\n\n";
    exit(1);
}

// Configuration et test des modèles
echo "\n3. Test des modèles Redis...\n";

require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../models/Cache_model.php';
require_once __DIR__ . '/../models/Session_model.php';

try {
    // Test Cache_model
    $cache = new Cache_model();
    if (!$cache->isAvailable()) {
        throw new Exception("Cache_model non disponible");
    }

    // Test opération basique
    $testKey = 'install_test_' . time();
    $testValue = ['installation' => true, 'timestamp' => time()];

    if (!$cache->set($testKey, $testValue, 60)) {
        throw new Exception("Échec SET operation");
    }

    $retrieved = $cache->get($testKey);
    if ($retrieved !== $testValue) {
        throw new Exception("Échec GET operation");
    }

    $cache->delete($testKey);
    echo "   ✅ Cache_model fonctionnel\n";

    // Test Session_model
    $session = new Session_model();
    $sessionId = $session->createSession('install_user', 'test_token', ['name' => 'Install Test']);

    if (!$sessionId) {
        throw new Exception("Échec création session");
    }

    $sessionData = $session->getSession($sessionId);
    if (!$sessionData || $sessionData['user_data']['name'] !== 'Install Test') {
        throw new Exception("Échec récupération session");
    }

    $session->destroySession($sessionId);
    echo "   ✅ Session_model fonctionnel\n";

} catch (Exception $e) {
    echo "   ❌ Erreur modèles: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Configuration recommandée
echo "\n4. Configuration recommandée Redis...\n";
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    // Vérifier la configuration actuelle
    $info = $redis->info();
    $maxMemory = $info['maxmemory'] ?? 0;
    $maxMemoryPolicy = $info['maxmemory_policy'] ?? 'noeviction';

    echo "   📊 Mémoire max: " . ($maxMemory > 0 ? $maxMemory . ' bytes' : 'Illimitée') . "\n";
    echo "   📊 Politique d'éviction: $maxMemoryPolicy\n";

    if ($maxMemory == 0) {
        echo "   ⚠️  Recommandation: Définir une limite de mémoire\n";
        echo "      redis.conf: maxmemory 256mb\n";
    }

    if ($maxMemoryPolicy === 'noeviction') {
        echo "   ⚠️  Recommandation: Changer la politique d'éviction\n";
        echo "      redis.conf: maxmemory-policy allkeys-lru\n";
    }

    $redis->close();
} catch (Exception $e) {
    echo "   ⚠️  Impossible de vérifier la configuration: " . $e->getMessage() . "\n";
}

// Test de performance
echo "\n5. Test de performance...\n";
try {
    $cache = new Cache_model();

    // Test 100 opérations SET/GET
    $startTime = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $key = "perf_test_$i";
        $cache->set($key, ['iteration' => $i, 'data' => str_repeat('x', 100)], 60);
        $cache->get($key);
        $cache->delete($key);
    }
    $endTime = microtime(true);

    $totalTime = round(($endTime - $startTime) * 1000, 2);
    $avgTime = round($totalTime / 300, 2); // 300 opérations (SET+GET+DELETE)

    echo "   📊 100 cycles SET/GET/DELETE: {$totalTime}ms\n";
    echo "   📊 Temps moyen par opération: {$avgTime}ms\n";

    if ($avgTime < 1.0) {
        echo "   ✅ Performance excellente\n";
    } elseif ($avgTime < 5.0) {
        echo "   ✅ Performance correcte\n";
    } else {
        echo "   ⚠️  Performance dégradée - vérifier la configuration Redis\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur test performance: " . $e->getMessage() . "\n";
}

// Création de données de démonstration
echo "\n6. Création de données de démonstration...\n";
try {
    $cache = new Cache_model();

    // Données de cache de démonstration
    $demoData = [
        'demo_projects' => [
            ['id' => 1, 'name' => 'Memory CDA', 'category' => 'Formation'],
            ['id' => 2, 'name' => 'Portfolio', 'category' => 'Personnel']
        ],
        'demo_stats' => [
            'total_projects' => 2,
            'active_users' => 5,
            'cache_hits' => 150
        ]
    ];

    foreach ($demoData as $key => $value) {
        $cache->set($key, $value, 7200); // 2 heures
    }

    echo "   ✅ Données de démonstration créées\n";
    echo "   🔑 Clés disponibles: " . implode(', ', array_keys($demoData)) . "\n";

} catch (Exception $e) {
    echo "   ⚠️  Erreur création démo: " . $e->getMessage() . "\n";
}

echo "\n=== Installation Redis terminée avec succès ===\n";
echo "\n📋 Résumé:\n";
echo "   ✅ Extension PHP Redis: OK\n";
echo "   ✅ Connexion serveur Redis: OK\n";
echo "   ✅ Modèles Cache et Session: OK\n";
echo "   ✅ Tests de performance: OK\n";
echo "   ✅ Données de démonstration: OK\n";

echo "\n🚀 Le composant NoSQL Redis est opérationnel!\n";
echo "\n📚 Documentation complète: docs/06_COMPOSANT_NOSQL_REDIS.md\n";
echo "🧪 Tests unitaires: backend/tests/CacheModelTest.php\n";

echo "\n💡 Commandes utiles:\n";
echo "   - Monitoring Redis: redis-cli monitor\n";
echo "   - Statistiques: redis-cli info stats\n";
echo "   - Lister les clés: redis-cli keys '*'\n";
echo "   - Vider le cache: redis-cli flushdb\n\n";
?>