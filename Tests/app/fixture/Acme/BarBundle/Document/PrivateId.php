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

/**
 * Product document for testing.
 *
 * @ES\Document(type="private")
 */
class PrivateId
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    private $id;

    /**
     * @var string
     * @ES\Property(type="string", name="title")
     */
    public $title;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
