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

use ONGR\ElasticsearchBundle\Event\PersistEvent;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;

class PersistEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDocument()
    {
        $document = new Product();
        $document->setTitle('foo');

        $event = new PersistEvent($document);

        $this->assertEquals($document, $event->getDocument());
    }
}
