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

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\Kernel;

class DocumentGenerateCommand extends AbstractManagerAwareCommand
{
    /**
     * @var QuestionHelper
     */
    private $questionHelper;

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
            throw $this->getException('No interaction mode is not allowed!');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');
        $this->questionHelper = new QuestionHelper();

        $output->writeln(
            [
                '',
                $formatter->formatBlock('Welcome to the ONGRElasticsearchBundle document generator', 'bg=blue', true),
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
            $document = $this->questionHelper->ask(
                $input,
                $output,
                $this->getQuestion('The Document shortcut name', null, [$this, 'validateDocumentName'], $bundleNames)
            );

            list($bundle, $document) = $this->parseShortcutNotation($document);

            if (in_array(strtolower($document), $this->getReservedKeywords())) {
                $output->writeln(
                    $formatter->formatBlock(sprintf('"%s" is a reserved word.', $document), 'bg=red', true)
                );
                continue;
            }

            try {
                if (!file_exists(
                    $kernel->getBundle($bundle)->getPath() . '/Document/' . str_replace('\\', '/', $document) . '.php'
                )) {
                    break;
                }

                $output->writeln(
                    $formatter->formatBlock(
                        sprintf('Document "%s:%s" already exists.', $bundle, $document),
                        'bg=red',
                        true
                    )
                );
            } catch (\Exception $e) {
                $output->writeln(
                    $formatter->formatBlock(sprintf('Bundle "%s" does not exist.', $bundle), 'bg=red', true)
                );
            }
        }

        $output->writeln($this->getOptionsLabel($this->getDocumentAnnotations(), 'Available types'));
        $annotation = $this->questionHelper->ask(
            $input,
            $output,
            $this->getQuestion(
                'Document type',
                'Document',
                [$this, 'validateDocumentAnnotation'],
                $this->getDocumentAnnotations()
            )
        );

        $documentType = lcfirst($document);
        if ($annotation == 'Document') {
            $documentType = $this->questionHelper->ask(
                $input,
                $output,
                $this->getQuestion(
                    "\n" . 'Elasticsearch Document name',
                    lcfirst($document),
                    [$this, 'validateFieldName']
                )
            );
        }

        $properties = [];
        $output->writeln(['', $formatter->formatBlock('New Document Property', 'bg=blue;fg=white', true)]);

        while (true) {
            $question = $this->getQuestion(
                'Property name [<comment>press <info><return></info> to stop</comment>]',
                false
            );

            if (!$field = $this->questionHelper->ask($input, $output, $question)) {
                break;
            }

            try {
                $this->validateFieldName($field);
            } catch (\InvalidArgumentException $e) {
                $output->writeln($e->getMessage());
                continue;
            }

            $output->writeln($this->getOptionsLabel($this->getPropertyAnnotations(), 'Available annotations'));
            $property['annotation'] = $this->questionHelper->ask(
                $input,
                $output,
                $this->getQuestion(
                    'Property annotation',
                    'Property',
                    [$this, 'validatePropertyAnnotation'],
                    $this->getPropertyAnnotations()
                )
            );

            $property['field_name'] = $property['property_name'] = $field;

            switch ($property['annotation']) {
                case 'Embedded':
                    $property['property_name'] = $this->askForPropertyName($input, $output, $property['field_name']);
                    $property['property_class'] = $this->askForPropertyClass($input, $output);

                    $question = new ConfirmationQuestion("\n<info>Multiple</info> [<comment>no</comment>]: ", false);
                    $question->setAutocompleterValues(['yes', 'no']);
                    $property['property_multiple'] = $this->questionHelper->ask($input, $output, $question);

                    $property['property_options'] = $this->askForPropertyOptions($input, $output);
                    break;
                case 'ParentDocument':
                    $property['property_class'] = $this->askForPropertyClass($input, $output);
                    break;
                case 'Property':
                    $property['property_name'] = $this->askForPropertyName($input, $output, $property['field_name']);

                    $output->writeln($this->getOptionsLabel($this->getPropertyTypes(), 'Available types'));
                    $property['property_type'] = $this->questionHelper->ask(
                        $input,
                        $output,
                        $this->getQuestion(
                            'Property type',
                            'string',
                            [$this, 'validatePropertyType'],
                            $this->getPropertyTypes()
                        )
                    );

                    $property['property_options'] = $this->askForPropertyOptions($input, $output);
                    break;
                case 'Ttl':
                    $property['property_default'] = $this->questionHelper->ask(
                        $input,
                        $output,
                        $this->getQuestion("\n" . 'Default time to live')
                    );
                    break;
            }

            $properties[] = $property;
            $output->writeln(['', $formatter->formatBlock('New Document Property', 'bg=blue;fg=white', true)]);
        }

        $this->getContainer()->get('es.generate')->generate(
            $this->getContainer()->get('kernel')->getBundle($bundle),
            $document,
            $annotation,
            $documentType,
            $properties
        );
    }

    /**
     * Asks for property name
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param mixed           $default
     *
     * @return string
     */
    private function askForPropertyName(InputInterface $input, OutputInterface $output, $default = null)
    {
        return $this->questionHelper->ask(
            $input,
            $output,
            $this->getQuestion("\n" . 'Property name in Elasticsearch', $default, [$this, 'validateFieldName'])
        );
    }

    /**
     * Asks for property options
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string
     */
    private function askForPropertyOptions(InputInterface $input, OutputInterface $output)
    {
        return $this->questionHelper->ask(
            $input,
            $output,
            $this->getQuestion(
                "\n" . 'Property options [<comment>press <info><return></info> to stop</comment>]',
                false,
                null,
                ['"index"="not_analyzed"', '"analyzer"="standard"']
            )
        );
    }

    /**
     * Asks for property class
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string
     */
    private function askForPropertyClass(InputInterface $input, OutputInterface $output)
    {
        return $this->questionHelper->ask(
            $input,
            $output,
            $this->getQuestion(
                "\n" . 'Property class',
                null,
                [$this, 'validatePropertyClass'],
                $this->getDocumentClasses()
            )
        );
    }

    /**
     * Returns available document classes
     *
     * @return array
     */
    private function getDocumentClasses()
    {
        /** @var MetadataCollector $metadataCollector */
        $metadataCollector = $this->getContainer()->get('es.metadata_collector');
        $classes = [];

        foreach ($this->getContainer()->getParameter('es.managers') as $manager) {
            $documents = $metadataCollector->getMappings($manager['mappings']);
            foreach ($documents as $document) {
                $classes[] = sprintf('%s:%s', $document['bundle'], $document['class']);
            }
        }

        return $classes;
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
            throw $this->getException(
                'The document name isn\'t valid ("%s" given, expecting something like AcmeBundle:Post)',
                [$shortcut]
            );
        }

        return [substr($shortcut, 0, $pos), substr($shortcut, $pos + 1)];
    }

    /**
     * Validates property class
     *
     * @param string $class
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validatePropertyClass($class)
    {
        if (!in_array($class, $this->getDocumentClasses())) {
            throw $this->getException(
                'The document isn\'t available ("%s" given, expecting one of following: %s)',
                [$class, $this->getDocumentClasses()]
            );
        }

        return $class;
    }

    /**
     * Performs basic checks in document name
     *
     * @param string $document
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validateDocumentName($document)
    {
        if (!preg_match('{^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*:[a-zA-Z0-9_\x7f-\xff\\\/]+$}', $document)) {
            throw $this->getException(
                'The document name isn\'t valid ("%s" given, expecting something like AcmeBundle:Post)',
                [$document]
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
            throw $this->getException(
                'The parameter isn\'t valid ("%s" given, expecting camelcase separated words)',
                [$field]
            );
        }

        if (in_array(strtolower($field), $this->getReservedKeywords())) {
            throw $this->getException('"%s" is a reserved word.', [$field]);
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
            throw $this->getException(
                'The property type isn\'t valid ("%s" given, expecting one of following: %s)',
                [$type, implode(', ', $this->getPropertyTypes())]
            );
        }

        return $type;
    }

    /**
     * Validates document annotation
     *
     * @param string $annotation
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validateDocumentAnnotation($annotation)
    {
        if (!in_array($annotation, $this->getDocumentAnnotations())) {
            throw $this->getException(
                'The document annotation isn\'t valid ("%s" given, expecting one of following: %s)',
                [$annotation, implode(', ', $this->getDocumentAnnotations())]
            );
        }

        return $annotation;
    }

    /**
     * Validates property annotation
     *
     * @param string $annotation
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validatePropertyAnnotation($annotation)
    {
        if (!in_array($annotation, $this->getPropertyAnnotations())) {
            throw $this->getException(
                'The property annotation isn\'t valid ("%s" given, expecting one of following: %s)',
                [$annotation, implode(', ', $this->getPropertyAnnotations())]
            );
        }

        return $annotation;
    }

    /**
     * Returns formatted question
     *
     * @param string        $question
     * @param null|string   $default
     * @param callable|null $validator
     * @param array|null    $values
     *
     * @return Question
     */
    private function getQuestion($question, $default = null, callable $validator = null, array $values = null)
    {
        $question = new Question(
            sprintf('<info>%s</info>%s: ', $question, $default ? sprintf(' [<comment>%s</comment>]', $default) : ''),
            $default
        );

        $question
            ->setValidator($validator)
            ->setAutocompleterValues($values);

        return $question;
    }

    /**
     * Returns options label
     *
     * @param array  $options
     * @param string $suffix
     *
     * @return array
     */
    private function getOptionsLabel(array $options, $suffix)
    {
        $label = sprintf('<info>%s:</info> ', $suffix);

        foreach ($options as &$option) {
            $option = sprintf('<comment>%s</comment>', $option);
        }

        return ['', $label . implode(', ', $options) . '.'];
    }

    /**
     * Returns formatted exception
     *
     * @param string $format
     * @param array  $args
     *
     * @return \InvalidArgumentException
     */
    private function getException($format, $args = [])
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');
        return new \InvalidArgumentException($formatter->formatBlock(vsprintf($format, $args), 'bg=red', true));
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
     * Returns property annotations
     *
     * @return array
     */
    private function getPropertyAnnotations()
    {
        return ['Embedded', 'Id', 'ParentDocument', 'Property', 'Ttl'];
    }

    /**
     * Returns document annotations
     *
     * @return array
     */
    private function getDocumentAnnotations()
    {
        return ['Document', 'Nested', 'Object'];
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
