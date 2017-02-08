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

use ONGR\ElasticsearchBundle\Service\ManagerFactory;
use ONGR\ElasticsearchBundle\Service\Manager;

class ManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests createManager with logger
     */
    public function testCreateManagerWithEnabledLogger()
    {
        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataCollector->expects($this->any())->method('getClientMapping')->will($this->returnValue([]));
        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->createMock('Psr\Log\LoggerInterface');
        $managerFactory = new ManagerFactory(
            $metadataCollector,
            $converter,
            null,
            $logger
        );

        $managerFactory->setEventDispatcher($dispatcher);

        $manager = $managerFactory->createManager(
            'test',
            [
                'index_name' => 'test',
                'settings' => [],
                'hosts' => []
            ],
            [],
            [
                'mappings' => [],
                'logger' => [
                    'enabled' => true
                ],
                'commit_mode' => 'flush',
                'bulk_size' => 10
            ]
        );
        $this->assertTrue($manager instanceof Manager);
    }
}
