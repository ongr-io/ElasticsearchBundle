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
                'product' => [
                    [
                        '_id' => 1,
                        'title' => ['foo', 'bar'],
                        'price' => 10,
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
        $product = $this->getDocument('AcmeTestBundle:Color', 1);

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyInterface',
            $product,
            'Recieved document should be a proxy.'
        );
        $this->assertTrue($product->__isInitialized(), 'Document should have initialized flag set.');
    }

    /**
     * Test if find by path works as expected.
     */
    public function testIfFindByPathInArray()
    {
        $product = $this->getDocument('AcmeTestBundle:Color', 1);
        $result = $product->findByPath('enabled_cdn[0].cdn_url');
        $this->assertEquals('foo', $result);
    }

    /**
     * Test if find by path works as expected.
     */
    public function testIfFindByPathInScalar()
    {
        $product = $this->getDocument('AcmeTestBundle:Product', 1);
        $result = $product->findByPath('price');
        $this->assertEquals(10, $result);
    }

    /**
     * Test if find by path works as expected when path not found.
     */
    public function testIfFindByPathWhenPathNotFound()
    {
        $product = $this->getDocument('AcmeTestBundle:Product', 1);
        $result = $product->findByPath('foo');
        $this->assertEquals(null, $result);
    }

    /**
     * @param string $document
     * @param int    $id
     *
     * @return null|\ONGR\ElasticsearchBundle\Document\DocumentInterface
     */
    private function getDocument($document, $id)
    {
        $manager = $this->getManager();
        $product = $manager->getRepository($document)->find($id);

        return $product;
    }
}
