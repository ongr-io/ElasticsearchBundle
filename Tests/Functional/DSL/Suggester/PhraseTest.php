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

use ONGR\ElasticsearchBundle\DSL\Suggester\Phrase;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class PhraseTest extends ElasticsearchTestCase
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
     * Executes phrase suggester and checks values.
     */
    public function testPhraseSuggester()
    {
        $phrase = new Phrase('description', 'Lorm adip');
        $phrase->setAnalyzer('simple');
        $phrase->setSize(1);
        $phrase->setRealWordErrorLikelihood(0.95);
        $phrase->setMaxErrors(0.5);
        $phrase->setGramSize(2);
        $phrase->setHighlight(
            [
                'pre_tag' => '<span class="highlight">',
                'post_tag' => '</span>',
            ]
        );

        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');
        $search = $repository->createSearch()->addSuggester($phrase);

        $results = $repository->execute($search, Repository::RESULTS_RAW);
        $score = array_pop($results['suggest']['description-phrase'][0]['options'][0]);

        $this->assertEquals(
            [
                'description-phrase' => [
                    [
                        'text' => 'Lorm adip',
                        'offset' => 0,
                        'length' => 9,
                        'options' => [
                            [
                                'text' => 'lorem adip',
                                'highlighted' => '<span class="highlight">lorem</span> adip',
                            ],
                        ],
                    ],
                ],
            ],
            $results['suggest']
        );

        $this->assertTrue($score > 0 && $score <= 1, 'Score is out of bounds');
    }
}
