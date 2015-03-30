<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\DSL\Suggester\Context;

use Ongr\ElasticsearchBundle\DSL\Suggester\Context\CategoryContext;
use Ongr\ElasticsearchBundle\Test\EncapsulationTestAwareTrait;

class CategoryContextTest extends \PHPUnit_Framework_TestCase
{
    use EncapsulationTestAwareTrait;

    /**
     * @return string
     */
    public function getClassName()
    {
        $this->setStub(new CategoryContext('foo', 'bar'));

        return 'Ongr\ElasticsearchBundle\DSL\Suggester\Context\CategoryContext';
    }

    /**
     * @return array
     */
    public function getFieldsData()
    {
        return [
            ['name'],
            ['value'],
        ];
    }
}
