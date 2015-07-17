<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Document;

use ONGR\ElasticsearchBundle\Document\Suggestions;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Suggester;

/**
 * Class SuggesterTest.
 */
class SuggesterTest extends AbstractElasticsearchTestCase
{
    /**
     * Tests if documents with completion and context data can be added and retrieved from ES.
     */
    public function testAddingAndGettingCompletion()
    {
        $completion = new Suggestions();
        $completion->setInput(['title', 'data']);
        $completion->setOutput(['title 1']);
        $completion->setPayload(['id' => 1]);
        $completion->setWeight(1);

        $context = new Suggestions();
        $context->setInput(['title', 'data2']);
        $context->setOutput(['title 1']);
        $context->setPayload(['id' => 1, 'data' => 2]);
        $context->setWeight(1);
        $context->addContext('title', 'title 1');
        $context->addContext('location', [1, 2]);

        $suggester = new Suggester();
        $suggester->setId(1);
        $suggester->setTitle('title 1');
        $suggester->setCompletion($completion);
        $suggester->setContext($context);

        $manager = $this->getManager();
        $manager->persist($suggester);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Suggester');
        $result = $repository->find(1);

        $this->assertEquals($suggester, $result);
    }
}
