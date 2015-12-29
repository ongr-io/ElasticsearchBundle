<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service;

use ONGR\ElasticsearchBundle\Service\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testConstructorException().
     *
     * @return array
     */
    public function getTestConstructorExceptionData()
    {
        return [
            [
                12345,
                '\InvalidArgumentException',
                'must be a string',
            ],
            [
                'Non\Existing\ClassName',
                '\InvalidArgumentException',
                'non-existing class',
            ],
        ];
    }

    /**
     * @param $className
     * @param $expectedException
     * @param $expectedExceptionMessage
     *
     * @dataProvider getTestConstructorExceptionData()
     */
    public function testConstructorException($className, $expectedException, $expectedExceptionMessage)
    {
        $this->setExpectedException($expectedException, $expectedExceptionMessage);

        new Repository(null, $className);
    }
}
