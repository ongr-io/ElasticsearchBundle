<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection;

use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * Unit test for configuration tree.
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns default configuration for bundle.
     *
     * @return array
     */
    public function getTestConfigurationData()
    {
        $expectedConfiguration = [
            'connections' => [
                'acme' => [
                    'index_name' => 'acme',
                    'hosts' => ['127.0.0.1:9200'],
                    'settings' => [],
                ],
            ],
            'managers' => [
                'acme' => [
                    'connection' => 'acme',
                    'debug' => [
                        'enabled' => false,
                        'level' => 'warning',
                    ],
                    'readonly' => false,
                    'mappings' => ['AcmeTestBundle'],
                ],
            ],
            'document_dir' => 'Document',
        ];

        $out = [];

        // Case #0 basic.
        $out[] = [
            [
                'document_dir' => 'Docs',
                'connections' => [
                    'acme' => ['index_name' => 'acme'],
                ],
                'managers' => [
                    'acme' => [
                        'connection' => 'acme',
                        'mappings' => ['AcmeTestBundle'],
                    ],
                ],
            ],
            array_merge($expectedConfiguration, ['document_dir' => 'Docs']),
        ];

        // Case #1 hosts as arrays.
        $out[] = [
            [
                'connections' => [
                    'acme' => [
                        'hosts' => [
                            ['host' => '127.0.0.1:9200'],
                        ],
                        'index_name' => 'acme',
                    ],
                ],
                'managers' => [
                    'acme' => [
                        'connection' => 'acme',
                        'mappings' => ['AcmeTestBundle'],
                    ],
                ],
            ],
            $expectedConfiguration,
        ];

        // Case #2 hosts as strings.
        $out[] = [
            [
                'connections' => [
                    'acme' => [
                        'hosts' => ['127.0.0.1:9200'],
                        'index_name' => 'acme',
                    ],
                ],
                'managers' => [
                    'acme' => [
                        'connection' => 'acme',
                        'mappings' => ['AcmeTestBundle'],
                    ],
                ],
            ],
            $expectedConfiguration,
        ];

        // Case #3: incomplete hosts array.
        $out[] = [
            [
                'connections' => [
                    'acme' => [
                        'hosts' => [
                            ['bar' => '127.0.0.1'],
                        ],
                        'index_name' => 'acme',
                    ],
                ],
                'managers' => [
                    'acme' => [
                        'connection' => 'acme',
                        'mappings' => ['AcmeTestBundle'],
                    ],
                ],
            ],
            $expectedConfiguration,
            true,
            'Host must be configured under hosts configuration tree.',
        ];

        // Case #4: using auth.
        $out[] = [
            [
                'connections' => [
                    'acme' => [
                        'hosts' => [
                            ['host' => '127.0.0.1:9200'],
                        ],
                        'auth' => [
                            'username' => 'user',
                            'password' => 'pass',
                        ],
                        'index_name' => 'acme',
                    ],
                ],
                'managers' => [
                    'acme' => [
                        'connection' => 'acme',
                        'mappings' => ['AcmeTestBundle'],
                    ],
                ],
            ],
            array_replace_recursive(
                $expectedConfiguration,
                [
                    'connections' => [
                        'acme' => [
                            'auth' => [
                                'username' => 'user',
                                'password' => 'pass',
                                'option' => 'Basic',
                            ],
                        ],
                    ],
                ]
            ),
        ];

        return $out;
    }

    /**
     * Tests if expected default values are added.
     *
     * @param array  $config
     * @param array  $expected
     * @param bool   $exception
     * @param string $exceptionMessage
     *
     * @dataProvider getTestConfigurationData
     */
    public function testConfiguration($config, $expected, $exception = false, $exceptionMessage = '')
    {
        if ($exception) {
            $this->setExpectedException(
                '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
                $exceptionMessage
            );
        }

        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);
        $this->assertEquals($expected, $processedConfig);
    }
}
