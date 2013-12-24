<?php

namespace Patbzh\BetaseriesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Patrick Coustans <patrick.coustans@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('patbzh_betaseries');

        $rootNode
            ->children()
                ->scalarNode('http_client_timeout')
                    ->defaultValue(5)
                    ->end()
                ->scalarNode('betaseries_key')
                    ->isRequired()
                    ->end()
                ->scalarNode('betaseries_api_version')
                    ->defaultValue(2.2)
                    ->end()
                ->scalarNode('betaseries_default_oauth_key')
                    ->end()
                ->scalarNode('betaseries_default_oauth_user_token')
                    ->end()
                ->scalarNode('betaseries_user_agent')
		    ->defaultValue('betaseries_patbzh_sf2_bundle')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

