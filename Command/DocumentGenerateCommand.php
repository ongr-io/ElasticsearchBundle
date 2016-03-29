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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;

class DocumentGenerateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:document:generate')
            ->setDescription('Generates a new Elasticsearch document inside a bundle');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasParameterOption(['--no-interaction', '-n'])) {
            throw new \InvalidArgumentException('No interaction mode is not allowed!');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Welcome to the ONGRElasticsearchBundle document generator');
        $io->writeln(
            [
                '',
                'This command helps you generate ONGRElasticsearchBundle documents.',
                '',
                'First, you need to give the document name you want to generate.',
                'You must use the shortcut notation like <comment>AcmeDemoBundle:Post</comment>.',
                '',
            ]
        );

        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $bundleNames = array_keys($kernel->getBundles());

        while (true) {
            $question = new Question('The Document shortcut name');
            $question
                ->setValidator([$this, 'validateDocumentName'])
                ->setAutocompleterValues($bundleNames);

            $document = $io->askQuestion($question);

            list($bundle, $document) = $this->parseShortcutNotation($document);

            if (in_array(strtolower($document), $this->getReservedKeywords())) {
                $io->error(sprintf('"%s" is a reserved word.', $document));
                continue;
            }

            try {
                if (!file_exists(
                    $kernel->getBundle($bundle)->getPath() . '/Document/' . str_replace('\\', '/', $document) . '.php'
                )) {
                    break;
                }

                $io->error(sprintf('Document "%s:%s" already exists.', $bundle, $document));
            } catch (\Exception $e) {
                $io->error(sprintf('Bundle "%s" does not exist.', $bundle));
            }
        }

        $question = new Question('Document type in Elasticsearch', lcfirst($document));
        $question->setValidator([$this, 'validateFieldName']);
        $documentType = $io->askQuestion($question);

        $properties = [];

        while (true) {
            $question = new Question(
                'Enter property name [<comment>No property will be added if empty!</comment>]',
                false
            );

            if (!$field = $io->askQuestion($question)) {
                break;
            }

            try {
                $this->validateFieldName($field);
            } catch (\InvalidArgumentException $e) {
                $io->error($e->getMessage());
                continue;
            }

            $question = new Question('Enter property name in Elasticsearch', $field);
            $question->setValidator([$this, 'validateFieldName']);
            $name = $io->askQuestion($question);

            $question = new Question('Enter property type', 'string');
            $question
                ->setAutocompleterValues($this->getPropertyTypes())
                ->setValidator([$this, 'validatePropertyType']);
            $type = $io->askQuestion($question);

            $question = new Question(
                'Enter property options [<comment>No options will be added if empty!</comment>]',
                false
            );

            $options = $io->askQuestion($question);

            $properties[] = [
                'annotation' => 'Property',
                'field_name' => $field,
                'property_name' => $name,
                'property_type' => $type,
                'property_options' => $options,
            ];
        }

        $this->getContainer()->get('es.generate')->generate(
            $this->getContainer()->get('kernel')->getBundle($bundle),
            $document,
            $documentType,
            $properties
        );
    }

    /**
     * Performs basic checks in document name
     *
     * @param string $document
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function validateDocumentName($document)
    {
        if (!preg_match('{^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*:[a-zA-Z0-9_\x7f-\xff\\\/]+$}', $document)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The document name isn\'t valid ("%s" given, expecting something like AcmeBlogBundle:Post)',
                    $document
                )
            );
        }

        return $document;
    }

    /**
     * Validates field name
     *
     * @param string $field
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validateFieldName($field)
    {
        if (!$field || $field != lcfirst(preg_replace('/[^a-zA-Z]+/', '', $field))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The parameter isn\'t valid ("%s" given, expecting camelcase separated words)',
                    $field
                )
            );
        }

        if (in_array(strtolower($field), $this->getReservedKeywords())) {
            throw new \InvalidArgumentException(sprintf('"%s" is a reserved word.', $field));
        }

        return $field;
    }

    /**
     * Validates property type
     *
     * @param string $type
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validatePropertyType($type)
    {
        if (!in_array($type, $this->getPropertyTypes())) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The property type isn\'t valid ("%s" given, expecting one of following: %s)',
                    $type,
                    implode(', ', $this->getPropertyTypes())
                )
            );
        }

        return $type;
    }

    /**
     * Parses shortcut notation
     *
     * @param string $shortcut
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    private function parseShortcutNotation($shortcut)
    {
        $shortcut = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($shortcut, ':')) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The document name isn\'t valid ("%s" given, expecting something like AcmeBlogBundle:Post)',
                    $shortcut
                )
            );
        }

        return [
            substr($shortcut, 0, $pos),
            substr($shortcut, $pos + 1),
        ];
    }

    /**
     * Returns available property types
     *
     * @return array
     */
    private function getPropertyTypes()
    {
        $reflection = new \ReflectionClass('ONGR\ElasticsearchBundle\Annotation\Property');

        return $this
            ->getContainer()
            ->get('annotations.cached_reader')
            ->getPropertyAnnotation($reflection->getProperty('type'), 'Doctrine\Common\Annotations\Annotation\Enum')
            ->value;
    }

    /**
     * Returns reserved keywords
     *
     * @return array
     */
    private function getReservedKeywords()
    {
        return [
            'abstract',
            'and',
            'array',
            'as',
            'break',
            'callable',
            'case',
            'catch',
            'class',
            'clone',
            'const',
            'continue',
            'declare',
            'default',
            'do',
            'else',
            'elseif',
            'enddeclare',
            'endfor',
            'endforeach',
            'endif',
            'endswitch',
            'endwhile',
            'extends',
            'final',
            'finally',
            'for',
            'foreach',
            'function',
            'global',
            'goto',
            'if',
            'implements',
            'interface',
            'instanceof',
            'insteadof',
            'namespace',
            'new',
            'or',
            'private',
            'protected',
            'public',
            'static',
            'switch',
            'throw',
            'trait',
            'try',
            'use',
            'var',
            'while',
            'xor',
            'yield',
            'die',
            'echo',
            'empty',
            'exit',
            'eval',
            'include',
            'include_once',
            'isset',
            'list',
            'require',
            'require_once',
            'return',
            'print',
            'unset',
        ];
    }
}
