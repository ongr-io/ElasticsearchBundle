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
     * @var String|string[]
     */
    private $value;

    /**
     * Returns context value.
     *
     * @return array
     */
    public function getValue()
    {
        return [];
    }

    /**
     * Returns context type.
     *
     * @return string
     */
    public function getType()
    {
        return 'category';
    }
}
