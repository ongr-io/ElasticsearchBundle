<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Result;

use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\DSL\Suggester\AbstractSuggester;
use ONGR\ElasticsearchBundle\DSL\Suggester\Completion;
use ONGR\ElasticsearchBundle\DSL\Suggester\Context;
use ONGR\ElasticsearchBundle\DSL\Suggester\Phrase;
use ONGR\ElasticsearchBundle\DSL\Suggester\Term;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\PhraseOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\SimpleOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\TermOption;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class SuggestionsIteratorTest extends ElasticsearchTestCase
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
                        'suggestions' => [
                            'input' => ['Lorem', 'ipsum', 'cons'],
                            'output' => 'Lorem ipsum',
                            'payload' => ['test' => true],
                            'weight' => 1,
                            'context' => [
                                'location' => [0, 0],
                                'price' => 500,
                            ],
                        ],
                        'completion_suggesting' => [
                            'input' => ['Lorem', 'ipsum'],
                            'output' => 'Lorem ipsum',
                            'weight' => 1,
                        ],
                    ],
                    [
                        '_id' => 2,
                        'title' => 'bar',
                        'price' => 100,
                        'description' => 'Lorem ipsum dolor sit amet... amte distributed disributed',
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
     * Data provider for testSuggestionIterator().
     *
     * @return array
     */
    public function getSuggestIterationData()
    {
        $out = [];

        // Case #0, Phrase type with all parameters set.
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
        $expectedOption = new PhraseOption('lorem adip', 0.0, '<span class="highlight">lorem</span> adip');

        $out[] = ['suggesters' => [$phrase], 'expectedOptions' => [$expectedOption]];

        // Case #1, Phrase type with almost nothing set.
        $phrase = new Phrase('description', 'Lorm adip');
        $expectedOption = new SimpleOption('lorem adip', 0.0);

        $out[] = ['suggesters' => [$phrase], 'expectedOptions' => [$expectedOption]];

        // Case #2, Term type with almost nothing set.
        $term = new Term('description', 'ipsu');
        $expectedOption = new TermOption('ipsum', 0.0, 3);

        $out[] = ['suggesters' => [$term], 'expectedOptions' => [$expectedOption]];

        // Case #3, Multiple suggesters.
        $term = new Term('description', 'ipsu');
        $phrase = new Phrase('description', 'Lorm adip');
        $expectedOptions = [new TermOption('ipsum', 0.0, 3), new SimpleOption('lorem adip', 0.0)];

        $out[] = ['suggesters' => [$term, $phrase], 'expectedOptions' => $expectedOptions];

        // Case #4, Multiple options within multiple suggesters.
        $term = new Term('description', 'distibutd');
        $phrase = new Phrase('description', 'Lorm adip');
        $expectedOptions = [
            new TermOption('disributed', 0.0, 1),
            new TermOption('distributed', 0.0, 1),
            new SimpleOption('lorem adip', 0.0),
        ];

        $out[] = ['suggesters' => [$term, $phrase], 'expectedOptions' => $expectedOptions];

        // Case #5, completion option using context suggester, with payload.
        $geoContext = new Context\GeoContext('location', ['lat' => 0, 'lon' => 0]);
        $categoryContext = new Context\CategoryContext('price', '500');
        $context = new Context('suggestions', 'cons');
        $context->addContext($geoContext);
        $context->addContext($categoryContext);
        $expectedOption = new CompletionOption('Lorem ipsum', 0.0, ['test' => true]);

        $out[] = ['suggesters' => [$context], 'expectedOptions' => [$expectedOption]];

        // Case #6, completion option using completion suggester, no payload.
        $completion = new Completion('completion_suggesting', 'ipsum');
        $expectedOption = new SimpleOption('Lorem ipsum', 0.0, null);

        $out[] = ['suggesters' => [$completion], 'expectedOptions' => [$expectedOption]];

        return $out;
    }

    /**
     * Iteration test.
     *
     * @param AbstractSuggester[] $suggesters
     * @param SimpleOption[]      $expectedOptions
     *
     * @dataProvider getSuggestIterationData()
     */
    public function testSuggestionIteration($suggesters, $expectedOptions)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('ONGRTestingBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);

        foreach ($suggesters as $suggester) {
            $search->addSuggester($suggester);
        }

        $iterator = $repo->execute($search, Repository::RESULTS_OBJECT);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\DocumentIterator', $iterator);

        $suggestions = $iterator->getSuggestions();

        $optionCount = 0;
        foreach ($suggestions as $suggestionEntries) {
            foreach ($suggestionEntries as $suggestionEntry) {
                $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionEntry', $suggestionEntry);
                foreach ($suggestionEntry->getOptions() as $option) {
                    $option->setScore(0.0);
                    $this->assertEquals($expectedOptions[$optionCount++], $option);
                }
            }
        }
        $this->assertEquals(count($expectedOptions), $optionCount, 'Expecteded option count was not met.');
    }

    /**
     * Check if suggestion properties are set as expected.
     */
    public function testSuggestionProperties()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('ONGRTestingBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);
        $search->addSuggester(new Phrase('description', 'Lorm adip', 'test'));

        $suggestions = $repo->execute($search, Repository::RESULTS_OBJECT)->getSuggestions();
        $this->assertEquals('Lorm adip', $suggestions['test'][0]->getText());
        $this->assertEquals(9, $suggestions['test'][0]->getLength());
        $this->assertEquals(0, $suggestions['test'][0]->getOffset());
    }
}
