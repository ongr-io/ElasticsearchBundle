<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Suggester;

use ONGR\ElasticsearchDSL\Suggester\Context;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Tests for context suggester.
 */
class ContextTest extends ElasticsearchTestCase
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
                        'suggestions' => [
                            'input' => ['Lorem', 'ipsum', 'cons'],
                            'output' => 'Lorem ipsum',
                            'payload' => new \stdClass(),
                            'weight' => 1,
                            'context' => [
                                'location' => [0, 0],
                                'price' => 500,
                            ],
                        ],
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'suggestions' => [
                            'input' => ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'cons'],
                            'output' => 'Lorem ipsum dolor sit amet...',
                            'payload' => new \stdClass(),
                            'weight' => 2,
                            'context' => [
                                'location' => [1, 1],
                                'price' => 500,
                            ],
                        ],
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'suggestions' => [
                            'input' => ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipisicing', 'elit'],
                            'output' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                            'payload' => ['my' => 'data'],
                            'weight' => 3,
                            'context' => [
                                'location' => [3, 3],
                                'price' => 700,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Executes completion suggester.
     */
    public function testContextSuggester()
    {
        $geoContext = new Context\GeoContext('location', ['lat' => 0, 'lon' => 0]);
        $categoryContext = new Context\CategoryContext('price', '500');

        $context = new Context('suggestions', 'cons');
        $context->addContext($geoContext);
        $context->addContext($categoryContext);

        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repository->createSearch()->addSuggester($context);
        $result = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('score', $result['suggest']['suggestions-completion'][0]['options'][0]);
        unset($result['suggest']['suggestions-completion'][0]['options'][0]['score']);

        $this->assertEquals(
            [
                'suggestions-completion' => [
                    [
                        'text' => 'cons',
                        'offset' => 0,
                        'length' => 4,
                        'options' => [
                            [
                                'text' => 'Lorem ipsum',
                                'payload' => [],
                            ],
                        ],
                    ],
                ],
            ],
            $result['suggest']
        );
    }

    /**
     * Check if precision setting works as expected.
     */
    public function testContextSuggesterPrecision()
    {
        $geoContext = new Context\GeoContext('location', ['lat' => 0, 'lon' => 0]);
        $geoContext->setPrecision('10000km');
        $categoryContext = new Context\CategoryContext('price', '500');

        $context = new Context('suggestions', 'cons');
        $context->addContext($geoContext);
        $context->addContext($categoryContext);

        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repository->createSearch()->addSuggester($context);
        $result = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertEmpty($result['suggest']['suggestions-completion'][0]['options']);
    }
}
