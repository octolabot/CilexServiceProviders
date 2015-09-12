<?php

namespace OctoLab\Cilex\Monolog;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;

/**
 * @author Kamil Samigullin <kamil@samigullin.info>
 */
class ConfigResolver
{
    /** @var \Pimple */
    private $handlers;
    /** @var \SplObjectStorage */
    private $processors;

    /**
     * @return \Pimple
     *
     * @api
     */
    public function getHandlers()
    {
        if (null === $this->handlers) {
            $this->handlers = new \Pimple();
        }
        return $this->handlers;
    }

    /**
     * @return \SplObjectStorage
     *
     * @api
     */
    public function getProcessors()
    {
        if (null === $this->processors) {
            $this->processors = new \SplObjectStorage();
        }
        return $this->processors;
    }

    /**
     * @param array $config
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function resolve(array $config)
    {
        if (array_key_exists('handlers', $config)) {
            $this->resolveHandlers($config['handlers']);
        }
        if (array_key_exists('processors', $config)) {
            $this->resolveProcessors($config['processors']);
        }
        return $this;
    }

    /**
     * @param array $config
     *
     * @throws \InvalidArgumentException
     */
    private function resolveHandlers(array $config)
    {
        foreach ($config as $key => $handler) {
            if (array_key_exists('type', $handler)) {
                $class = $this->resolveClass($handler['type'], 'Monolog\Handler', 'Handler');
            } elseif (array_key_exists('class', $handler)) {
                $class = $handler['class'];
            } else {
                throw new \InvalidArgumentException('Handler\'s config requires either the type or class.');
            }
            $arguments = [];
            $reflection = new \ReflectionClass($class);
            if (array_key_exists('arguments', $handler)) {
                $arguments = $this->resolveArguments($handler['arguments'], $reflection);
            }
            /** @var HandlerInterface $instance */
            $instance = $reflection->newInstanceArgs($arguments);
            if (array_key_exists('formatter', $handler)) {
                $this->resolveFormatter($handler['formatter'], $instance);
            }
            $this->getHandlers()->offsetSet($key, $instance);
        }
    }

    /**
     * @param array $config
     *
     * @throws \InvalidArgumentException
     */
    private function resolveProcessors(array $config)
    {
        foreach ($config as $processor) {
            if (array_key_exists('type', $processor)) {
                $class = $this->resolveClass($processor['type'], 'Monolog\Processor', 'Processor');
            } elseif (array_key_exists('class', $processor)) {
                $class = $processor['class'];
            } else {
                throw new \InvalidArgumentException('Processor\'s config requires either the type or class.');
            }
            $arguments = [];
            $reflection = new \ReflectionClass($class);
            if (array_key_exists('arguments', $processor)) {
                $arguments = $this->resolveArguments($processor['arguments'], $reflection);
            }
            $this->getProcessors()->attach($reflection->newInstanceArgs($arguments));
        }
    }

    /**
     * @param array $formatter
     * @param HandlerInterface $handler
     *
     * @throws \InvalidArgumentException
     */
    private function resolveFormatter(array $formatter, HandlerInterface $handler)
    {
        if (array_key_exists('type', $formatter)) {
            $class = $this->resolveClass($formatter['type'], 'Monolog\Formatter', 'Formatter');
        } elseif (array_key_exists('class', $formatter)) {
            $class = $formatter['class'];
        } else {
            throw new \InvalidArgumentException('Formatter\'s config requires either the type or class.');
        }
        $arguments = [];
        $reflection = new \ReflectionClass($class);
        if (array_key_exists('arguments', $formatter)) {
            $arguments = $this->resolveArguments($formatter['arguments'], $reflection);
        }
        /** @var FormatterInterface $instance */
        $instance = $reflection->newInstanceArgs($arguments);
        $handler->setFormatter($instance);
    }

    /**
     * @param string $type
     * @param string $ns
     * @param string $postfix
     *
     * @return string
     */
    private function resolveClass($type, $ns, $postfix = null)
    {
        $parts = explode(' ', ucwords(str_replace('_', ' ', $type)));
        $class = implode('', $parts);
        return $ns . '\\' . $class . $postfix;
    }

    /**
     * @param array $arguments
     * @param \ReflectionClass $reflection
     *
     * @return array
     */
    private function resolveArguments(array $arguments, \ReflectionClass $reflection)
    {
        if (is_int(key($arguments))) {
            return $arguments;
        } else {
            $params = [];
            foreach ($reflection->getConstructor()->getParameters() as $param) {
                try {
                    $params[$param->getName()] = $param->getDefaultValue();
                } catch (\Exception $e) {
                    $params[$param->getName()] = null;
                }
            }
            return array_merge($params, array_intersect_key($arguments, $params));
        }
    }
}
