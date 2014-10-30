<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Command;

use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * AbstractElasticsearchCommand class.
 */
abstract class AbstractElasticsearchCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(
            'manager',
            null,
            InputOption::VALUE_REQUIRED,
            'Set connection to work with.',
            'default'
        );
    }

    /**
     * Returns elasticsearch manager by name with latest mappings.
     *
     * @param string $name
     *
     * @return Manager
     */
    protected function getManager($name)
    {
        /** @var Manager $manager */
        $manager = $this->getContainer()->get($this->getManagerId($name));

        return $manager;
    }

    /**
     * Returns connection service id.
     *
     * @param string $name
     *
     * @return string
     */
    private function getManagerId($name)
    {
        $manager = $name == 'default' || empty($name) ? 'es.manager' : sprintf('es.manager.%s', $name);

        return $manager;
    }
}
