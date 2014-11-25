<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation\Suggester\Context;

/**
 * Class for geo category context annotations used in context suggester.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
class CategoryContext extends AbstractContext
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'category';
    }
}
