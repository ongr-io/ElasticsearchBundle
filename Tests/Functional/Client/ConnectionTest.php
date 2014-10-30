<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Client;

use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Functional tests for connection service.
 */
class ConnectionTest extends ElasticsearchTestCase
{
    /**
     * @return array
     */
    protected function getAcmeMapping()
    {
        return [
            'product' => [
                'properties' => [
                    'id' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
                    'title' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests updateMapping with real data.
     */
    public function testUpdateMapping()
    {
        $manager = $this->getManager('bar');
        $connection = $manager->getConnection();

        // Using phpunit setExpectedException does not continue after exception is thrown.
        $thrown = false;
        try {
            $connection->updateMapping();
        } catch (\LogicException $e) {
            $thrown = true;
            // Continue.
        }
        $this->assertTrue($thrown, '\LogicException should be thrown');

        $connection = $this->getManager(
            'bar',
            false,
            $this->getAcmeMapping()
        )->getConnection();

        $status = $connection->updateMapping();
        $this->assertTrue($status, 'Mapping should be updated');
    }
}
