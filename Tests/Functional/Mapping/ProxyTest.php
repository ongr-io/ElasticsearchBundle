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

use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Tests proxy classes.
 */
class ProxyTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 1,
                        'title' => 'foo',
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
        $product = $manager->getRepository('AcmeTestBundle:Product')->find(1);

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyInterface',
            $product,
            'Recieved document should be a proxy.'
        );
        $this->assertTrue($product->__isInitialized(), 'Document should have initialized flag set.');
    }
}
