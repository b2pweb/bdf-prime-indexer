<?php

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class WithAnonAnalyzer
{
    use ArrayInjector;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $values;


    /**
     * User constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->import($data);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name): User
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @return array
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     */
    public function setValues(array $values): WithAnonAnalyzer
    {
        $this->values = $values;
        return $this;
    }
}

class WithAnonAnalyzerIndex implements ElasticsearchIndexConfigurationInterface
{
    public function entity(): string
    {
        return WithAnonAnalyzer::class;
    }

    public function index(): string
    {
        return 'test_anon_analyzers';
    }

    public function type(): string
    {
        return 'anon_analyzer';
    }

    public function id(): ?PropertyAccessorInterface
    {
        return null;
    }

    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->string('name')
            ->string('values')->analyzer(new CsvAnalyzer(';'))
        ;
    }

    public function analyzers(): array
    {
        return [];
    }

    public function scopes(): array
    {
        return [];
    }
}
