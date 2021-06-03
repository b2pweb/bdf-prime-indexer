<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Entity\Extensions\ArrayInjector;

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
        $this->name = (string)$name;

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