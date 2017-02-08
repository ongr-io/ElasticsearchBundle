<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product as BaseProduct;

/**
 * Product document for testing.
 *
 * @ES\Document()
 */
class Product extends BaseProduct
{
}
