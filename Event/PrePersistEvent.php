<?php

namespace ONGR\ElasticsearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PrePersistEvent extends Event
{
    /**
     * @var object
     */
    protected $document;

    /**
     * PrePersistEvent constructor.
     *
     * @param $document
     */
    public function __construct($document)
    {
        $this->document = $document;
    }

    /**
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }
}