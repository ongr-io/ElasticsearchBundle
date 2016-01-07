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
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Person\Address;

/**
 * Place document for testing.
 *
 * IMPORTANT: this class is used to test if document with setters/getters
 * works correctly. Do not remove these methods.
 *
 * @ES\Document(type="place")
 */
class Place
{
    /**
     * @var string
     *
     * @ES\Property(type="string")
     */
    private $title;

    /**
     * @var Address
     *
     * @ES\Embedded(class="AcmeBarBundle:Person\Address")
     */
    private $address;

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param Address|null $address
     */
    public function setAddress(Address $address = null)
    {
        $this->address = $address;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }
}
