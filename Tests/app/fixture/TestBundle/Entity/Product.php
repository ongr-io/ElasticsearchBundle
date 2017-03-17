<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Collection\Collection;

/**
 * Product document for testing.
 *
 * @ES\Document()
 */
class Product
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    public $id;

    /**
     * @var string
     * @ES\Property(type="keyword", name="title")
     */
    public $title;

    /**
     * @var CategoryObject[]
     *
     * @ES\Embedded(class="TestBundle:CategoryObject", multiple=true)
     */
    public $categories;

    public function __construct()
    {
        $this->categories = new Collection();
    }
}
