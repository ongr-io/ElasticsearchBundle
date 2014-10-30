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
use ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product;
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
        $product->links = [$url, $url2];

        $manager->persist($product);
        $manager->commit();
        $manager->flush();
        $manager->refresh();

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

        /** @var CdnObject[] $actualCdn */
        $actualCdn = iterator_to_array($actualUrl[0]->cdn);
        $this->assertEquals(1, count($actualCdn));
        $this->assertEquals($cdn->cdn_url, $actualCdn[0]->cdn_url);
    }

    /**
     * Check if special fields are set as expected.
     */
    public function testPersistSpecialFields()
    {
        /** @var Manager $manager */
        $manager = $this->getManager();

        $product = new Comment();
        $product->setId('testId');
        $product->setTtl(500000);
        $product->setScore('1.0');
        $product->setParent('parentId');
        $product->userName = 'testUser';

        $manager->persist($product);
        $manager->commit();

        $repository = $manager->getRepository('AcmeTestBundle:Comment');
        $search = $repository->createSearch();
        $results = $repository->execute($search);
        /** @var DocumentInterface $actualProduct */
        $actualProduct = $results[0];

        $this->assertEquals($product->getId(), $actualProduct->getId());
        $this->assertEquals($product->getParent(), $actualProduct->getParent());
        $this->assertLessThan($product->getTtl(), $actualProduct->getTtl());
    }
}
