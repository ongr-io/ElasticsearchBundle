<?php

namespace ONGR\ElasticsearchBundle\Tests\Unit\Event;

use ONGR\ElasticsearchBundle\Event\PrePersistEvent;

class PrePersistEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetProduct()
    {
        $document = new \stdClass();

        $event = new PrePersistEvent($document);

        self::assertEquals($document, $event->getDocument());
    }
}