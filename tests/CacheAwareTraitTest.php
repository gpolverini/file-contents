<?php

namespace Psr\SimpleCache;

use PHPUnit\Framework\TestCase;

/**
 * @author Gabriel Polverini <polverini.gabriel@gmail.com>
 *
 * @group CacheAwareTrait
 */
class CacheAwareTraitTest extends TestCase
{
    use CacheAwareTrait;

    /**
     * @test
     */
    public function testSetCache()
    {
        $this->assertNull($this->cache);
        $cache = $this->createMock('Psr\SimpleCache\CacheInterface');
        $this->setCache($cache);
        $this->assertTrue($this->cache instanceof CacheInterface);
    }
}
