<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\DSL\Suggester;

use Ongr\ElasticsearchBundle\DSL\Suggester\Completion;
use Ongr\ElasticsearchBundle\DSL\Suggester\Context;
use Ongr\ElasticsearchBundle\DSL\Suggester\Phrase;
use Ongr\ElasticsearchBundle\Test\EncapsulationTestAwareTrait;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    use EncapsulationTestAwareTrait;

    /**
     * Tests toArray method when $this->getSize() !== null.
     */
    public function testToArrayNotNull()
    {
        $name = 'testName';

        $context = new Context('', '', $name);
        $context->setSize(123);
        $context->setContext(new Phrase('', ''));

        $result = $context->toArray();

        $this->assertArrayHasKey($name, $result);

        $data = $result[$name];
        $this->assertArrayHasKey('completion', $data);

        $completion = $data['completion'];
        $this->assertArrayHasKey('size', $completion);

        $this->assertEquals($completion['size'], 123);
    }

    /**
     * Returns list of fields to test. Works as data provider.
     *
     * @return array
     */
    public function getFieldsData()
    {
        return [
            ['context'],
            ['size'],
        ];
    }

    /**
     * Returns entity class name.
     *
     * @return string
     */
    public function getClassName()
    {
        $this->setStub(new Context('foo', 'bar'));

        return 'Ongr\ElasticsearchBundle\DSL\Suggester\Context';
    }
}
