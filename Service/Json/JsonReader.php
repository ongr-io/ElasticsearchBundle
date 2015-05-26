<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service\Json;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\Result\Converter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Reads records one by one.
 */
class JsonReader implements \Countable, \Iterator
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var resource A file system pointer resource.
     */
    private $handle;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var string
     */
    private $currentLine;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @var bool
     */
    private $convertDocuments;

    /**
     * Constructor.
     *
     * @param Manager $manager
     * @param string  $filename
     * @param bool    $convertDocuments
     */
    public function __construct($manager, $filename, $convertDocuments = true)
    {
        $this->manager = $manager;
        $this->filename = $filename;
        $this->convertDocuments = $convertDocuments;
    }

    /**
     * Destructor. Closes file handler if open.
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            @fclose($this->handle);
        }
    }

    /**
     * Returns file handler.
     *
     * @return resource
     *
     * @throws \LogicException
     */
    protected function getFileHandler()
    {
        if ($this->handle === null) {
            $fileHandler = @fopen($this->filename, 'r');

            if ($fileHandler === false) {
                throw new \LogicException('Can not open file.');
            }

            $this->handle = $fileHandler;
        }

        return $this->handle;
    }

    /**
     * Reads metadata from file.
     *
     * @throws \InvalidArgumentException
     */
    protected function readMetadata()
    {
        if ($this->metadata !== null) {
            return;
        }

        $line = fgets($this->getFileHandler());

        if (trim($line) !== '[') {
            throw new \InvalidArgumentException('Given file does not match expected pattern.');
        }

        $line = trim(fgets($this->getFileHandler()));
        $this->metadata = json_decode(rtrim($line, ','), true);
    }

    /**
     * Reads single line from file.
     */
    protected function readLine()
    {
        $buffer = '';

        while ($buffer === '') {
            $buffer = fgets($this->getFileHandler());

            if ($buffer === false) {
                $this->currentLine = null;

                return;
            }

            $buffer = trim($buffer);
        }

        if ($buffer === ']') {
            $this->currentLine = null;

            return;
        }

        $data = json_decode(rtrim($buffer, ','), true);
        $this->currentLine = $this->convertDocument($this->getOptionsResolver()->resolve($data));
    }

    /**
     * Configures OptionResolver for resolving document metadata fields.
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function configureResolver(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['_id', '_type', '_source'])
            ->setDefaults(['_score' => null])
            ->setAllowedTypes(
                [
                    '_id' => ['integer', 'string'],
                    '_type' => 'string',
                    '_source' => 'array',
                ]
            );
    }

    /**
     * Returns parsed current line.
     *
     * @return mixed
     */
    public function current()
    {
        if ($this->currentLine === null) {
            $this->readLine();
        }

        return $this->currentLine;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->readLine();

        $this->key++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return !feof($this->getFileHandler()) && $this->currentLine;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        rewind($this->getFileHandler());
        $this->metadata = null;
        $this->readMetadata();
        $this->readLine();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $metadata = $this->getMetadata();

        if (!isset($metadata['count'])) {
            throw new \LogicException('Given file does not contain count of documents.');
        }

        return $metadata['count'];
    }

    /**
     * Returns metadata.
     *
     * @return array|null
     */
    public function getMetadata()
    {
        $this->readMetadata();

        return $this->metadata;
    }

    /**
     * @return Converter
     */
    protected function getConverter()
    {
        if (!$this->converter) {
            $this->converter = new Converter(
                $this->manager->getTypesMapping(),
                $this->manager->getBundlesMapping()
            );
        }

        return $this->converter;
    }

    /**
     * Returns configured options resolver instance.
     *
     * @return OptionsResolver
     */
    private function getOptionsResolver()
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
            $this->configureResolver($this->optionsResolver);
        }

        return $this->optionsResolver;
    }

    /**
     * Converts array to document.
     *
     * @param array $document
     *
     * @return DocumentInterface
     */
    private function convertDocument($document)
    {
        if (!$this->convertDocuments) {
            return $document;
        }

        return $this->getConverter()->convertToDocument($document);
    }
}
