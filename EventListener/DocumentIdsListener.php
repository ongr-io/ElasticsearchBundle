<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\EventListener;

use ONGR\ElasticsearchBundle\Event\CommitEvent;
use ONGR\ElasticsearchBundle\Event\PersistEvent;
use ONGR\ElasticsearchBundle\Service\IdSetter;

class DocumentIdsListener
{
    /**
     * @var IdSetter $setter
     */
    private $setter;

    /**
     * @param IdSetter $setter
     */
    public function __construct(IdSetter $setter)
    {
        $this->setter = $setter;
    }

    /**
     * @param PersistEvent $event
     */
    public function onEsPersist(PersistEvent $event)
    {
        $this->setter->persist($event->getDocument());
    }


    public function onESPostCommit(CommitEvent $event)
    {
        $this->setter->addIds($event->getBulkParams());
    }
}
