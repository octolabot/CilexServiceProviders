<?php

namespace OctoLab\Cilex\ServiceProvider;

use Cilex\Application;
use Cilex\ServiceProviderInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use OctoLab\Common\Doctrine\Util\ConfigResolver;

/**
 * @author Kamil Samigullin <kamil@samigullin.info>
 */
class DoctrineServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function register(Application $app)
    {
        $config = $app->offsetGet('config');
        if (!isset($config['doctrine'])) {
            return;
        }
        ConfigResolver::resolve($config['doctrine:dbal']);
        $app['connections'] = $app::share(function () use ($app, $config) {
            $connections = new \Pimple();
            foreach ($config['doctrine:dbal:connections'] as $id => $params) {
                $connections->offsetSet(
                    $id,
                    DriverManager::getConnection($params, new Configuration(), new EventManager())
                );
            }
            return $connections;
        });
        $app['connection'] = $app::share(function () use ($app, $config) {
            $ids = $app['connections']->keys();
            $default = $config['doctrine:dbal:default_connection'] ?: current($ids);
            return $app['connections'][$default];
        });
        $app
            ->offsetGet('console')
            ->getHelperSet()
            ->set(new ConnectionHelper($app->offsetGet('connection')), 'connection')
        ;
    }
}
