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

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Annotation as ES;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ES\Index(alias="field-data-index")
 */
class IndexWithFieldsDataDocument
{
    // This con't is only as a helper.
    CONST INDEX_NAME = 'field-data-index';

    /**
     * @ES\Id()
     */
    private $id;

    /**
     * @ES\Property(type="text", name="private", settings={"fielddata"=true})
     */
    public $title;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
