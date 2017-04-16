<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Document;

/**
 * Test for DocumentTrait.
 */
class DocumentTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test if getHighlight throws exception.
     *
     * @expectedException \UnderflowException
     */
    public function testGetHighlight()
    {
        $documentTraitMock = $this->getMockForTrait('ONGR\ElasticsearchBundle\Document\DocumentTrait');
        $documentTraitMock->getHighlight();
    }
}
