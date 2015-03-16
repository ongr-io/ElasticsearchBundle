<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DataCollector;

use ONGR\ElasticsearchBundle\DataCollector\ElasticsearchDataCollector;

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

    /**
     * Tests getManagers method.
     */
    public function testGetManagers()
    {
        $collector = new ElasticsearchDataCollector();
        $collector->setManagers(
            [
                'test_name1' => ['test_manager1'],
                'default' => ['test_manager2'],
            ]
        );

        $result = [
            'test_name1' => 'es.manager.test_name1',
            'default' => 'es.manager',
        ];

        $this->assertEquals($result, $collector->getManagers());
    }
}
