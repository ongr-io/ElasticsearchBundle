<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\LongDescriptionTrait;

class DocumentParserTest extends \PHPUnit_Framework_TestCase
{
    /*
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $reader;

    /*
     * @var \ONGR\ElasticsearchBundle\Mapping\DocumentFinder
     */
    private $finder;

    public function setUp()
    {
        $this->reader = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')
            ->disableOriginalConstructor()
            ->getMock();

        $this->finder = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\DocumentFinder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testReturnFalseOnTrait()
    {
        $traitReflection = new \ReflectionClass(
            '\\ONGR\\ElasticsearchBundle\\Tests\\app\\fixture\\TestBundle\\Document\\LongDescriptionTrait'
        );

        $parser = new DocumentParser($this->reader, $this->finder);
        $this->assertFalse($parser->parse($traitReflection));
    }
}
