<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL;

/**
 * Interface BuilderInterface.
 */
interface BuilderInterface
{
    /**
     * Generates array which will be passed to elasticsearch-php client.
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns element type.
     *
     * @return string
     */
    public function getType();
}
