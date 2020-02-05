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

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class DocumentNullObjectFieldTest extends AbstractElasticsearchTestCase
{
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 'foo',
                    'title' => null,
                ],
            ],
        ];
    }

    /**
     * Test if fetched object field is actually NULL.
     */
    public function testResultWithNullObjectField()
    {
        /** @var DummyDocument $document */
        $document = $this->getIndex(DummyDocument::class)->find('foo');

        $this->assertInstanceOf(
            DummyDocument::class,
            $document
        );

        $this->assertNull($document->title);
    }
}
