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

use ONGR\ElasticsearchBundle\DSL\Suggester\Term;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class TermTest extends ElasticsearchTestCase
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
                        'description' => 'Lorem ipsum',
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
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                    ],
                ],
            ],
        ];
    }

    /**
     * Testing term suggester execution.
     */
    public function testTermSuggester()
    {
        $term = new Term('description', 'ipsu');
        $term->setAnalyzer('simple');

        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repository->createSearch()->addSuggester($term);

        $raw = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertTrue(array_key_exists('freq', $raw['suggest']['description-term'][0]['options'][0]));
        array_pop($raw['suggest']['description-term'][0]['options'][0]);

        $this->assertEquals(
            [
                'description-term' => [
                    [
                        'text' => 'ipsu',
                        'offset' => 0,
                        'length' => 4,
                        'options' => [
                            [
                                'text' => 'ipsum',
                                'score' => 0.75,
                            ],
                        ],
                    ],
                ],
            ],
            $raw['suggest']
        );
    }
}
