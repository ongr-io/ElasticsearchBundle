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
 * Document to test if it works from a not default directory.
 *
 * @ES\Document()
 */
class DummyDocumentInNotDefaultDirectory
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    public $id;

    /**
     * @var string
     * @ES\Property(type="keyword", name="keyword_field")
     */
    public$keywordField;
}
