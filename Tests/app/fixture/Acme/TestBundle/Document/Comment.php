<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use Ongr\ElasticsearchBundle\Annotation as ES;
use Ongr\ElasticsearchBundle\Document\AbstractDocument;
use Ongr\ElasticsearchBundle\Document\DocumentInterface;
use Ongr\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Comment document for testing.
 *
 * @ES\Document(type="comment", parent="AcmeTestBundle:Content", ttl={"enabled":true, "default": "1d"})
 */
class Comment extends AbstractDocument
{
    /**
     * @var string
     *
     * @ES\Property(type="string", name="userName")
     */
    public $userName;

    /**
     * @var \DateTime
     *
     * @ES\Property(name="createdAt", type="date")
     */
    private $createdAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
