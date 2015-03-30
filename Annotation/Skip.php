<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Annotation;

/**
 * Annotation used to skip properties during the parsing process.
 * 
 * @Annotation
 * @Target("CLASS")
 */
final class Skip extends AbstractValue
{
}
