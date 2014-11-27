<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document\Suggester\Context;

/**
 * Category context type for context suggester.
 */
class CategoryContext extends AbstractContext
{
    /**
     * Type to use as a context.
     *
     * @var string|array
     */
    private $types;

    /**
     * Returns type.
     *
     * @return string|array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Sets type.
     *
     * @param $type string|array
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function getContextType()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->types;
    }
}
