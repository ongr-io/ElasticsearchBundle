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

use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\DSL\Query\TermQuery;
use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\CdnObject;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\ColorDocument;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Comment;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\CompletionSuggesting;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\PriceLocationSuggesting;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\PriceLocationContext;
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\UrlObject;

/**
 * Functional tests for orm manager.
 */
class ManagerTest extends AbstractElasticsearchTestCase
{
    /**
     * @var Converter
     */
    private $converter;

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

        // Update links, as object.
        $url3 = new UrlObject();
        $url3->url = 'test_url3';
        $actualProduct->links[] = $url3;
        $manager->persist($actualProduct);
        $manager->commit();

        $actualProduct = $repository->execute($repository->createSearch())[0];
        $this->assertEquals(3, count($actualProduct->links));
        $this->assertEquals('test_url3', $actualProduct->links[2]->url);

        // Update links, as array.
        $actualProduct->links[] = ['url' => 'test_url4'];
        $manager->persist($actualProduct);
        $manager->commit();

        $actualProduct = $repository->execute($repository->createSearch())[0];
        $this->assertEquals(4, count($actualProduct->links));
        $this->assertEquals('test_url4', $actualProduct->links[3]->url);

        // Update links, existing using foreach.
        foreach ($actualProduct->links as $link) {
            if ($link->url === 'test_url2') {
                $link->url = 'updated_test_url2';
            }
        }

        $manager->persist($actualProduct);
        $manager->commit();

        $actualProduct = $repository->execute($repository->createSearch())[0];
        $this->assertEquals(4, count($actualProduct->links));
        $this->assertEquals('updated_test_url2', $actualProduct->links[1]->url);
    }

    /**
     * Test if exception is thrown on read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Bulk operation not permitted.
     */
    public function testPersistReadOnlyManager()
    {
        $manager = $this->getContainer()->get('es.manager.readonly');

        $product = new Product();
        $product->title = 'test';

        $manager->persist($product);
        $manager->commit();
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

        $repository = $manager->getRepository('AcmeTestBundle:Product');
        $product = $repository->createDocument();
        $product->contextSuggesting = $suggester;
        $product->completionSuggesting = $completionSuggester;

        $manager->persist($product);
        $manager->commit();

        /** @var Product[] $actualProduct */
        $actualProducts = $repository->execute($repository->createSearch());
        $this->assertCount(1, $actualProducts);

        /** @var Product $actualProduct */
        $actualProduct = $actualProducts->current();
        $actualProduct->setId(null);
        $actualProduct->setScore(null);

        $this->assertEquals($this->convertToArray($product), $this->convertToArray($actualProduct));
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
        $results = $repository->execute($repository->createSearch());
        /** @var DocumentInterface $actualProduct */
        $actualProduct = $results[0];

        $this->assertGreaterThan(time(), $actualProduct->getCreatedAt()->getTimestamp());
    }

    /**
     * Check if `token_count` field works as expected.
     */
    public function testPersistTokenCountField()
    {
        $manager = $this->getManager();
        $colorDocument = new ColorDocument();
        $colorDocument->piecesCount = 't e s t';
        $manager->persist($colorDocument);
        $manager->commit();
        $repository = $manager->getRepository('AcmeTestBundle:ColorDocument');

        // Analyzer is whitespace, so there are four tokens.
        $search = new Search();
        $search->addQuery(new TermQuery('pieces_count.count', '4'));
        $this->assertEquals(1, $repository->execute($search)->getTotalCount());

        // Test with invalid count.
        $search = new Search();
        $search->addQuery(new TermQuery('pieces_count.count', '6'));
        $this->assertEquals(0, $repository->execute($search)->getTotalCount());
    }

    /**
     * Tests cloning documents.
     */
    public function testCloningDocuments()
    {
        $manager = $this->getManager();

        $document = new Product();
        $document->setId('tuna_id');
        $document->title = 'tuna';

        $manager->persist($document);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Product');
        $document = $repository->find('tuna_id');
        $clone = clone $document;

        $this->assertNull($clone->getId(), 'Id should be null\'ed.');
        $manager->persist($clone);
        $manager->commit();

        $search = $repository
            ->createSearch()
            ->addQuery(new TermQuery('title', 'tuna'));

        $this->assertCount(2, $repository->execute($search), '2 Results should be found.');
    }

    /**
     * Converts document to array.
     *
     * @param DocumentInterface $document
     *
     * @return array
     */
    private function convertToArray($document)
    {
        $manager = $this->getManager();

        if (!$this->converter) {
            $this->converter = new Converter($manager->getTypesMapping(), $manager->getBundlesMapping());
        }

        return $this->converter->convertToArray($document);
    }
}
