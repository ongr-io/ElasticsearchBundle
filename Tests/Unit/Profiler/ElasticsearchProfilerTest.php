<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Profiler;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Profiler\ElasticsearchProfiler;

class ElasticsearchProfilerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests if correct name is being returned.
     */
    public function testGetName()
    {
        $collector = new ElasticsearchProfiler();
        $this->assertEquals('ongr.profiler', $collector->getName());
    }

    /**
     * Tests getManagers method.
     */
    public function testGetManagers()
    {
        $indexes = [
            DummyDocument::INDEX_NAME => DummyDocument::class
        ];
        $collector = new ElasticsearchProfiler();
        $collector->setIndexes($indexes);

        $result = $collector->getIndexes();
        $this->assertEquals(
            $indexes,
            $result
        );
    }
}
