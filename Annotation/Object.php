<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation;

if (version_compare(PHP_VERSION, '7.2.0') < 0) {
    class_alias('ONGR\ElasticsearchBundle\Annotation\ObjectType', 'ONGR\ElasticsearchBundle\Annotation\Object', false);
    class_exists('ONGR\ElasticsearchBundle\Annotation\ObjectType');
}
