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
 * Command to update mapping.
 */
class MappingUpdateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:mapping:update')
            ->setDescription('Updates elasticsearch index mappings.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'This is mandatory parameter to execute this command.'
            )
            ->addOption(
                'types',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set this parameter to update only a specific document types. ' .
                'The syntax of definition is with bundle: AcmeBundle:SomeDocument. ' .
                'If no value is provided, it will update all mapping provided in the manager mapping.',
                []
            )
            ->addOption(
                'enable-warnings',
                'w',
                InputOption::VALUE_NONE,
                'By setting this option you will enable elasticsearch merge conflicts warnings. '.
                'It will add `ignore_conflicts` with false option in the client call.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $managerName = $input->getOption('manager');
            $types = $input->getOption('types');

            // Doing bool value reverse.
            $ignoreConflicts = $input->getOption('enable-warnings') ? false : true;

            $this
                ->getManager($input->getOption('manager'))
                ->updateMapping($types, $ignoreConflicts);

            $typesOutput = empty($types) ? 'All' : implode('</comment><info>`, `</info><comment>', $types);

            $output->writeln(
                sprintf(
                    '<info>`</info><comment>%s</comment><info>` document(s) type(s) have been updated for the '
                    . '`</info><comment>%s</comment><info> manager`.</info>',
                    $typesOutput,
                    $managerName
                )
            );
        } else {
            $output->writeln('<error>ATTENTION:</error> This action should not be used in the production environment.');
            $output->writeln('<error>"Option --force is mandatory to change type(s) mapping."</error> ');
        }
    }
}
