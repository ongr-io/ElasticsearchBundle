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
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\ProductDocument;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $document = new ProductDocument();
        $document->title = 'tuna';

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
        $repository = $manager->getRepository('AcmeBarBundle:ProductDocument');
        $repository->find(3);

        $this->assertGreaterThan(0.0, $this->getCollector()->getTime(), 'Time should be greater than 0ms');
    }

    /**
     * Tests if logged query are correct.
     */
    public function testGetQueries()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('AcmeBarBundle:ProductDocument');
        $repository->find(2);
        $queries = $this->getCollector()->getQueries();

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

        $repository = $manager->getRepository('AcmeBarBundle:ProductDocument');
        $search = $repository
            ->createSearch()
            ->addQuery(new TermQuery('title', 'pizza'));
        $result = $repository->execute($search, Repository::RESULTS_OBJECT);

        $queries = $this->getCollector()->getQueries();
        $lastQuery = end($queries[ElasticsearchProfiler::UNDEFINED_ROUTE]);
        $this->checkQueryParameters($lastQuery);

        $this->assertEquals(
            [
                'body' => [],
                'method' => 'POST',
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
}
