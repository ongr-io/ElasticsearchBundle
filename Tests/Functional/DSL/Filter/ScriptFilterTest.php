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

use ONGR\ElasticsearchBundle\DSL\Filter\ScriptFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class ScriptFilterTest extends ElasticsearchTestCase
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
                        'price' => 20
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                    ],
                    [
                        '_id' => 3,
                        'title' => 'zoo',
                        'price' => 50,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testScriptFilter().
     *
     * @return array[]
     */
    public function getScriptFilterData()
    {
        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 with params and caching.
        $out[] = [
            "doc['price'].value > min_val and doc['price'].value < max_val",
            [
                'params' => [
                    'min_val' => 20,
                    'max_val' => 100,
                ],
                '_cache' => true,
            ],
            [
                $testProducts[2],
            ],
        ];

        // Case #1 without params and caching.
        $out[] = [
            "doc['price'].value > 30",
            [],
            [
                $testProducts[1],
                $testProducts[2],
            ],
        ];

        return $out;
    }

    /**
     * Tests script filter.
     *
     * @param string $script
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getScriptFilterData()
     */
    public function testScriptFilter($script, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $script = new ScriptFilter($script, $parameters);
        $search = $repo->createSearch()->addFilter($script);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        sort($results);
        $this->assertEquals($expected, $results);
    }
}
