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

use ONGR\ElasticsearchBundle\DSL\Filter\TermsFilter;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class TermsFilterTest extends ElasticsearchTestCase
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
                        'user' => 'baz',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'user' => 'cab',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'dog',
                        'user' => 'zoo',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for terms filter.
     */
    public function testTermsFilter()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $field = 'user';
        $terms = [
            'zoo',
        ];
        $parameters = ['_cache' => true];

        $filter = new TermsFilter($field, $terms, $parameters);
        $search = $repo->createSearch()->addFilter($filter);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $expected = [
            [
                'title' => 'dog',
                'user' => 'zoo',
            ]
        ];

        $this->assertEquals($expected, $results);
    }
}
