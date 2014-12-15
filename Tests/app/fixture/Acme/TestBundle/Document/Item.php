<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Document class Item.
 *
 * @ES\Document(create=false)
 */
class Item implements DocumentInterface
{
    use DocumentTrait;

    /**
     * @var string
     *
     * @ES\Property(name="name", type="string")
     */
    public $name;

    /**
     * @var float
     *
     * @ES\Property(type="float", name="price")
     */
    public $price;

    /**
     * @var \DateTime
     * 
     * @ES\Property(name="created_at", type="date")
     */
    public $createdAt;
}
