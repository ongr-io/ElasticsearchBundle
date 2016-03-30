<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\FooBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Testing document for representing media.
 *
 * @ES\Document(type="customer");
 */
class Customer
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    private $id;

    /**
     * Test adding raw mapping.
     *
     * @var string
     *
     * @ES\Property(name="name", type="string", options={"index"="not_analyzed"})
     */
    private $name;

    /**
     * Test adding raw mapping.
     *
     * @var boolean
     *
     * @ES\Property(name="active", type="boolean")
     */
    private $active;

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
