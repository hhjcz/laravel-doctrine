<?php namespace Tests\MultipleEntityManagersAndConnections;

use Illuminate\Container\Container;
use Mitch\LaravelDoctrine\LaravelDoctrineServiceProvider;
use Mitch\LaravelDoctrine\IlluminateRegistry;
use Mitch\LaravelDoctrine\CacheManager;
use Mitch\LaravelDoctrine\Cache\ApcProvider;
use Mitch\LaravelDoctrine\Cache\MemcacheProvider;
use Mitch\LaravelDoctrine\Cache\RedisProvider;
use Mitch\LaravelDoctrine\Cache\XcacheProvider;
use Mitch\LaravelDoctrine\Cache\NullProvider;
use Mitch\LaravelDoctrine\Configuration\DriverMapper;
use Mitch\LaravelDoctrine\Configuration\SqlMapper;
use Mitch\LaravelDoctrine\Configuration\SqliteMapper;

abstract class AbstractDatabaseMappingTest extends \PHPUnit_Framework_TestCase
{
    protected $sp;
    protected $containerStub;
    protected $containerInstances = array();

    protected function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    public function make($abstract, $parameters = array())
    {
        if (isset($this->containerInstances[$abstract])) {
            return $this->containerInstances[$abstract];
        }
    }

    public function instance($abstract, $instance)
    {
        $this->containerInstances[$abstract] = $instance;
    }

    protected function createCacheManager($cacheConfig)
    {
        $manager = new CacheManager($cacheConfig);
        $manager->add(new ApcProvider);
        $manager->add(new MemcacheProvider);
        $manager->add(new RedisProvider);
        $manager->add(new XcacheProvider);
        $manager->add(new NullProvider);

        return $manager;
    }

    protected function getLaravelDBConfig()
    {
        return [
            'default' => 'mysql',

            'connections' => array(
                'sqlite' => array(
                    'driver'   => 'sqlite',
                    'database' => ':memory:',
                    'prefix'   => '',
                ),
                'mysql' => array(
                    'driver'    => 'mysql',
                    'host'      => 'localhost',
                    'database'  => 'laravel',
                    'username'  => 'root',
                    'password'  => 'root',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ),
                'pgsql' => array(
                    'driver'   => 'pgsql',
                    'host'     => 'localhost',
                    'database' => 'database',
                    'username' => 'root',
                    'password' => '',
                    'charset'  => 'utf8',
                    'prefix'   => '',
                    'schema'   => 'public',
                ),
                'sqlsrv' => array(
                    'driver'   => 'sqlsrv',
                    'host'     => 'localhost',
                    'database' => 'database',
                    'username' => 'root',
                    'password' => '',
                    'prefix'   => '',
                ),
            ),
        ];
    }

    protected function getBasicDoctrineConfiguration()
    {
        return [
            'simple_annotations' => false,

            'metadata' => [
                __DIR__.'/Models',
            ],

            'proxy' => [
                'auto_generate' => false,
                'directory'     => null,
                'namespace'     => null
            ],

            // Available: null, apc, xcache, redis, memcache
            'cache_provider' => null,

            'cache' => [
                'redis' => [
                    'host'     => '127.0.0.1',
                    'port'     => 6379,
                    'database' => 1
                ],
                'memcache' => [
                    'host' => '127.0.0.1',
                    'port' => 11211
                ]
            ],

            'repository' => 'Doctrine\ORM\EntityRepository',

            'repositoryFactory' => null,

            'logger' => null
        ];
    }

    public function setUp()
    {
        $mapper = new DriverMapper;
        $mapper->registerMapper(new SqlMapper);
        $mapper->registerMapper(new SqliteMapper);

        $this->containerInstances[DriverMapper::class] = $mapper;

        $containerStub = $this->getMock('\Illuminate\Container\Container');
        $containerStub->expects($this->any())
            ->method('make')
            ->will($this->returnCallback([$this,'make']));
        $containerStub->expects($this->any())
            ->method('instance')
            ->will($this->returnCallback([$this,'instance']));
        $this->sp = new LaravelDoctrineServiceProvider($containerStub);
        $this->containerStub = $containerStub;
    }
}
