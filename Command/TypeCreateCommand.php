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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for putting mapping into elasticsearch client.
 */
class TypeCreateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:type:create')
            ->setDescription('Puts mappings into elasticsearch client for specific manager.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Specific types to load.',
                []
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $input->getOption('manager');
        $type = $input->getOption('type');

        $connection = $this->getManager($manager)->getConnection();
        $status = $connection->createTypes($type);

        switch ($status) {
            case 0:
                $message = sprintf(
                    '<info>Manager `</info><comment>%s</comment><info>` does not contain%s type(s) information.</info>',
                    $manager,
                    empty($type) ? '' : ' `' . implode('</comment><info>`, `</info><comment>', $type) . '`'
                );
                break;
            case 1:
                $message = sprintf(
                    '<info>Created `</info><comment>%s</comment><info>` type(s) for manager named '
                    . '`</info><comment>%s</comment><info>`.</info>',
                    empty($type) ? 'all' : implode('</comment><info>`, `</info><comment>', $type),
                    $manager
                );
                break;
            case -1:
                $message = sprintf(
                    '<error>ATTENTION:</error> type(s) already loaded into `<comment>%s</comment>` manager.',
                    $manager
                );
                break;
            default:
                $message = 'Message not found.';
                break;
        }

        $output->writeln($message);

        return 0;
    }
}
