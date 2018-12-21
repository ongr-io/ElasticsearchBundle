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

use ONGR\ElasticsearchBundle\Profiler\ElasticsearchProfiler;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\GlobalAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;

class ElasticsearchProfilerTest extends AbstractElasticsearchTestCase
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
            ],
        ];
    }

    /**
     * Tests if right amount of queries catched.
     */
    public function testGetQueryCount()
    {
        $manager = $this->getManager();

        $document = new Product();
        $document->setTitle('tuna');

        $manager->persist($document);
        $manager->commit();

        // Four queries executed while index was being created.
        $this->greaterThanOrEqual(4, $this->getCollector()->getQueryCount());
    }

    /**
     * Tests if correct time is being returned.
     */
    public function testGetTime()
    {
        $manager = $this->getManager();
        $manager->find('TestBundle:Product', 3);

        $this->assertGreaterThan(0.0, $this->getCollector()->getTime(), 'Time should be greater than 0ms');
    }

    /**
     * Tests if logged query are correct.
     */
    public function testGetQueries()
    {
        $manager = $this->getManager();
        $manager->find('TestBundle:Product', 2);

        // guard
        $queries = $this->getCollector()->getQueries();
        $this->assertInstanceOf(Data::class, $queries);
        $this->assertCount(1, $queries);

        $queries = $this->readAttribute($this->getCollector(), 'data')['queries'];
        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);

        $this->assertEquals(
            [
                'body' => '',
                'method' => 'GET',
                'httpParameters' => [],
                'scheme' => 'http',
                'port' => 9200,
            ],
            $lastQuery,
            'Logged data did not match expected data.'
        );
    }

    /**
     * Tests if term query is correct.
     */
    public function testGetTermQuery()
    {
        $manager = $this->getManager();

        $repository = $manager->getRepository('TestBundle:Product');
        $search = $repository
            ->createSearch()
            ->addQuery(new TermQuery('title', 'pizza'));
        $repository->findDocuments($search);

        // guard
        $queries = $this->getCollector()->getQueries();
        $this->assertInstanceOf(Data::class, $queries);
        $this->assertCount(1, $queries);

        $queries = $this->readAttribute($this->getCollector(), 'data')['queries'];
        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);

        $lastQuery['body'] = trim(preg_replace('/\s+/', '', $lastQuery['body']));

        $this->assertEquals(
            [
                'body' => json_encode($search->toArray()),
                'method' => 'GET',
                'httpParameters' => [],
                'scheme' => 'http',
                'port' => 9200,
            ],
            $lastQuery,
            'Logged data did not match expected data.'
        );
    }

    /**
     * Checks query parameters that are not static.
     *
     * @param array $query
     */
    public function checkQueryParameters(&$query)
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

    /**
     * @return ElasticsearchProfiler
     */
    private function getCollector()
    {
        $collector = $this->getContainer()->get('es.profiler');
        $collector->collect(new Request(), new Response());

        return $collector;
    }

    public function testMatchAllQuery()
    {
        $manager = $this->getManager();

        $repository = $manager->getRepository('TestBundle:Product');
        $search = $repository
            ->createSearch()
            ->addAggregation(new GlobalAggregation('g'));
        $repository->findDocuments($search);

        // guard
        $queries = $this->getCollector()->getQueries();
        $this->assertInstanceOf(Data::class, $queries);
        $this->assertCount(1, $queries);

        $queries = $this->readAttribute($this->getCollector(), 'data')['queries'];
        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);
        $lastQuery['body'] = trim(preg_replace('/\s+/', '', $lastQuery['body']));

        $this->assertEquals('{"aggregations":{"g":{"global":{}}}}', $lastQuery['body']);
    }
}
