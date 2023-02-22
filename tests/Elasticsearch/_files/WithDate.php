<?php

namespace ElasticsearchTestFiles;

use DateTimeInterface;

class WithDate
{
    public string $id;
    public string $value;
    public DateTimeInterface $date;

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return WithDate
     */
    public function setId(string $id): WithDate
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return WithDate
     */
    public function setValue(string $value): WithDate
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function date(): DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param DateTimeInterface $date
     *
     * @return WithDate
     */
    public function setDate(DateTimeInterface $date): WithDate
    {
        $this->date = $date;

        return $this;
    }
}
