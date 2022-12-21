<?php

namespace DenormalizeTestFiles;

use Bdf\Prime\Entity\Model;

/**
 * Class UserAttributes
 */
class UserAttributes extends Model
{
    /**
     * @var int
     */
    public $userId;

    /**
     * @var array
     */
    public $attributes = [];

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}
