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

use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads records one by one.
 *
 * Sample input:
 * <p>
 * [
 * {"count":2},
 * {"_id":"doc1","title":"Document 1"},
 * {"_id":"doc2","title":"Document 2"}
 * ]
 * </p>
 */
class JsonReader implements \Countable, \Iterator
{
    private $filename;
    private $handle;
    private $key = 0;
    private $currentLine;
    private $metadata;
    private $index;
    private $optionsResolver;
    private $options;

    public function __construct(IndexService $index, string $filename, array $options = [])
    {
        $this->index = $index;
        $this->filename = $filename;
        $this->options = $options;
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

    public function getIndex(): IndexService
    {
        return $this->index;
    }

    protected function getFileHandler()
    {
        //Make sure the gzip option is resolved from a filename.
        if ($this->handle === null) {
            $isGzip = array_key_exists('gzip', $this->options);

            $filename = !$isGzip?
                $this->filename:
                sprintf('compress.zlib://%s', $this->filename);
            $fileHandler = @fopen($filename, 'r');

            if ($fileHandler === false) {
                throw new \LogicException('Can not open file.');
            }

            $this->handle = $fileHandler;
        }

        return $this->handle;
    }

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
        $this->currentLine = $this->getOptionsResolver()->resolve($data);
    }

    protected function configureResolver(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['_id', '_source'])
            ->setDefaults(['_score' => null, 'fields' => []])
            ->addAllowedTypes('_id', ['integer', 'string'])
            ->addAllowedTypes('_source', 'array')
            ->addAllowedTypes('fields', 'array');
    }

    public function current()
    {
        if ($this->currentLine === null) {
            $this->readLine();
        }

        return $this->currentLine;
    }

    public function next()
    {
        $this->readLine();

        $this->key++;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return !feof($this->getFileHandler()) && $this->currentLine;
    }

    public function rewind()
    {
        rewind($this->getFileHandler());
        $this->metadata = null;
        $this->readMetadata();
        $this->readLine();
    }

    public function count()
    {
        $metadata = $this->getMetadata();

        if (!isset($metadata['count'])) {
            throw new \LogicException('Given file does not contain count of documents.');
        }

        return $metadata['count'];
    }

    public function getMetadata()
    {
        $this->readMetadata();

        return $this->metadata;
    }

    private function getOptionsResolver(): OptionsResolver
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
            $this->configureResolver($this->optionsResolver);
        }

        return $this->optionsResolver;
    }
}
