<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Filter;

use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Filter\BoolFilter;
use ONGR\ElasticsearchDSL\Filter\IdsFilter;
use ONGR\ElasticsearchDSL\Filter\MissingFilter;
use ONGR\ElasticsearchDSL\Filter\PrefixFilter;
use ONGR\ElasticsearchDSL\Search;

class BoolFilterTest extends AbstractElasticsearchTestCase
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
                        'description' => 'super foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'barbara',
                        'description' => 'super bar',
                    ],
                    [
                        '_id' => 4,
                        'title' => 'foot',
                        'price' => 300,
                    ],
                    [
                        '_id' => 5,
                        'title' => 'paper',
                        'description' => '500 sheets',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testBoolFilter().
     *
     * @return array[]
     */
    public function getBoolFilterData()
    {
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 with cache and all filters.
        $out[] = [
            new MissingFilter('price'),
            new IdsFilter(['1']),
            new PrefixFilter('title', 'pa'),
            ['_cache' => true],
            [
                $testProducts[4],
            ],
        ];

        return $out;
    }

    /**
     * Bool filter test.
     *
     * @param BuilderInterface $mustFilter    Data for must.
     * @param BuilderInterface $mustNotFilter Data for must_not.
     * @param BuilderInterface $shouldFilter  Data for should.
     * @param array            $parameters    Additional parameters.
     * @param array            $expected      Expected result.
     *
     * @dataProvider getBoolFilterData()
     */
    public function testBoolFilter($mustFilter, $mustNotFilter, $shouldFilter, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
        /** @var Search $search */
        $search = $repository
            ->createSearch()
            ->addFilter($mustFilter, BoolFilter::MUST)
            ->addFilter($mustNotFilter, BoolFilter::MUST_NOT)
            ->addFilter($shouldFilter, BoolFilter::SHOULD)
            ->setBoolFilterParameters($parameters);
        $results = $repository->execute($search, Repository::RESULTS_ARRAY);
        sort($results);
        $this->assertEquals($expected, $results);
    }
}
