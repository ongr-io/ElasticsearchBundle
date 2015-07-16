<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document\Suggester;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class ContextSuggesting.
 */
class ContextSuggesting extends AbstractSuggester implements ContextSuggesterInterface
{
    /**
     * @var array
     *
     * @ES\Property(type="object", name="context")
     */
    protected $context = [];

    /**
     * {@inheritdoc}
     */
    public function getContext($name = null)
    {
        if ($name === null) {
            return $this->context;
        }
        if (!isset($this->context[$name])) {
            return null;
        }

        return $this->context[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(array $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addContext($name, $value)
    {
        $this->context[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeContext($name)
    {
        if (array_key_exists($name, $this->context)) {
            unset($this->context[$name]);
        }

        return $this;
    }
}
