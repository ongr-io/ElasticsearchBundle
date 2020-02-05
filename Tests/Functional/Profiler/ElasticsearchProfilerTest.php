<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Profiler;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Profiler\ElasticsearchProfiler;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\GlobalAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ElasticsearchProfilerTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'foo',
                ],
                [
                    '_id' => 2,
                    'title' => 'bar',
                ],
                [
                    '_id' => 3,
                    'title' => 'pizza',
                ],
            ],
        ];
    }

    /**
     * Tests if multiple queries are captured.
     */
    public function testGetQueryCount()
    {
        $index = $this->getIndex(DummyDocument::class);

        $document = new DummyDocument();
        $document->title = 'tuna';

        $index->persist($document);
        $index->commit();

        // Four queries executed while index was being created.
        $this->assertGreaterThanOrEqual(4, $this->getCollector()->getQueryCount());
    }

    /**
     * Tests if a returned time is correct.
     */
    public function testGetTime()
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->find(3);

        $this->assertGreaterThan(0.0, $this->getCollector()->getTime(), 'Time should be greater than 0ms');
    }

    /**
     * Tests if a logged query is correct.
     */
    public function testGetQueries()
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->find(2);
        $queries = $this->getCollector()->getQueries();

        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);

        $this->assertEquals(
            [
                'body' => '',
                'method' => 'GET',
                'httpParameters' => [],
                'scheme' => 'http',
            ],
            $lastQuery,
            'Logged data did not match expected data.'
        );
    }

    /**
     * Tests if a term query is correct.
     */
    public function testGetTermQuery()
    {
        $index = $this->getIndex(DummyDocument::class);
        $search = $index
            ->createSearch()
            ->addQuery(new TermQuery('title', 'pizza'));
        $index->findDocuments($search);

        $queries = $this->getCollector()->getQueries();
        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);

        $lastQuery['body'] = trim(preg_replace('/\s+/', '', $lastQuery['body']));

        $this->assertEquals(
            [
                'body' => json_encode($search->toArray()),
                'method' => 'POST',
                'httpParameters' => [],
                'scheme' => 'http',
            ],
            $lastQuery,
            'Logged data did not match expected data.'
        );
    }

    /**
     * Checks query parameters.
     */
    public function checkQueryParameters(array &$query)
    {
        $this->assertArrayHasKey('time', $query, 'Query should have time set.');
        $this->assertGreaterThan(0.0, $query['time'], 'Time should be greater than 0');
        unset($query['time']);

        $this->assertArrayHasKey('host', $query, 'Query should have host set.');
        $this->assertNotEmpty($query['host'], 'Host should not be empty');
        unset($query['host']);

        $this->assertArrayHasKey('path', $query, 'Query should have host path set.');
        $this->assertNotEmpty($query['path'], 'Path should not be empty.');
        unset($query['path']);
    }

    public function testMatchAllQuery()
    {
        $index = $this->getIndex(DummyDocument::class);

        $search = $index
            ->createSearch()
            ->addAggregation(new GlobalAggregation('g'));
        $index->findDocuments($search);

        $queries = $this->getCollector()->getQueries();
        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);
        $lastQuery['body'] = trim(preg_replace('/\s+/', '', $lastQuery['body']));

        $this->assertEquals('{"aggregations":{"g":{"global":{}}}}', $lastQuery['body']);
    }

    private function getCollector(): ElasticsearchProfiler
    {
        $collector = $this->getContainer()->get(ElasticsearchProfiler::class);
        $collector->collect(new Request(), new Response());

        return $collector;
    }
}
