<?php

namespace DenormalizeTestFiles;

use Bdf\Prime\Mapper\Mapper;

class UserAttributesMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connexion' => 'test',
            'table' => 'user_attr',
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('userId')->primary()
            ->json('attributes')
        ;
    }
}
