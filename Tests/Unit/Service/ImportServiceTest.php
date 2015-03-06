<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Aggregation;

use ONGR\ElasticsearchBundle\Service\ImportService;

class ImportServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests importIndex method exception.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Currently only raw import is supported. Please set --raw flag to use it.
     */
    public function testImportIndexException()
    {
        $service = new ImportService();
        $output = $this->getMock('Symfony\Component\Console\Output\NullOutput');

        $service->importIndex(null, '', false, $output);
    }
}
