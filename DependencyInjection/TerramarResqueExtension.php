<?php

namespace Terramar\Bundle\ResqueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class TerramarResqueExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('terramar.resque.class', $config['class']);
        $container->setParameter('terramar.resque.redis.host', $config['redis']['host']);
        $container->setParameter('terramar.resque.redis.port', $config['redis']['port']);
        $container->setParameter('terramar.resque.redis.database', $config['redis']['database']);

        if(!empty($config['prefix'])) {
            $container->setParameter('terramar.resque.prefix', $config['prefix']);
            $container->getDefinition('terramar.resque')->addMethodCall('setPrefix', array($config['prefix']));
        }
    }

    public function getAlias()
    {
        return 'resque';
    }
}
