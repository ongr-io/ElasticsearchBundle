<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractDocument;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Class ColorDocument.
 *
 * @ES\Document(type="color", all={"enabled": false})
 */
class ColorDocument extends AbstractDocument
{
    /**
     * @var CdnObject[]
     *
     * @ES\Property(
     *      type="object",
     *      name="disabled_cdn",
     *      enabled=false,
     *      multiple=true,
     *      objectName="AcmeTestBundle:CdnObject"
     * )
     */
    public $disabledCdn;

    /**
     * @var CdnObject[]
     *
     * @ES\Property(
     *      type="object",
     *      name="enabled_cdn",
     *      multiple=true,
     *      objectName="AcmeTestBundle:CdnObject"
     * )
     */
    public $enabledCdn;

    /**
     * @var string
     *
     * @ES\Property(includeInAll=false, type="string", name="excluded_from_all")
     */
    public $excludedFromAll;

    /**
     * @var string
     *
     * @ES\Property(includeInAll=true, type="string", name="included_in_all")
     */
    public $includedInAll;

    /**
     * @var int
     *
     * @ES\Property(
     *     type="string",
     *     name="pieces_count",
     *     fields={@ES\MultiField(name="count", type="token_count", analyzer="whitespace")}
     * )
     */
    public $piecesCount;
}
