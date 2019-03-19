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

use ONGR\ElasticsearchBundle\Event\PrePersistEvent;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;

class PrePersistEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $entity = new Product();
        $event = new PrePersistEvent($entity);

        $this->assertInstanceOf(Product::class, $event->getDocument());
    }
}
