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
        return $this->getContainer()->get($this->getManagerId($name));
    }

    /**
     * Returns elasticsearch connection by name.
     * 
     * @param string $name
     *
     * @return \ONGR\ElasticsearchBundle\Client\Connection
     */
    protected function getConnection($name)
    {
        return $this->getManager($this->getManagerNameByConnection($name))->getConnection();
    }

    /**
     * Returns manager name which is using passed connection.
     *
     * @param string $name Connection name.
     *
     * @return string
     * 
     * @throws \RuntimeException
     */
    private function getManagerNameByConnection($name)
    {
        foreach ($this->getContainer()->getParameter('es.managers') as $managerName => $params) {
            if ($params['connection'] === $name) {
                return $managerName;
            }
        }

        throw new \RuntimeException(
            sprintf('Connection named %s is not used by any manager. Check your configuration.', $name)
        );
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
