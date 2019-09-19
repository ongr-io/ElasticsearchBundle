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

use ONGR\ElasticsearchBundle\Service\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AbstractElasticsearchCommand class.
 */
abstract class AbstractManagerAwareCommand extends Command
{

    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @return ContainerInterface
     *
     * @throws \LogicException
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $application = $this->getApplication();
            if (null === $application) {
                throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
            }

            $this->container = $application->getKernel()->getContainer();
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(
            'manager',
            'm',
            InputOption::VALUE_REQUIRED,
            'Manager name',
            'default'
        );
    }

    /**
     * Returns elasticsearch manager by name from service container.
     *
     * @param string $name Manager name defined in configuration.
     *
     * @return Manager
     *
     * @throws \RuntimeException If manager was not found.
     */
    protected function getManager($name)
    {
        $id = $this->getManagerId($name);

        if ($this->getContainer()->has($id)) {
            return $this->getContainer()->get($id);
        }

        throw new \RuntimeException(
            sprintf(
                'Manager named `%s` not found. Available: `%s`.',
                $name,
                implode('`, `', array_keys($this->getContainer()->getParameter('es.managers')))
            )
        );
    }

    /**
     * Formats manager service id from its name.
     *
     * @param string $name Manager name.
     *
     * @return string Service id.
     */
    private function getManagerId($name)
    {
        return sprintf('es.manager.%s', $name);
    }
}
