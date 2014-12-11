<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Service;

use ONGR\ElasticsearchBundle\Service\ESDataCollector;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ESDataCollectorTest extends ElasticsearchTestCase
{
    const START_QUERY_COUNT = 8;

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
        $repository = $manager->getRepository('ONGRTestingBundle:Product');

        $document = $repository->createDocument();
        $document->title = 'Awesomo';

        $manager->persist($document);
        $manager->commit();

        // Four queries executed while index was being created.
        $this->assertEquals(4, $this->getCollector()->getQueryCount() - self::START_QUERY_COUNT);
    }

    /**
     * Tests if correct time is being returned.
     */
    public function testGetTime()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('ONGRTestingBundle:Product');
        $repository->find(3);

        $this->assertGreaterThan(0.0, $this->getCollector()->getTime(), 'Time should be greater than 0ms');
    }

    /**
     * Tests if logged query are correct.
     */
    public function testGetQueries()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('ONGRTestingBundle:Product');
        $repository->find(2);
        $queries = $this->getCollector()->getQueries();

        $lastQuery = end($queries[ESDataCollector::UNDEFINED_ROUTE]);
        $time = $lastQuery['time'];
        unset($lastQuery['time']);

        $this->assertGreaterThan(0.0, $time, 'Time should be greater than 0');
        $this->assertEquals(
            [
                'body' => '',
                'method' => 'GET',
                'uri' => 'http://127.0.0.1:9200/ongr-elasticsearch-bundle-test/product/2',
            ],
            $lastQuery,
            'Logged data did not match expected data.'
        );
    }

    /**
     * @return ESDataCollector
     */
    private function getCollector()
    {
        $collector = $this->getContainer()->get('es.collector');
        $collector->collect(new Request(), new Response());

        return $collector;
    }
}
