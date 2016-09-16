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

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class DocumentScanIteratorTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        $documents = ['default' => ['product' => []]];

        for ($i = 0; $i < 4; $i++) {
            $documents['default']['product'][] = [
                '_id' => $i,
                'title' => 'content_' . $i,
                'price' => $i,
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
        $search->addSort(new FieldSort('price'));
        $search->addQuery(new MatchAllQuery());

        $out[] = ['search' => $search, true];

        // Case #1: search type set to scan, with a sort, results should not be sorted.
        $search = new Search();
        $search->setSize(2);
        $search->setScroll('1m');
        $search->setSearchType('scan');
        $search->addSort(new FieldSort('price'));
        $search->addQuery(new MatchAllQuery());

        $out[] = ['search' => $search, false];

        // Case #3: minimum size, should give the same results.
        $search = new Search();
        $search->setSize(1);
        $search->setScroll('1m');
        $search->addSort(new FieldSort('price'));
        $search->addQuery(new MatchAllQuery());

        $out[] = ['search' => $search, true];

        return $out;
    }

    /**
     * Iteration test.
     *
     * @deprecated Tested function will be removed in 3.0
     *
     * @param Search $search
     * @param bool   $isSorted
     *
     * @dataProvider getIterationData()
     */
    public function testIteration(Search $search, $isSorted)
    {
        $iterator = $this
            ->getManager()
            ->execute(['AcmeBarBundle:Product'], $search);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\DocumentIterator', $iterator);
        $this->assertCount(4, $iterator);

        $expectedHeaders = [
            'content_0',
            'content_1',
            'content_2',
            'content_3',
        ];

        $data = $this->iterateThrough($iterator);

        if ($isSorted) {
            $this->assertEquals($expectedHeaders, $data);
        } else {
            $this->assertEmpty(array_diff($expectedHeaders, $data));
        }
    }

    /**
     * Returns relevant data by iterating through.
     *
     * @param DocumentIterator $iterator
     *
     * @return array
     */
    protected function iterateThrough($iterator)
    {
        $data = [];
        foreach ($iterator as $result) {
            $data[] = $result->getTitle();
        }

        return $data;
    }
}
