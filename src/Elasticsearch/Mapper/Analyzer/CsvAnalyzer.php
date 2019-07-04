<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer;

/**
 * Analyzer for CSV array values
 */
final class CsvAnalyzer implements AnalyzerInterface
{
    /**
     * @var string
     */
    private $separator;

    /**
     * @var array
     */
    private $filters;


    /**
     * CsvAnalyzer constructor.
     *
     * @param string $separator The elements separator character
     * @param array $filters Filters to apply (only for declaration)
     */
    public function __construct(string $separator = ',', array $filters = [])
    {
        $this->separator = $separator;
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration()
    {
        return [
            'type' => 'custom'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromIndex($value)
    {
        return explode($this->separator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toIndex($value)
    {
        return $value ? implode($this->separator, $value) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function tokenizer()
    {
        return [
            'type' => 'pattern',
            'pattern' => $this->separator,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function filters()
    {
        return $this->filters;
    }
}
