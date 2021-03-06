<?php

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\IndexTestCase;

class User
{
    use ArrayInjector;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $roles = [];


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
     * @return string
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email): User
    {
        $this->email = (string) $email;

        return $this;
    }

    /**
     * @return string
     */
    public function password(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password): User
    {
        $this->password = (string) $password;

        return $this;
    }

    /**
     * @return array
     */
    public function roles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;

        return $this;
    }
}

class UserIndex implements ElasticsearchIndexConfigurationInterface
{
    public function entity(): string
    {
        return User::class;
    }

    public function index(): string
    {
        return 'test_users';
    }

    public function type(): string
    {
        return IndexTestCase::minimalElasticsearchVersion('7.0') ? '' : 'user';
    }

    public function id(): ?PropertyAccessorInterface
    {
        return null;
    }

    public function properties(PropertiesBuilder $builder): void
    {
        if (IndexTestCase::minimalElasticsearchVersion('5.0')) {
            $builder
                ->text('name')
                ->text('email')
                ->keyword('login')->accessor('email')->disableIndexing()
                ->keyword('password')->disableIndexing()
                ->text('roles')->analyzer('csv')
            ;
        } else {
            $builder
                ->string('name')
                ->string('email')
                ->string('login')->accessor('email')->notAnalyzed()
                ->string('password')->notAnalyzed()
                ->string('roles')->analyzer('csv')
            ;
        }
    }

    public function analyzers(): array
    {
        return [
            'csv' => new CsvAnalyzer()
        ];
    }

    public function scopes(): array
    {
        return [];
    }
}

class UserMapper extends \Bdf\Prime\Mapper\Mapper
{
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'user'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->string('name')
            ->string('email')->primary()
            ->string('password')
            ->simpleArray('roles')
        ;
    }
}
