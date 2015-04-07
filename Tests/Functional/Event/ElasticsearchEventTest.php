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

use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Event\ElasticsearchDocumentEvent;
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
     * Data provider for testPersistEventDispatch().
     *
     * @return array
     */
    public function getTestPersistEventDispatchData()
    {
        return [
            [Events::PRE_PERSIST],
            [Events::POST_PERSIST],
        ];
    }

    /**
     * Tests if ElasticsearchDocumentEvent is dispatched before/after document is persisted.
     *
     * @param string $eventName
     *
     * @dataProvider getTestPersistEventDispatchData
     */
    public function testPersistEventDispatch($eventName)
    {
        /** @var string $productTitle */
        $productTitle = '';

        /** @var Connection $connection */
        $connection = null;

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            $eventName,
            function (ElasticsearchDocumentEvent $event) use (&$connection, &$productTitle) {
                $connection = $event->getConnection();
                $productTitle = $event->getDocument()->title;
            }
        );

        $product = new Product();
        $product->title = 'Test product';

        /** @var Manager $manager */
        $manager = $this->getManager();
        $manager->setEventDispatcher($this->getContainer()->get('event_dispatcher'));
        $manager->persist($product);

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Client\Connection',
            $connection,
            'Connection passed to the event must be instance of Connection class.'
        );
        $this->assertEquals(
            'Test product',
            $productTitle,
            'Document passed to the event must be the same as the persisting document.'
        );
    }

    /**
     * Data provider for testCommitEventDispatch().
     *
     * @return array
     */
    public function getTestCommitEventDispatchData()
    {
        return [
            [Events::PRE_COMMIT],
            [Events::POST_COMMIT],
        ];
    }

    /**
     * Tests if ElasticsearchEvent is dispatched before/after data are commited.
     *
     * @param string $eventName
     *
     * @dataProvider getTestCommitEventDispatchData
     */
    public function testCommitEventDispatch($eventName)
    {
        $eventDispatched = false;

        /** @var Connection $connection */
        $connection = null;

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            $eventName,
            function (ElasticsearchEvent $event) use (&$connection, &$eventDispatched) {
                $connection = $event->getConnection();
                $eventDispatched = true;
            }
        );

        $product = new Product();
        $product->title = 'Test product';

        /** @var Manager $manager */
        $manager = $this->getManager();
        $manager->setEventDispatcher($this->getContainer()->get('event_dispatcher'));
        $manager->persist($product);

        $this->assertFalse($eventDispatched, 'Event should have not been dispatched yet.');
        $manager->commit();
        $this->assertTrue($eventDispatched, 'Event should have been already dispatched.');
        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Client\Connection',
            $connection,
            'Connection passed to the event must be instance of Connection class.'
        );
    }
}
