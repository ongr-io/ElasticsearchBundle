<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * AppKernel class.
 */
class AppKernel extends Kernel
{
    /**
     * Register bundles.
     *
     * @return array
     */
    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
            new ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\AcmeTestBundle(),
            new ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\FooBundle\AcmeFooBundle(),
        ];
    }

    /**
     * Register container configuration.
     *
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * Only check configuration difference on first test case. The rest test cases might be the same ;).
     *
     * @throws Exception
     */
    protected function initializeContainer()
    {
        static $first = true;

        if ('test' !== $this->getEnvironment()) {
            parent::initializeContainer();
            return;
        }

        $debug = $this->debug;

        if (!$first) {
            // disable debug mode on all but the first initialization
            $this->debug = false;
        }

        // will not work with --process-isolation
        $first = false;

        try {
            parent::initializeContainer();
        } catch (\Exception $e) {
            $this->debug = $debug;
            throw $e;
        }

        $this->debug = $debug;
    }
}
