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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LifecycleCallbackListener.
 */
class LifecycleCallbackSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DocumentEvent::PRE_PERSIST => ['onDocumentPrePersist'],
        ];
    }

    /**
     * @param array $metadata
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Document pre create event.
     *
     * @param FilterDocumentEvent $event
     */
    public function onDocumentPrePersist(FilterDocumentEvent $event)
    {
        $methodName = $this->getMethodName($event, DocumentEvent::PRE_PERSIST);
        if ($methodName) {
            /** @var DocumentInterface $document */
            $document = $event->getDocument();

            if (method_exists($document, $methodName)) {
                call_user_func([$document, $methodName], $event);
            }
        }
    }

    /**
     * Check if event ir registered for particular class.
     *
     * @param FilterDocumentEvent $event
     * @param string              $eventName
     *
     * @return bool
     */
    private function getMethodName(FilterDocumentEvent $event, $eventName)
    {
        $className = get_class($event->getDocument());

        return isset($this->metadata[$className][$eventName]) ? $this->metadata[$className][$eventName] : false;
    }
}
