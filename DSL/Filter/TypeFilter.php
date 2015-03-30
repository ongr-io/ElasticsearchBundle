<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Filter;

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Represents Elasticsearch "type" filter.
 *
 * Filters documents matching the provided type.
 */
class TypeFilter implements BuilderInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * Constructor.
     *
     * @param string $type Type name.
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'type';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'value' => $this->type,
        ];
    }
}
