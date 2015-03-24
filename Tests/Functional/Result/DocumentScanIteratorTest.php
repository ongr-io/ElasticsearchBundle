<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\Result;

use Ongr\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use Ongr\ElasticsearchBundle\DSL\Search;
use Ongr\ElasticsearchBundle\DSL\Sort\Sort;
use Ongr\ElasticsearchBundle\ORM\Repository;
use Ongr\ElasticsearchBundle\Result\DocumentScanIterator;
use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;
use Ongr\ElasticsearchBundle\Test\TestHelperTrait;

class DocumentScanIteratorTest extends ElasticsearchTestCase
{
    use TestHelperTrait;

    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        $documents = ['default' => ['foocontent' => []]];

        for ($i = 0; $i < 4; $i++) {
            $documents['default']['foocontent'][] = [
                '_id' => 'someId_' . $i,
                'header' => 'content_' . $i,
            ];
        }

        return $documents;
    }

    /**
     * Data provider for testIteration.
     *
     * @return array
     */
    public function getIterationData()
    {
        $out = [];

        // Case #0: no search type set, with a sort, results should be sorted.
        $search = new Search();
        $search->setSize(2);
        $search->setScroll('1m');
        $search->addSort(new Sort('header'));
        $search->addQuery(new MatchAllQuery());

        $out[] = ['search' => $search, true];

        // Case #1: search type set to scan, with a sort, results should not be sorted.
        $search = new Search();
        $search->setSize(2);
        $search->setScroll('1m');
        $search->setSearchType('scan');
        $search->addSort(new Sort('header'));
        $search->addQuery(new MatchAllQuery());

        $out[] = ['search' => $search, false];

        // Case #3: minimum size, should give the same results.
        $search = new Search();
        $search->setSize(1);
        $search->setScroll('1m');
        $search->addSort(new Sort('header'));
        $search->addQuery(new MatchAllQuery());

        $out[] = ['search' => $search, true];

        return $out;
    }

    /**
     * Iteration test.
     *
     * @param Search $search
     * @param bool   $isSorted
     *
     * @dataProvider getIterationData()
     */
    public function testIteration(Search $search, $isSorted)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Content');

        $iterator = $repo->execute($search, Repository::RESULTS_OBJECT);

        $this->assertInstanceOf('Ongr\ElasticsearchBundle\Result\DocumentScanIterator', $iterator);
        $this->assertCount(4, $iterator);

        $expectedHeaders = [
            'content_0',
            'content_1',
            'content_2',
            'content_3',
        ];

        // Iterate multiple times to see if it's cached correctly.
        if ($isSorted) {
            $this->assertEquals($expectedHeaders, $this->iterateThrough($iterator));
            $this->assertEquals($expectedHeaders, $this->iterateThrough($iterator));
        } else {
            $this->assertArrayContainsArrayValues($expectedHeaders, $this->iterateThrough($iterator), true);
            $this->assertArrayContainsArrayValues($expectedHeaders, $this->iterateThrough($iterator), true);
        }
    }

    /**
     * Returns relevant data by iterating through.
     *
     * @param DocumentScanIterator $iterator
     *
     * @return array
     */
    protected function iterateThrough($iterator)
    {
        $data = [];
        foreach ($iterator as $result) {
            $data[] = $result->header;
        }

        return $data;
    }
}
