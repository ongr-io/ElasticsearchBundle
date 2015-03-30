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
 * Interface used by builders with names.
 */
interface NamedBuilderInterface extends BuilderInterface
{
    /**
     * Returns builder name.
     *
     * @return string
     */
    public function getName();
}
