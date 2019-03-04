<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\NestedType()
 */
class CollectionNested
{
    /**
     * @ES\Property(type="keyword")
     */
    public $key;

    /**
     * @ES\Property(type="keyword")
     */
    public $value;
}