<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractDocument;

/**
 * Product document for testing.
 *
 * @ES\Document(type="product")
 */
class ProductDocument extends AbstractDocument
{
    /**
     * @var string
     *
     * @ES\Property(type="string", name="title", fields={@ES\MultiField(name="raw", type="string")})
     */
    public $title;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="description")
     */
    public $description;

    /**
     * @var int
     *
     * @ES\Property(type="float", name="price")
     */
    public $price;

    /**
     * @var string
     *
     * @ES\Property(type="geo_point", name="location")
     */
    public $location;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="limited", index="not_analyzed", ignoreAbove=20)
     */
    public $limited;
}
