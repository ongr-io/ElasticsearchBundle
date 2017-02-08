<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Exception;

/**
 * This is the exception which should be thrown when class does not have @ONGR\ElasticsearchBundle\Annotation\Document
 * annotation.
 */
class MissingDocumentAnnotationException extends DocumentParserException
{
}
