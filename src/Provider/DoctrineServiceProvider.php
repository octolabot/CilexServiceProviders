<?php
/**
 * @link http://www.octolab.org/
 * @copyright Copyright (c) 2013 OctoLab
 * @license http://www.octolab.org/license
 */

namespace OctoLab\Cilex\Provider;

use Cilex\Application;
use Cilex\ServiceProviderInterface;

/**
 * @author Kamil Samigullin <kamil@samigullin.info>
 *
 * @see \Cilex\Provider\DoctrineServiceProvider
 * @todo roadmap:
 * @todo - config namespace support [doctrine \ dbal \ connections, default_connection]
 */
class DoctrineServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
    }
}
