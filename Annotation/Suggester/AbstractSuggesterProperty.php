<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation\Suggester;

/**
 * Abstract class for various suggester annotations.
 */
abstract class AbstractSuggesterProperty
{
    /**
     * @var string
     */
    public $type = 'completion';

    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * Object name to map.
     *
     * @var string
     */
     public $objectName;

    /**
     * Returns required properties.
     *
     * @return array
     */
    public function filter()
    {
        return ['type' => $this->type];
    }
}
