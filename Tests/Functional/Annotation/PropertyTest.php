<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Annotation;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class PropertyTest extends AbstractElasticsearchTestCase
{
    public function testPropertyTypeName()
    {
        /** @var IndexService $index */
        $index = $this->getIndex(DummyDocument::class, false);
        $meta = $index->getIndexSettings()->getIndexMetadata();

        $this->assertEquals(
            [
                'fields' =>
                    [
                        'raw' => [
                            'type' => 'keyword',
                        ],
                        'increment' => [
                            'type' => 'text',
                            'analyzer' => 'incrementalAnalyzer',
                        ],
                    ],
                'type' => 'text',
            ],
            $meta['mappings']['properties']['title']
        );
    }
}
