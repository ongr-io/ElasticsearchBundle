<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Mapping;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Tests proxy classes.
 */
class ProxyTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'color' => [
                    [
                        '_id' => 1,
                        'enabled_cdn' => [
                            [
                                'cdn_url' => 'foo',
                            ],
                        ],
                        'disabled_cdn' => [
                            [
                                'cdn_url' => 'foo',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests if documents have initialization flag set.
     */
    public function testIsInitialized()
    {
        $manager = $this->getManager();
        $product = $manager->getRepository('AcmeTestBundle:Color')->find(1);

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyInterface',
            $product,
            'Recieved document should be a proxy.'
        );
        $result = $product->findByPath('enabled_cdn[0].cdn_url');
        $this->assertEquals('foo', $result);
        $this->assertTrue($product->__isInitialized(), 'Document should have initialized flag set.');
    }
}
