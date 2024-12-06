<?php

/**
 * Contao Open Source CMS.
 *
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\SearchBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Doctrine\DBAL\Types\Types;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    protected bool $filterSearch = false;
    private bool $disableMaxKeywordFilter = false;

    public function __construct(array $bundleConfig)
    {
        if (isset($bundleConfig['enable_search_filter']) && true === $bundleConfig['enable_search_filter']) {
            $this->filterSearch = true;
        }
        if (isset($bundleConfig['disable_max_keyword_filter']) && true === $bundleConfig['disable_max_keyword_filter']) {
            $this->disableMaxKeywordFilter = true;
        }
    }

    public function __invoke(string $table): void
    {
        if ('tl_module' !== $table) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_module'];

        if ($this->filterSearch) {
            PaletteManipulator::create()
                ->addLegend('search_filter_legend', 'redirect_legend')
                ->addField(['pageMode', 'filterPages', 'addPageDepth'], 'search_filter_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette('search', 'tl_module');

            $dca['fields']['pageMode'] = [
                'exclude' => true,
                'inputType' => 'radio',
                'options' => ['exclude', 'include'],
                'default' => 'exclude',
                'reference' => &$GLOBALS['TL_LANG']['tl_module']['pageMode'],
                'eval' => [
                    'tl_class' => 'w50',
                ],
                'sql' => "varchar(8) NOT NULL default 'exclude'",
            ];

            $dca['fields']['filterPages'] = [
                'exclude' => true,
                'inputType' => 'pageTree',
                'foreignKey' => 'tl_page.title',
                'eval' => [
                    'multiple' => true,
                    'fieldType' => 'checkbox',
                    'isSortable' => true,
                    'tl_class' => 'clr',
                ],
                'load_callback' => [['tl_module', 'setPagesFlags']],
                'sql' => 'blob NULL',
                'relation' => [
                    'type' => 'hasMany',
                    'load' => 'lazy',
                ],
            ];

            $dca['fields']['addPageDepth'] = [
                'exclude' => true,
                'inputType' => 'checkbox',
                'default' => true,
                'eval' => [
                    'tl_class' => 'w50 clr',
                ],
                'sql' => "char(1) NOT NULL default '1'",
            ];
        }

        if (!$this->disableMaxKeywordFilter) {
            PaletteManipulator::create()
                ->addField('maxKeywordCount', 'fuzzy')
                ->applyToPalette('search', 'tl_module');

            $dca['fields']['maxKeywordCount'] = [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => [
                    'rgxp' => 'digit',
                    'tl_class' => 'clr w50',
                    'maxval' => 128,
                ],
                'sql' => [
                    'type' => Types::SMALLINT,
                    'notnull' => true,
                    'unsigned' => true,
                    'default' => 0,
                ],
            ];
        }
    }
}
