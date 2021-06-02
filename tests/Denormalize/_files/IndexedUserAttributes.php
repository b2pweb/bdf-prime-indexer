<?php


namespace DenormalizeTestFiles;

use Bdf\Prime\Entity\Extensions\ArrayInjector;

/**
 * Class IndexedUserAttributes
 */
class IndexedUserAttributes
{
    use ArrayInjector;

    /**
     * @var integer
     */
    public $userId;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @var string[]
     */
    public $keys;

    /**
     * @var string[]
     */
    public $values;

    /**
     * @var string[]
     */
    public $tags;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }

    /**
     * @return int
     */
    public function userId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return IndexedUserAttributes
     */
    public function setUserId(int $userId): IndexedUserAttributes
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return IndexedUserAttributes
     */
    public function setAttributes(array $attributes): IndexedUserAttributes
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return string[]
     */
    public function keys(): array
    {
        return $this->keys;
    }

    /**
     * @param string[] $keys
     * @return IndexedUserAttributes
     */
    public function setKeys(array $keys): IndexedUserAttributes
    {
        $this->keys = $keys;
        return $this;
    }

    /**
     * @return string[]
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * @param string[] $values
     * @return IndexedUserAttributes
     */
    public function setValues(array $values): IndexedUserAttributes
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return string[]
     */
    public function tags(): array
    {
        return $this->tags;
    }

    /**
     * @param string[] $tags
     * @return IndexedUserAttributes
     */
    public function setTags(array $tags): IndexedUserAttributes
    {
        $this->tags = $tags;
        return $this;
    }
}
