<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DSL\Highlight;

use Ongr\ElasticsearchBundle\DSL\Highlight\Field;
use Ongr\ElasticsearchBundle\DSL\Highlight\Highlight;
use Ongr\ElasticsearchBundle\DSL\Query\TermQuery;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;
use Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product;

/**
 * Highlighting functional test.
 */
class HighlightTest extends ElasticsearchTestCase
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
                        'description' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'foo baz bar bazbarfoo bar baz foo',
                        'description' => 'foo',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test highlighted output.
     *
     * The following methods of Highlight class tested:
     *  - setting specific tag without class
     *  - setting sort order by score
     */
    public function testHighlight0()
    {
        /**
         * @var Repository $repository
         * @var TermQuery  $termQuery
         */

        list($repository, $termQuery) = $this->buildRepositoryAndTerm();

        $highlight = new Highlight();
        $highlight->setTag('tag')
            ->setOrder('score')
            ->add((new Field('title'))->setForceSource(true));

        $results = $this->executeHighlight($repository, $termQuery, $highlight);

        $this->assertStringStartsWith(
            '<tag>',
            $results['hits']['hits'][0]['highlight']['title'][0]
        );
    }

    /**
     * Test highlighted output.
     *
     * The following methods of Highlight class tested:
     *  - setting specific tag configured with css class
     *  - setting fragment size
     *  - setting number of fragments
     */
    public function testHighlighting1()
    {
        /**
         * @var Repository $repository
         * @var TermQuery  $termQuery
         */

        list($repository, $termQuery) = $this->buildRepositoryAndTerm();

        $highlight = new Highlight();
        $highlight->setTag('tag', 'class')
            ->setFragmentSize(12)
            ->setNumberOfFragments(1)
            ->add((new Field('title'))->setForceSource(true));

        $results = $this->executeHighlight($repository, $termQuery, $highlight);

        $this->assertStringStartsWith(
            '<tag class="class">',
            $results['hits']['hits'][0]['highlight']['title'][0]
        );

        $this->assertLessThanOrEqual(
            12,
            strlen(strip_tags($results['hits']['hits'][1]['highlight']['title'][0]))
        );

        $this->assertEquals(1, count($results['hits']['hits'][1]['highlight']['title']));
    }

    /**
     * Test highlighted output.
     *
     * The following methods of Highlight class tested:
     *  - setting styled tags schema
     *  - removing field
     *  - setting global highlighting type
     */
    public function testHighlighting2()
    {
        /**
         * @var Repository $repository
         * @var TermQuery  $termQuery
         */

        list($repository, $termQuery) = $this->buildRepositoryAndTerm();

        $highlight = new Highlight();
        $highlight->setTagsSchema('styled')
            ->add((new Field('title'))->setForceSource(true))
            ->add(new Field('title'))
            ->add(new Field('description'));

        $highlight->remove('description');
        $highlight->setHighlighterType(Highlight::TYPE_PLAIN);

        $results = $this->executeHighlight($repository, $termQuery, $highlight);
        $product = $results['hits']['hits'][0];
        $this->assertStringStartsWith('<em class="hlt1">', $product['highlight']['title'][0]);
        $this->assertArrayNotHasKey('description', $product['highlight']);
    }

    /**
     * Check if highlight is parsed as expected.
     *
     * @expectedException \UnderflowException
     * @expectedExceptionMessage Offset unknown_field undefined.
     */
    public function testHighlightParse()
    {
        /**
         * @var Repository $repository
         * @var TermQuery  $termQuery
         */

        list($repository, $termQuery) = $this->buildRepositoryAndTerm();

        $highlight = new Highlight();
        $highlight->setTag('tag')
            ->setOrder('score')
            ->add((new Field('title'))->setForceSource(true));

        $result = $this->executeHighlight($repository, $termQuery, $highlight, Repository::RESULTS_OBJECT)[0];
        $this->assertStringStartsWith('<tag>foo</tag>', $result->getHighLight()['title']);
        $result->getHighLight()['unknown_field'];
    }

    /**
     * Get repository and build TermQuery. Return them.
     *
     * @return array
     */
    private function buildRepositoryAndTerm()
    {
        $repository = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $termQuery = new TermQuery('title', 'foo');

        return [
            $repository,
            $termQuery,
        ];
    }

    /**
     * Create and execute highlighted search.
     *
     * @param Repository $repository
     * @param TermQuery  $termQuery
     * @param Highlight  $highlight
     * @param string     $resultsType
     *
     * @return array|Product[]
     */
    private function executeHighlight($repository, $termQuery, $highlight, $resultsType = Repository::RESULTS_RAW)
    {
        $search = $repository->createSearch()
            ->addQuery($termQuery)
            ->setHighlight($highlight);

        $results = $repository->execute($search, $resultsType);

        return $results;
    }
}
