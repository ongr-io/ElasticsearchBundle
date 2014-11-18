<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service;

use ONGR\ElasticsearchBundle\Service\ESDataCollector;

class ESDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if correct name is being returned.
     */
    public function testGetName()
    {
        $collector = new ESDataCollector();
        $this->assertEquals('es', $collector->getName());
    }
}
