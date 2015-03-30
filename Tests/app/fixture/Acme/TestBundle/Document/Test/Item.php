<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Test;

use Ongr\ElasticsearchBundle\Annotation as ES;
use Ongr\ElasticsearchBundle\Document\AbstractDocument;
use Ongr\ElasticsearchBundle\Document\DocumentInterface;
use Ongr\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Document class Item.
 *
 * @ES\Document(create=false)
 */
class Item extends AbstractDocument
{
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
    protected $price;

    /**
     * @var \DateTime
     * 
     * @ES\Property(name="created_at", type="date")
     */
    private $createdAt;

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
