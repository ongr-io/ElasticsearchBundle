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
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FilterDocumentEvent.
 */
class FilterDocumentEvent extends Event
{
    /**
     * @var DocumentInterface
     */
    protected $document;

    /**
     * @param DocumentInterface $document
     */
    public function __construct(DocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->document;
    }
}
