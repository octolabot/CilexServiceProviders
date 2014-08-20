<?php
/**
 * @link http://www.octolab.org/
 * @copyright Copyright (c) 2013 OctoLab
 * @license http://www.octolab.org/license
 */

namespace OctoLab\Cilex\Provider;

use Cilex\Application;
use Cilex\ServiceProviderInterface;
use OctoLab\Cilex\Config\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * @author Kamil Samigullin <kamil@samigullin.info>
 *
 * @see \Cilex\Provider\ConfigServiceProvider
 */
class ConfigServiceProvider implements ServiceProviderInterface
{
    /** @var string */
    protected $filename;
    /** @var array */
    protected $placeholders;

    /**
     * @param string $filename
     * @param array $placeholders
     */
    public function __construct($filename, array $placeholders = [])
    {
        $this->filename = $filename;
        $this->placeholders = $placeholders;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $file = $this->filename;
        $placeholders = $this->placeholders;
        $app['config'] = $app->share(function () use ($app, $file, $placeholders) {
            // TODO возможно не лучшее решение -> посмотреть в сторону $app
            $arrayMergeRecursive = function (array $base, array $mixtures) use (&$arrayMergeRecursive) {
                foreach ($mixtures as $key => $value) {
                    if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                        $base[$key] = $arrayMergeRecursive($base[$key], $value);
                    } else {
                        $base[$key] = $value;
                    }
                }
                return $base;
            };
            $loader = new YamlFileLoader(new FileLocator());
            switch (true) {
                case $loader->supports($file):
                    $loader->load($file);
                    break;
                default:
                    throw new \RuntimeException(sprintf('File "%s" is not supported.', $file));
            }
            $config = [];
            foreach (array_reverse($loader->getContent()) as $data) {
                $config = $arrayMergeRecursive($config, $data);
            }
            if (isset($config['parameters'])) {
                $placeholders = array_merge($config['parameters'], $placeholders);
            }
            array_walk_recursive($config, function (&$param) use ($placeholders) {
                if (preg_match('/^%(.+)%$/', $param, $matches)) {
                    $placeholder = $matches[1];
                    if (isset($placeholders[$placeholder])) {
                        $param = $placeholders[$placeholder];
                    }
                }
            });
            unset($config['parameters'], $config['imports']);
            return $config;
        });
    }
}
