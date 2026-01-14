<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../models/Cache_model.php';
require_once __DIR__ . '/../config/redis.php';

/**
 * Tests unitaires pour le composant NoSQL Redis (Cache_model)
 * Validation de la compétence CDA "Développer des composants d'accès aux données NoSQL"
 */
class CacheModelTest extends TestCase
{
    private $cache;
    private $testKeys = [];

    protected function setUp(): void
    {
        $this->cache = new Cache_model();

        // Skip tests if Redis is not available
        if (!$this->cache->isAvailable()) {
            $this->markTestSkipped('Redis is not available. Please start Redis server.');
        }
    }

    protected function tearDown(): void
    {
        // Clean up test keys
        foreach ($this->testKeys as $key) {
            $this->cache->delete($key);
        }
        $this->testKeys = [];
    }

    /**
     * Helper to track keys for cleanup
     */
    private function trackKey($key) {
        $this->testKeys[] = $key;
        return $key;
    }

    /**
     * Test Redis connection availability
     */
    public function testRedisConnectionAvailable()
    {
        $this->assertTrue($this->cache->isAvailable());
    }

    /**
     * Test basic SET/GET operations
     */
    public function testSetAndGet()
    {
        $key = $this->trackKey('test:basic');
        $value = ['name' => 'Memory Project', 'type' => 'CDA'];

        // Set value
        $result = $this->cache->set($key, $value, 60);
        $this->assertTrue($result);

        // Get value
        $retrieved = $this->cache->get($key);
        $this->assertEquals($value, $retrieved);
    }

    /**
     * Test key existence check
     */
    public function testExists()
    {
        $key = $this->trackKey('test:exists');

        // Key should not exist initially
        $this->assertFalse($this->cache->exists($key));

        // Set value and check existence
        $this->cache->set($key, 'test_value', 60);
        $this->assertTrue($this->cache->exists($key));
    }

    /**
     * Test delete operation
     */
    public function testDelete()
    {
        $key = $this->trackKey('test:delete');

        // Set then delete
        $this->cache->set($key, 'to_be_deleted', 60);
        $this->assertTrue($this->cache->exists($key));

        $deleted = $this->cache->delete($key);
        $this->assertTrue($deleted);
        $this->assertFalse($this->cache->exists($key));
    }

    /**
     * Test TTL expiration (shortened test)
     */
    public function testTTLExpiration()
    {
        $key = $this->trackKey('test:ttl');

        // Set with 1 second TTL
        $this->cache->set($key, 'expiring_value', 1);
        $this->assertTrue($this->cache->exists($key));

        // Wait 2 seconds and verify expiration
        sleep(2);
        $this->assertFalse($this->cache->exists($key));
    }

    /**
     * Test hash operations (NoSQL document storage)
     */
    public function testHashOperations()
    {
        $key = $this->trackKey('test:hash');
        $hashData = [
            'user_id' => '123',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'developer'
        ];

        // Set hash
        $result = $this->cache->setHash($key, $hashData, 300);
        $this->assertTrue($result);

        // Get hash
        $retrieved = $this->cache->getHash($key);
        $this->assertEquals($hashData, $retrieved);
    }

    /**
     * Test session storage functionality
     */
    public function testSessionOperations()
    {
        $sessionId = 'test_session_' . time();
        $sessionData = [
            'user_id' => '456',
            'created_at' => time(),
            'ip_address' => '127.0.0.1'
        ];

        // Set session
        $result = $this->cache->setSession($sessionId, $sessionData, 3600);
        $this->assertTrue($result);

        // Get session
        $retrieved = $this->cache->getSession($sessionId);
        $this->assertEquals($sessionData, $retrieved);

        // Delete session
        $deleted = $this->cache->deleteSession($sessionId);
        $this->assertTrue($deleted);
        $this->assertNull($this->cache->getSession($sessionId));
    }

    /**
     * Test query caching functionality
     */
    public function testQueryCaching()
    {
        $query = "SELECT * FROM Project WHERE Category_id_Category = ?";
        $params = [1];
        $queryResult = [
            ['id' => 1, 'name' => 'Project A'],
            ['id' => 2, 'name' => 'Project B']
        ];

        // Cache query result
        $cached = $this->cache->cacheQueryResult($query, $params, $queryResult, 600);
        $this->assertTrue($cached);

        // Retrieve cached result
        $retrieved = $this->cache->getCachedQueryResult($query, $params);
        $this->assertEquals($queryResult, $retrieved);
    }

    /**
     * Test cache invalidation for table updates
     */
    public function testTableCacheInvalidation()
    {
        // Create test cache entries
        $key1 = $this->trackKey('query:project_table_test1');
        $key2 = $this->trackKey('query:project_table_test2');
        $key3 = $this->trackKey('query:other_table_test');

        $this->cache->set($key1, 'project_data_1', 600);
        $this->cache->set($key2, 'project_data_2', 600);
        $this->cache->set($key3, 'other_data', 600);

        // Verify all exist
        $this->assertTrue($this->cache->exists($key1));
        $this->assertTrue($this->cache->exists($key2));
        $this->assertTrue($this->cache->exists($key3));

        // Invalidate Project table cache
        $this->cache->invalidateTableCache('project_table');

        // Project keys should be deleted, other should remain
        $this->assertFalse($this->cache->exists($key1));
        $this->assertFalse($this->cache->exists($key2));
        $this->assertTrue($this->cache->exists($key3));
    }

    /**
     * Test error handling when Redis operations fail
     */
    public function testErrorHandling()
    {
        // Test with invalid key (too long)
        $invalidKey = str_repeat('x', 1000000); // Very long key
        $result = $this->cache->set($invalidKey, 'test', 60);
        // Should handle gracefully and return false
        $this->assertFalse($result);

        // Test getting non-existent key
        $nonExistent = $this->cache->get('non_existent_key_' . time());
        $this->assertNull($nonExistent);
    }

    /**
     * Test complex data structures (nested arrays/objects)
     */
    public function testComplexDataStructures()
    {
        $key = $this->trackKey('test:complex');
        $complexData = [
            'user' => [
                'id' => 123,
                'profile' => [
                    'name' => 'John Doe',
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => true
                    ]
                ]
            ],
            'projects' => [
                ['id' => 1, 'name' => 'Memory'],
                ['id' => 2, 'name' => 'Portfolio']
            ],
            'timestamp' => time()
        ];

        // Store complex structure
        $result = $this->cache->set($key, $complexData, 300);
        $this->assertTrue($result);

        // Retrieve and verify
        $retrieved = $this->cache->get($key);
        $this->assertEquals($complexData, $retrieved);
    }

    /**
     * Performance test - measure cache vs no-cache
     */
    public function testPerformanceImprovement()
    {
        $key = $this->trackKey('test:performance');
        $largeData = array_fill(0, 1000, ['id' => rand(), 'data' => str_repeat('x', 100)]);

        // Time cache storage
        $startTime = microtime(true);
        $this->cache->set($key, $largeData, 300);
        $cacheSetTime = microtime(true) - $startTime;

        // Time cache retrieval
        $startTime = microtime(true);
        $retrieved = $this->cache->get($key);
        $cacheGetTime = microtime(true) - $startTime;

        // Verify data integrity
        $this->assertEquals($largeData, $retrieved);

        // Cache retrieval should be very fast (< 10ms typically)
        $this->assertLessThan(0.01, $cacheGetTime, 'Cache retrieval should be under 10ms');

        // Log performance for documentation
        error_log("Cache SET time: " . round($cacheSetTime * 1000, 2) . "ms");
        error_log("Cache GET time: " . round($cacheGetTime * 1000, 2) . "ms");
    }
}
?>