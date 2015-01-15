<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping\Proxy;

/**
 * Defines necessary methods that proxy documents should have.
 */
interface ProxyInterface
{
    /**
     * Should return if document exists on client index or not.
     *
     * @return bool
     */
    public function __isInitialized();
}
