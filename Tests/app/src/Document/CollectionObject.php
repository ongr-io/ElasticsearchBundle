<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\ObjectType()
 */
class CollectionObject
{
    /**
     * @var string
     * @ES\Property(type="keyword", name="title")
     */
    private $title;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): CollectionObject
    {
        $this->title = $title;
        return $this;
    }
}