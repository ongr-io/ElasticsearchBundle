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

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
        ];
    }

    public function testIndexCrate()
    {
        $index = $this->getIndex(DummyDocument::class);

        $client = $index->getClient();
        $actualIndexExists = $client->indices()->exists(['index' => DummyDocument::INDEX_NAME]);

        $this->assertTrue($actualIndexExists);
    }
}
