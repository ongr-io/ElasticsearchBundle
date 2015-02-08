<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\Suggester\AbstractSuggester;
use ONGR\ElasticsearchBundle\Document\Suggester\ContextSuggesterInterface;

/**
 * Suggesting document for testing.
 *
 * @ES\Object()
 */
class PriceLocationSuggesting extends AbstractSuggester implements ContextSuggesterInterface
{
    /**
     * @var object
     *
     * @ES\Property(type="object", objectName="AcmeTestBundle:PriceLocationContext", name="context")
     */
    private $context;

    /**
     * Returns context to be used for completion.
     *
     * @return object
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets context to be used for completion.
     *
     * @param object $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}
