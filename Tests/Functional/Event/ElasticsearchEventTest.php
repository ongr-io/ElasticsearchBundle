<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Event;

use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Event\ElasticsearchEvent;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\_Proxy\Product;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Tests checking that ElasticsearchEvent is being dispatched correctly.
 */
class ElasticsearchEventTest extends AbstractElasticsearchTestCase
{
    /**
     * Tests if ElasticsearchEvent is dispatched before document is persisted.
     */
    public function testPrePersistEventDispatch()
    {
        /** @var string $productTitle */
        $productTitle = '';

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            Events::PRE_PERSIST,
            function (ElasticsearchEvent $event) use (&$productTitle) {
                $productTitle = $event->getDocument()->title;
            }
        );

        $product = new Product();
        $product->title = 'Test product';

        /** @var Manager $manager */
        $manager = $this->getManager();
        $manager->persist($product);

        $this->assertEquals('Test product', $productTitle);
    }
}
