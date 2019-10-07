<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Event;

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Event\PrePersistEvent;
use PHPUnit\Framework\TestCase;

class PrePersistEventTest extends TestCase
{
    public function testGetters()
    {
        $document = new DummyDocument();
        $event = new PrePersistEvent($document);

        $this->assertInstanceOf(DummyDocument::class, $event->getDocument());
    }
}
