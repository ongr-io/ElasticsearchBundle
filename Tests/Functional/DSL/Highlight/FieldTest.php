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

use ONGR\ElasticsearchBundle\DSL\Highlight\Field;
use ONGR\ElasticsearchBundle\DSL\Highlight\Highlight;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Highlighting functional test
 */
class FieldTest extends ElasticsearchTestCase
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
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $termQuery = new TermQuery('title', 'foo');

        $highlight = new Highlight();
        $highlight->setTag('tag')
            ->addField(
                (new Field('title'))
                    ->setForceSource(true)
                    ->setHighlighterType(Field::TYPE_PLAIN)
                    ->setFragmentSize(12)
                    ->setNumberOfFragments(1)
            );

        $search = $repo->createSearch()
            ->addQuery($termQuery)
            ->setHighlight($highlight);

        $results = $repo->execute($search, Repository::RESULTS_RAW);

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
            ->addField(
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
            ->addField(
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
}
