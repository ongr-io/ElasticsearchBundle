<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use Ongr\ElasticsearchBundle\Annotation as ES;
use Ongr\ElasticsearchBundle\Document\AbstractDocument;
use Ongr\ElasticsearchBundle\Document\DocumentInterface;
use Ongr\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Class ColorDocument.
 *
 * @ES\Document(type="color")
 */
class ColorDocument extends AbstractDocument
{
}
