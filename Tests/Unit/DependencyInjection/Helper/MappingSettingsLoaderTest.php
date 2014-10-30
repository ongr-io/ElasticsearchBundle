<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler\Helper;

use ONGR\ElasticsearchBundle\DependencyInjection\Helper\MappingSettingsLoader;

class MappingSettingsLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests get settings method.
     */
    public function testSettingsLoader()
    {
        $settingsLoader = new MappingSettingsLoader();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container
            ->expects($this->once())
            ->method('getParameter')
            ->willReturn(['AcmeTestBundle' => 'path']);

        $mappingResult = [
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
        $metadataCollector = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataCollector
            ->expects($this->once())
            ->method('getMapping')
            ->willReturn($mappingResult);

        $settings = [
            'hosts' => [
                '127.0.0.1:9200',
            ],
            'auth' => [
                'username' => 'user',
                'password' => 'pass',
                'option' => 'Basic',
            ],
            'index_name' => 'acmedemoindex',
            'settings' => [],
        ];

        list($params, $settings) = $settingsLoader->getSettings(
            $settings,
            ['mappings' => []],
            $container,
            $metadataCollector
        );

        $expectedParams = [
            'hosts' => [
                '127.0.0.1:9200',
            ],
            'connectionParams' => [
                'auth' => ['user', 'pass', 'Basic'],
            ],
        ];

        $expectedSettings = [
            'body' => [
                'mappings' => [
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
                ],
            ],
            'index' => 'acmedemoindex',
        ];

        $this->assertEquals($expectedSettings, $settings, 'Recieved incorrect mapping.');
        $this->assertEquals($expectedParams, $params, 'Recieved incorrect client params.');
    }
}
