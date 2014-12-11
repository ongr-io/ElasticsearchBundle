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

use ONGR\ElasticsearchBundle\DSL\Filter\RegexpFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class RegexpFilterTest extends ElasticsearchTestCase
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
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testRegexpFilter().
     *
     * @return array[]
     */
    public function getRegexpFilterData()
    {
        // Case #0 with flags and parameters.
        $out[] = [
            'title',
            'f.*',
            [
                'flags' => 'INTERSECTION|COMPLEMENT|EMPTY',
                '_cache' => true,
            ],
            [
                [
                    'title' => 'foo',
                ],
            ],
        ];

        // Case #1 with just flags.
        $out[] = [
            'title',
            'f.*',
            [
                'flags' => 'INTERSECTION|COMPLEMENT|EMPTY',
            ],
            [
                [
                    'title' => 'foo',
                ],
            ],
        ];

        // Case #2 with just parameters.
        $out[] = [
            'title',
            'f.*',
            [
                '_cache' => true,
            ],
            [
                [
                    'title' => 'foo',
                ],
            ],
        ];

        // Case #3 without flags or parameters.
        $out[] = [
            'title',
            'f.*',
            [],
            [
                [
                    'title' => 'foo',
                ],
            ],
        ];

        return $out;
    }

    /**
     * Tests regexp filter.
     *
     * @param string $field
     * @param string $regexp
     * @param array  $parameters
     * @param array  $expected
     *
     * @dataProvider getRegexpFilterData()
     */
    public function testRegexpFilter($field, $regexp, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $regexp = new RegexpFilter($field, $regexp, $parameters);
        $search = $repo->createSearch()->addFilter($regexp);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
