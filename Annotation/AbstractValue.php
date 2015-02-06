<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation;

/**
 * Abstract annotations used for handling value.
 */
abstract class AbstractValue
{
    /**
     * @var array
     */
    public $value;

    /**
     * Constructor.
     *
     * @param array $values
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (is_string($values['value'])) {
            $this->value = [$values['value']];
        } elseif (is_array($values['value'])) {
            $this->value = $values['value'];
        } else {
            throw new \InvalidArgumentException(
                'Annotation `' . get_class($this) . '` unexpected type given. Expected string or array, given `'
                . gettype($values['value']) . '`'
            );
        }
    }
}
