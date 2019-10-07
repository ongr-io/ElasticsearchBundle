<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\App\Entity;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Document to test if it works from a not default directory.
 *
 * @ES\Index(alias="entity-index")
 */
class DummyDocumentInTheEntityDirectory
{

    CONST INDEX_NAME = 'entity-index';

    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="keyword")
     */
    public $keywordField;
}
