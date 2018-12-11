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
 * Category document for testing.
 *
 * @ES\ObjectType
 */
class CategoryObject
{
    /**
     * @var string Field without ESB annotation, should not be indexed.
     */
    private $withoutAnnotation;

    /**
     * @var string
     * @ES\Property(type="string", name="title", options={"index"="not_analyzed"})
     */
    private $title;

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

    /**
     * @return string
     */
    public function getWithoutAnnotation()
    {
        return $this->withoutAnnotation;
    }

    /**
     * @param string $withoutAnnotation
     */
    public function setWithoutAnnotation($withoutAnnotation)
    {
        $this->withoutAnnotation = $withoutAnnotation;
    }


}
