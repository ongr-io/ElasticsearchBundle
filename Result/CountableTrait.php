<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

/**
 * Class CountableTrait.
 */
trait CountableTrait
{
    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    abstract protected function getCount();
}
