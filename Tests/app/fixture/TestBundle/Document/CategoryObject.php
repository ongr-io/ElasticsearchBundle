<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Category object for testing.
 *
 * @ES\ObjectType
 */
class CategoryObject
{
    /**
     * @var string
     * @ES\Property(type="text", options={"index"="not_analyzed"})
     */
    private $title;

    /**
     * Public property to test converter if it can handle private and public properties.
     *
     * @var string
     * @ES\Property(type="text", options={"analyzer":"keyword"})
     */
    public $description;

    /*
     * Test the use of traits
     */
    use LongDescriptionTrait;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}
