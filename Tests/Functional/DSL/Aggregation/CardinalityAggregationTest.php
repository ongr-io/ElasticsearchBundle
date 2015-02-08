<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Aggregation;

use ONGR\ElasticsearchBundle\DSL\Aggregation\CardinalityAggregation;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class CardinalityAggregationTest extends ElasticsearchTestCase
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
                        'price' => 10.45,
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 32,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'bar',
                        'price' => 32,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testCardinalityAggregation().
     *
     * @return array
     */
    public function getCardinalityAggregationData()
    {
        $out = [];

        // Case #0 without any threshold or rehash.
        $out[] = ['threshold' => null, 'rehash' => null, 'expectedResults' => 2];

        // Case #1 with threshold.
        $out[] = ['threshold' => 1, 'rehash' => null, 'expectedResults' => 2];

        // Case #2 with threshold and rehash.
        $out[] = ['threshold' => 4000, 'rehash' => true, 'expectedResults' => 2];

        return $out;
    }

    /**
     * Test for cardinality aggregation.
     *
     * @param int  $threshold
     * @param bool $rehash
     * @param int  $expectedResults
     *
     * @dataProvider getCardinalityAggregationData()
     */
    public function testCardinalityAggregation($threshold, $rehash, $expectedResults)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new CardinalityAggregation('test_agg');
        $aggregation->setField('price');
        $aggregation->setPrecisionThreshold($threshold);
        $aggregation->setRehash($rehash);

        $search = $repo->createSearch()->addAggregation($aggregation);
        $results = $repo->execute($search, Repository::RESULTS_OBJECT);

        /** @var ValueAggregation $result */
        $result = $results->getAggregations()['test_agg'];
        $this->assertEquals($expectedResults, $result->getValue()['value']);
    }

    /**
     * Tests cardinality aggregation using script instead of field.
     */
    public function testCardinalityWithScript()
    {
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $aggregation = new CardinalityAggregation('foo');
        $aggregation->setScript("doc['product.price'].value + ' ' + doc['product.title'].value");
        $search = $repository
            ->createSearch()
            ->addAggregation($aggregation);
        $result = $repository->execute($search)->getAggregations()->find('foo');
        $this->assertEquals(2, $result->getValue()['value']);
    }
}
