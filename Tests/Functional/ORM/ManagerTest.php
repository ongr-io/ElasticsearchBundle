<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\ORM;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\CdnObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Comment;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\CompletionSuggesting;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Order;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\PriceLocationSuggesting;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\PriceLocationContext;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\UrlObject;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends ElasticsearchTestCase
{
    /**
     * Check if persisted objects are flushed.
     */
    public function testPersist()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        // CDN for first url.
        $cdn = new CdnObject();
        $cdn->cdn_url = 'test_cd';

        // New urls.
        $url = new UrlObject();
        $url->url = 'test_url';
        $url->cdn = $cdn;
        $url2 = new UrlObject();
        $url2->url = 'test_url2';

        // Multiple urls.
        $product = new Product();
        $product->title = 'test';
        $product->links = [
            $url,
            $url2,
        ];

        $manager->persist($product);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Product');
        /** @var Product[] $actualProduct */
        $actualProducts = $repository->execute($repository->createSearch());
        $this->assertCount(1, $actualProducts);

        /** @var Product $actualProduct */
        $actualProduct = $actualProducts->current();
        $this->assertEquals($product->title, $actualProduct->title);

        /** @var UrlObject[] $actualUrl */
        $actualUrl = iterator_to_array($actualProduct->links);
        $this->assertEquals(2, count($actualUrl));
        $this->assertEquals($url->url, $actualUrl[0]->url);
        $this->assertEquals($url2->url, $actualUrl[1]->url);

        $this->assertEquals($cdn->cdn_url, $actualUrl[0]->cdn->cdn_url);
    }

    /**
     * Check if per persist event was executed.
     */
    public function testPreCreateEvent()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        $order = new Order();
        $order->name = 'foo name';

        $manager->persist($order);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Order');
        /** @var Order[] $actualOrders */
        $actualOrders = $repository->execute($repository->createSearch());

        /** @var Order $actualOrder */
        $actualOrder = $actualOrders->current();
        $this->assertEquals('fooPrePersistEvent', $actualOrder->name);
    }

    /**
     * Check if indexed suggest fields are stored as expected.
     */
    public function testPersistSuggesters()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        $categoryContext = new PriceLocationContext();
        $categoryContext->price = '500';
        $categoryContext->location = ['lat' => 50, 'lon' => 50];
        $suggester = new PriceLocationSuggesting();
        $suggester->setInput(['test']);
        $suggester->setOutput('success');
        $suggester->setContext($categoryContext);
        $suggester->setPayload(['test']);
        $suggester->setWeight(50);

        $completionSuggester = new CompletionSuggesting();
        $completionSuggester->setInput(['a', 'b', 'c']);
        $completionSuggester->setOutput('completion success');
        $completionSuggester->setWeight(30);

        $product = new Product();
        $product->contextSuggesting = $suggester;
        $product->completionSuggesting = $completionSuggester;

        $manager->persist($product);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Product');
        /** @var Product[] $actualProduct */
        $actualProducts = $repository->execute($repository->createSearch());
        $this->assertCount(1, $actualProducts);

        /** @var Product $actualProduct */
        $actualProduct = $actualProducts->current();
        $actualProduct->setId(null);
        $actualProduct->setScore(null);

        $this->assertEquals($product, $actualProduct);
    }

    /**
     * Data provider for testPersistExceptions().
     *
     * @return array
     */
    public function getPersistExceptionsData()
    {
        $out = [];

        // Case #0: multiple cdns are put into url object, although it isn't a multiple field.
        $url = new UrlObject();
        $url->cdn = [new CdnObject(), new CdnObject()];

        $product = new Product();
        $product->links = [$url];
        $out[] = [$product, 'Expected variable of type object, got array.'];

        // Case #1: a single link is set, although field is set to multiple.
        $product = new Product();
        $product->links = new UrlObject();
        $out[] = [$product, "Variable isn't traversable, although field is set to multiple."];

        // Case #2: invalid type of object is set in multiple field.
        $product = new Product();
        $product->links = new \ArrayIterator([new UrlObject(), new CdnObject()]);
        $out[] = [
            $product,
            'Expected object of type ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\UrlObject, ' .
            'got ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\CdnObject.',
        ];

        // Case #3: invalid type of object is set in single field.
        $url = new UrlObject();
        $url->cdn = new UrlObject();

        $product = new Product();
        $product->links = [$url];
        $out[] = [
            $product,
            'Expected object of type ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\CdnObject, ' .
            'got ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\UrlObject.',
        ];

        return $out;
    }

    /**
     * Check if expected exceptions are thrown while trying to persisnt an invalid object.
     *
     * @param Product $product
     * @param string  $exceptionMessage
     * @param string  $exception
     *
     * @dataProvider getPersistExceptionsData()
     */
    public function testPersistExceptions(Product $product, $exceptionMessage, $exception = 'InvalidArgumentException')
    {
        $this->setExpectedException($exception, $exceptionMessage);

        /** @var Manager $manager */
        $manager = $this->getManager();
        $manager->persist($product);
        $manager->commit();
    }

    /**
     * Check if special fields are set as expected.
     */
    public function testPersistSpecialFields()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        $comment = new Comment();
        $comment->setId('testId');
        $comment->setTtl(500000);
        $comment->setScore('1.0');
        $comment->setParent('parentId');
        $comment->userName = 'testUser';

        $manager->persist($comment);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Comment');
        $search = $repository->createSearch();
        $results = $repository->execute($search);
        /** @var DocumentInterface $actualProduct */
        $actualProduct = $results[0];

        $this->assertEquals($comment->getId(), $actualProduct->getId());
        $this->assertEquals($comment->getParent(), $actualProduct->getParent());
        $this->assertLessThan($comment->getTtl(), $actualProduct->getTtl());
    }

    /**
     * Tests if DateTime object is being parsed.
     */
    public function testPersistDateField()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        $comment = new Comment();
        $comment->setId('testId');
        $comment->setParent('parentId');
        $comment->setCreatedAt(new \DateTime('2100-01-02 03:04:05.889342'));

        $manager->persist($comment);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Comment');
        $search = $repository->createSearch();
        $results = $repository->execute($search);
        /** @var DocumentInterface $actualProduct */
        $actualProduct = $results[0];

        $this->assertGreaterThan(time(), $actualProduct->getCreatedAt()->getTimestamp());
    }
}
