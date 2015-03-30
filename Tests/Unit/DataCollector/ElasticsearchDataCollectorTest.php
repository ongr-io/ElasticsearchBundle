<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\DataCollector;

use Ongr\ElasticsearchBundle\DataCollector\ElasticsearchDataCollector;

class ElasticsearchDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if correct name is being returned.
     */
    public function testGetName()
    {
        $collector = new ElasticsearchDataCollector();
        $this->assertEquals('es', $collector->getName());
    }
}
