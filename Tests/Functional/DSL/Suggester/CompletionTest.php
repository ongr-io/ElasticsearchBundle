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

use ONGR\ElasticsearchBundle\DSL\Suggester\Completion;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class CompletionTest extends ElasticsearchTestCase
{
    /**
     * @return array
     */
    protected function getCustomMapping()
    {
        return [
            'product' => [
                'properties' => [
                    'description' => [
                        'type' => 'completion',
                        'index_analyzer' => 'simple',
                        'search_analyzer' => 'simple',
                        'payloads' => true,
                    ],
                ],
            ],
        ];
    }

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
                        'description' => [
                            'input' => ['Lorem', 'ipsum'],
                            'output' => 'Lorem ipsum',
                            'payload' => new \stdClass(),
                            'weight' => 1,
                        ],
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'description' => [
                            'input' => ['Lorem', 'ipsum', 'dolor', 'sit', 'amet'],
                            'output' => 'Lorem ipsum dolor sit amet...',
                            'payload' => new \stdClass(),
                            'weight' => 2,
                        ],
                    ],
                    [
                        '_id' => 3,
                        'title' => 'baz',
                        'description' => [
                            'input' => ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipisicing', 'elit'],
                            'output' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                            'payload' => ['my' => 'data'],
                            'weight' => 3,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Executes completion suggester.
     */
    public function testCompletionSuggester()
    {
        $completion = new Completion('description', 'cons');

        $repository = $this
            ->getManager('default', true, $this->getCustomMapping())
            ->getRepository('AcmeTestBundle:Product');

        $search = $repository->createSearch()->addSuggester($completion);
        $result = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertArrayHasKey('score', $result['suggest']['description-completion'][0]['options'][0]);
        unset($result['suggest']['description-completion'][0]['options'][0]['score']);

        $this->assertEquals(
            [
                'description-completion' => [
                    [
                        'text' => 'cons',
                        'offset' => 0,
                        'length' => 4,
                        'options' => [
                            [
                                'text' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit...',
                                'payload' => ['my' => 'data'],
                            ],
                        ],
                    ],
                ],
            ],
            $result['suggest']
        );
    }
}
