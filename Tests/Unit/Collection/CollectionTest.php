<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Collection;

use Doctrine\Common\Collections\ArrayCollection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $data = [
        'foo' => 'Bob 1',
        'bar' => 'Bob 2',
    ];

    /**
     * Tests \Countable implementation.
     */
    public function testCountable()
    {
        $this->assertCount(count($this->data), new ArrayCollection($this->data));
    }

    /**
     * Tests \Iterator implementation.
     */
    public function testIterator()
    {
        $this->assertEquals($this->data, iterator_to_array(new ArrayCollection($this->data)));
    }

    /**
     * Tests \ArrayAccess implementation.
     */
    public function testArrayAccess()
    {
        $collection = new ArrayCollection($this->data);

        $this->assertArrayHasKey('foo', $collection);
        $this->assertEquals($this->data['foo'], $collection['foo']);

        $newData = $this->data;
        $newData['baz'] = 'Bob 3';
        $newData[] = 'Bob 4';
        $collection['baz'] = 'Bob 3';
        $collection[] = 'Bob 4';

        $this->assertEquals($newData, iterator_to_array($collection));

        unset($collection['baz']);
        $this->assertArrayNotHasKey('baz', $collection);
    }
}
