<?php

namespace Sensio\Bundle\BuzzBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * PatbzhBetaseriesExtension
 *
 * @author Patrick Coustans <patrick.coustans@gmail.com>
 */
class PatbzhBetaseriesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

/*
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('buzz.xml');
        $container->setParameter('buzz.client.timeout', $config['client_timeout']);
*/

        $container->setParameter('patbzh.betaseries.http_client_timeout', $config['http_client_timeout']);
        $container->setParameter('patbzh.betaseries.key', $config['betaseries_key']);
        $container->setParameter('patbzh.betaseries.api_version', $config['betaseries_api_version']);
        $container->setParameter('patbzh.betaseries.default_oauth_key', $config['betaseries_default_oauth_key']);
        $container->setParameter('patbzh.betaseries.user_agent', $config['betaseries_user_agent']);
    }
}

