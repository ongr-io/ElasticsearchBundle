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

use Ongr\ElasticsearchBundle\DSL\Suggester\Phrase;
use Ongr\ElasticsearchBundle\Test\EncapsulationTestAwareTrait;

class PhraseTest extends \PHPUnit_Framework_TestCase
{
    use EncapsulationTestAwareTrait;

    /**
     * @return array
     */
    public function getTestToArrayData()
    {
        $out = [];

        // Case #0: simple.
        $phrase0 = new Phrase('body', 'lorem ipsum');
        $expected0 = [
            'body-phrase' => [
                'text' => 'lorem ipsum',
                'phrase' => ['field' => 'body'],
            ],
        ];

        $out[] = [
            $expected0,
            $phrase0,
        ];

        // Case #1: using all fields.
        $phrase1 = new Phrase('description', 'awesome cat');
        $phrase1->setMaxErrors(2);
        $phrase1->setGramSize(1);
        $phrase1->setRealWordErrorLikelihood(0.95);
        $phrase1->setHighlight(['pre_tag' => '<span class="info">', 'post_tag' => '</span>']);
        $phrase1->setAnalyzer('simple');
        $phrase1->setConfidence(1);
        $phrase1->setSize(6);

        $highlightObject = new \stdClass();
        $highlightObject->post_tag = '</span>';
        $highlightObject->pre_tag = '<span class="info">';

        $expected1 = [
            'description-phrase' => [
                'text' => 'awesome cat',
                'phrase' => [
                    'analyzer' => 'simple',
                    'field' => 'description',
                    'size' => 6,
                    'real_word_error_likelihood' => 0.95,
                    'max_errors' => 2.0,
                    'gram_size' => 1,
                    'highlight' => $highlightObject,
                ],
            ],
        ];

        $out[] = [
            $expected1,
            $phrase1,
        ];

        return $out;
    }

    /**
     * Tests toArray method.
     *
     * @param array  $expected
     * @param Phrase $phrase
     *
     * @dataProvider getTestToArrayData
     */
    public function testToArray($expected, $phrase)
    {
        $this->assertEquals($expected, $phrase->toArray());
    }

    /**
     * Tests toArray method exception.
     *
     * @expectedException \LogicException
     */
    public function testToArrayException()
    {
        $phrase = new Phrase('', '');
        $phrase->toArray();
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        $this->setStub(new Phrase('foo', 'bar'));

        return 'Ongr\ElasticsearchBundle\DSL\Suggester\Phrase';
    }

    /**
     * Returns list of fields to test. Works as data provider.
     *
     * @return array
     */
    public function getFieldsData()
    {
        return [
            ['analyzer'],
            ['gramSize'],
            ['realWordErrorLikelihood'],
            ['confidence'],
            ['maxErrors'],
            ['highlight'],
            ['size'],
        ];
    }
}
