<?php

namespace DenormalizeTestFiles;

use Bdf\Prime\Mapper\Mapper;

class UserAttributesMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connexion' => 'test',
            'table' => 'user_attr',
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->integer('userId')->primary()
            ->json('attributes')
        ;
    }
}
