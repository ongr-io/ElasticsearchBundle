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

use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class DocumentScanIteratorTest extends ElasticsearchTestCase
{
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
     * Iteration test.
     */
    public function testIteration()
    {
        /** @var Repository $repo */

        $repo = $this->getManager()->getRepository('AcmeTestBundle:Content');

        $search = $repo->createSearch();
        $search->setSize(2);
        $search->setScroll('1m');
        $search->addQuery(new MatchAllQuery());

        $iterator = $repo->execute($search, Repository::RESULTS_OBJECT);

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Result\DocumentScanIterator', $iterator);
        $this->assertCount(4, $iterator);

        $headers = [];
        foreach ($iterator as $_result) {
            $headers[] = $_result->header;
        }
        sort($headers);

        $expectedHeaders = ['content_0', 'content_1', 'content_2', 'content_3'];

        $this->assertEquals($expectedHeaders, $headers);
    }
}
