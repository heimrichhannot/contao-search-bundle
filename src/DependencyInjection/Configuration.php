<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('huh_search');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('pdf_indexer')
                    ->info("Configure the pdf indexer.")
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->info("Enable pdf indexing for search.")->end()
                        ->integerNode('max_indexed_characters')->min(0)->defaultValue(2000)->info("Max characters to process and store from a pdf file. 0 means no limit.")->end()
                        ->integerNode('max_file_size')->min(0)->defaultValue(8096)->info("Maximum file size of a pdf that can be processed by the pdf parser to prevent memory overflow or process timeout. Specify in KiB. 0 means no limit. 1024KiB = 1MB.")->end()

                    ->end()
                ->end()
                ->booleanNode('enable_search_filter')->defaultTrue()->info("Enable or disable search filter for search module")->end()
                ->booleanNode('disable_max_keyword_filter')->defaultFalse()->info("Enable or disable max keyword filter for search module")->end()
                ->booleanNode('enable_search_log')->defaultFalse()->info("Enable a search keyword logging.")->end()
                ->scalarNode('valid_word_chars')->defaultValue('ÄäÖöÜüẞß')->info("Set additional chars that should be not break a word (used for charlist parameter of str_word_count function).")->end()


            ->end()
        ;
        return $treeBuilder;
    }


}