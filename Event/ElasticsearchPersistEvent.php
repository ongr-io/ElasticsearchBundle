<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Event;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Service\Manager;

/**
 * Event to be dispatched before and after persisting a document.
 */
class ElasticsearchPersistEvent extends AbstractElasticsearchEvent
{
    /**
     * @var DocumentInterface
     */
    protected $document;

    /**
     * @param Manager           $manager
     * @param DocumentInterface $document
     */
    public function __construct(Manager $manager, DocumentInterface $document)
    {
        parent::__construct($manager);

        $this->document = $document;
    }

    /**
     * Returns document associated with the event.
     *
     * @return DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }
}
