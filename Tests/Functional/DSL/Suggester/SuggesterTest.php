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

use ONGR\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\PhraseOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\TermOption;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Suggester\Suggester;

/**
 * Class SuggesterTest.
 */
class SuggesterTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'Suggester' => [
                    [
                        '_id' => 1,
                        'title' => 'title 1',
                        'completionSuggester' => [
                            'input' => ['title'],
                            'output' => 'title 1',
                            'payload' => ['_id' => 1],
                            'weight' => 1,
                        ],
                    ],
                    [
                        '_id' => 2,
                        'title' => 'title 2',
                        'completionSuggester' => [
                            'input' => ['title', 'new'],
                            'output' => 'title 2',
                            'payload' => ['_id' => 2],
                            'weight' => 2,
                        ],
                    ],
                    [
                        '_id' => 3,
                        'title' => 'Something original',
                        'completionSuggester' => [
                            'input' => ['title 3', 'Something', 'original'],
                            'output' => 'Something original',
                            'payload' => ['_id' => 3],
                            'weight' => 1,
                        ],
                    ],
                    [
                        '_id' => 4,
                        'title' => 'Something old',
                        'completionSuggester' => [
                            'input' => ['title 4', 'Something', 'old'],
                            'output' => 'Something old',
                            'payload' => ['_id' => 4],
                            'weight' => 0.1,
                        ],
                    ],
                    [
                        '_id' => 5,
                        'title' => 'Something Something',
                        'completionSuggester' => [
                            'input' => ['Something'],
                            'output' => 'Something',
                            'payload' => ['_id' => 5],
                            'weight' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests term suggester.
     */
    public function testTermSuggest()
    {
        $this->markTestSkipped('Waiting for DSL update');

        $suggester = new Suggester(Suggester::TYPE_TERM, 'title', 'someting titl');
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Suggester');
        $suggestResult = $repository->suggest($suggester);

        foreach ($suggestResult['title-term'] as $suggestionEntry) {
            foreach ($suggestionEntry->getOptions() as $option) {
                /** @var TermOption $option */
                $this->assertInstanceOf(
                    'ONGR\ElasticsearchBundle\Result\Suggestion\Option\TermOption',
                    $option
                );
                $this->assertNotEmpty($option->getFreq());
            }
        }
    }

    /**
     * Tests phrase suggester.
     */
    public function testPhraseSuggest()
    {
        $this->markTestSkipped('Waiting for DSL update');

        $suggester = new Suggester(Suggester::TYPE_PHRASE, 'title', 'Someting original');
        $suggester->addParameter('highlight', ['pre_tag' => '*', 'post_tag' => '*']);
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Suggester');
        $suggestResult = $repository->suggest($suggester);

        foreach ($suggestResult['title-phrase'] as $suggestionEntry) {
            foreach ($suggestionEntry->getOptions() as $option) {
                /** @var PhraseOption $option */
                $this->assertInstanceOf(
                    'ONGR\ElasticsearchBundle\Result\Suggestion\Option\PhraseOption',
                    $option
                );
                $this->assertNotEmpty($option->getHighlighted());
            }
        }
    }

    /**
     * Tests completion suggester.
     */
    public function testCompletionSuggest()
    {
        $this->markTestSkipped('Waiting for DSL update');

        $suggester = new Suggester(Suggester::TYPE_COMPLETION, 'completionSuggester', 'ti');
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Suggester');
        $suggestResult = $repository->suggest($suggester);

        foreach ($suggestResult as $suggestionEntries) {
            foreach ($suggestionEntries as $suggestionEntry) {
                foreach ($suggestionEntry->getOptions() as $option) {
                    /** @var CompletionOption $option */
                    $this->assertInstanceOf(
                        'ONGR\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption',
                        $option
                    );
                    $this->assertNotEmpty($option->getPayload());
                }
            }
        }
    }

    /**
     * Tests phrase without highlight.
     */
    public function testSimpleOption()
    {
        $this->markTestSkipped('Waiting for DSL update');

        $suggester = new Suggester(Suggester::TYPE_PHRASE, 'title', 'Someting original');
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Suggester');
        $suggestResult = $repository->suggest($suggester);

        foreach ($suggestResult['title-phrase'] as $suggestionEntry) {
            $this->assertEquals('Someting original', $suggestionEntry->getText());
            $this->assertEquals(0, $suggestionEntry->getOffset());
            $this->assertEquals(17, $suggestionEntry->getLength());
            foreach ($suggestionEntry->getOptions() as $option) {
                $this->assertInstanceOf(
                    'ONGR\ElasticsearchBundle\Result\Suggestion\Option\SimpleOption',
                    $option
                );
                $this->assertNotEmpty($option->getScore());
                $this->assertNotEmpty($option->getText());
            }
        }

        $this->assertNull($suggestResult['non-existent']);
        $this->assertNull($suggestResult['title-phrase'][0]->getOptions()['non-existent']);
    }
}
