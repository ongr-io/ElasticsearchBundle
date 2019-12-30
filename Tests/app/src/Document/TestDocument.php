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

/**
 * test document for unit testing of DocumentParser class
 *
 * @ES\Index(alias="testdocument")
 */
class TestDocument
{
    // This con't is only as a helper.
    CONST INDEX_NAME = 'testdocument';

    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(
     *  type="text",
     *  name="title",
     *  fields={
     *    "raw"={"type"="keyword"},
     *    "increment"={"type"="text", "analyzer"="incrementalAnalyzer"},
     *    "sorting"={"type"="keyword", "normalizer"="lowercase_normalizer"}
     *  }
     * )
     */
    public $title;
}
