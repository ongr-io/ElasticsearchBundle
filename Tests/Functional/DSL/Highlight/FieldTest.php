<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Highlight;

use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Highlight as HighlightDocument;
use ONGR\ElasticsearchDSL\Highlight\Field;
use ONGR\ElasticsearchDSL\Highlight\Highlight;
use ONGR\ElasticsearchDSL\Query\FuzzyLikeThisQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;

/**
 * Highlighting functional test.
 */
class FieldTest extends AbstractElasticsearchTestCase
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
                        'title' => 'foo baz bar bazbarfoo bar baz foo',
                        'description' => 'description',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'foo baz bar bazbarfoo bar baz foo',
                        'description' => 'description',
                    ],
                ],
                'Highlight' => [
                    [
                        '_id' => 1,
                        'plain_field' => 'foo baz bazfoo baz foo',
                        'offsets_field' => 'foo baz bazfoo baz foo',
                        'term_vector_field' => 'foo baz bazfoo baz foo',
                    ],
                    [
                        '_id' => 2,
                        'plain_field' => 'foo baz bar bazbarfoo bar baz foo',
                        'offsets_field' => 'foo baz bar bazbarfoo bar baz foo',
                        'term_vector_field' => 'foo baz bar bazbarfoo bar baz foo',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test highlight for TermQuery.
     *
     * The following methods of Field class tested:
     *
     *  - setting highlighter type
     *  - setting fragment size
     *  - setting number of fragments
     */
    public function testHighlightedField()
    {
        $repository = $this
            ->getManager()
            ->getRepository('AcmeTestBundle:Product');

        $highlight = new Highlight();
        $highlight
            ->setTag('tag')
            ->add(
                (new Field('title'))
                    ->setForceSource(true)
                    ->setHighlighterType(Field::TYPE_PLAIN)
                    ->setFragmentSize(12)
                    ->setNumberOfFragments(1)
            );

        $search = $repository
            ->createSearch()
            ->addQuery(new TermQuery('title', 'foo'))
            ->setHighlight($highlight);

        $results = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertStringStartsWith(
            '<tag>',
            $results['hits']['hits'][0]['highlight']['title'][0]
        );

        $this->assertLessThanOrEqual(
            12,
            strlen(strip_tags($results['hits']['hits'][1]['highlight']['title'][0]))
        );

        $this->assertEquals(1, count($results['hits']['hits'][1]['highlight']['title']));
    }

    /**
     * Test query within highlight property.
     */
    public function testFieldHighlightQuery()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $termQuery = new TermQuery('title', 'foo');

        $highlight = new Highlight();
        $highlight
            ->add(
                (new Field('title'))
                    ->setForceSource(true)
                    ->setHighlightQuery($termQuery)
            );

        $search = $repo->createSearch()
            ->setSource('title')
            ->setHighlight($highlight);

        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertStringStartsWith('<em>', $results['hits']['hits'][0]['highlight']['title'][0]);
    }

    /**
     * Test for no match size.
     */
    public function testNoMatchSize()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $termQuery = new TermQuery('description', 'description');

        $highlight = new Highlight();
        $highlight
            ->add(
                (new Field('title'))
                    ->setForceSource(true)
                    ->setNoMatchSize(10)
            );

        $search = $repo->createSearch()
            ->addQuery($termQuery)
            ->setHighlight($highlight);

        $results = $repo->execute($search, Repository::RESULTS_RAW);

        $this->assertLessThanOrEqual(
            10,
            strlen(strip_tags($results['hits']['hits'][1]['highlight']['title'][0]))
        );
    }

    /**
     * Data provider for testHighlighterTypes.
     *
     * @return array
     */
    public function highlighterTypeTestProvider()
    {
        return [
            [
                'type' => Highlight::TYPE_POSTINGS,
                'field' => 'offsets_field',
            ],
            [
                'type' => Highlight::TYPE_FVH,
                'field' => 'term_vector_field',
            ],
        ];
    }

    /**
     * Tests if provided mapping allows for different highlighter types.
     *
     * @param string $type
     * @param string $field
     *
     * @dataProvider highlighterTypeTestProvider
     */
    public function testHighlighterTypes($type, $field)
    {
        /** @var Repository $repository */
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Highlight');
        $fuzzyLikeThisQuery = new FuzzyLikeThisQuery($field, 'bar');
        $highlight = new Highlight();
        $highlight->setHighlighterType($type);
        $highlight->add(new Field($field));

        $search = $repository->createSearch()
            ->addQuery($fuzzyLikeThisQuery)
            ->setHighlight($highlight);

        $result = $repository->execute($search);
        /** @var HighlightDocument $document */
        $document = $result[0];
        $highlightResult = $document->getHighLight();
        $this->assertNotEmpty($highlightResult[$field]);
    }
}
