<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Query\FuzzyLikeThisQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class FuzzyLikeThisTest extends AbstractElasticsearchTestCase
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
                        'price' => 10,
                        'description' => 'Loram ipsum',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'Lorem ipsum dolor sit amet...',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'price' => 1000,
                        'description' => 'Loruu ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test match query for expected search result when fields are set.
     */
    public function testFuzzyLikeThisQueryWhenFieldsAreSet()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $fuzzyLikeThis = new FuzzyLikeThisQuery(['title', 'description'], 'consectetur adipisicing bar');
        $search = $repo->createSearch()->addQuery($fuzzyLikeThis);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expectedResult = [
            [
                'title' => 'bar',
                'price' => 100,
                'description' => 'Lorem ipsum dolor sit amet...',
            ],
            [
                'title' => 'baz',
                'price' => 1000,
                'description' => 'Loruu ipsum dolor sit amet, consectetur adipisicing elit...',
            ],
        ];
        $this->assertEquals($expectedResult, $results);
    }

    /**
     * Test match query for expected search result when fields are not set.
     */
    public function testFuzzyLikeThisQueryWhenFieldsAreNotSet()
    {
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $fuzzyLikeThis = new FuzzyLikeThisQuery([], 'consectetur adipisicing bar', ['fuzziness' => 1]);
        $search = $repo->createSearch()->addQuery($fuzzyLikeThis);
        $results = $repo->execute($search, Repository::RESULTS_ARRAY);
        $expectedResult = [
            [
                'title' => 'baz',
                'price' => 1000,
                'description' => 'Loruu ipsum dolor sit amet, consectetur adipisicing elit...',
            ],
            [
                'title' => 'bar',
                'price' => 100,
                'description' => 'Lorem ipsum dolor sit amet...',
            ],
        ];
        $this->assertEquals($expectedResult, $results);
    }
}
