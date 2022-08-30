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
    private string $separator;

    /**
     * @var array
     */
    private array $filters;


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
    public function declaration(): array
    {
        return [
            'type' => 'custom'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromIndex($value): array
    {
        return explode($this->separator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toIndex($value): ?string
    {
        return $value ? implode($this->separator, $value) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function tokenizer(): array
    {
        return [
            'type' => 'pattern',
            'pattern' => $this->separator,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function filters(): array
    {
        return $this->filters;
    }
}
