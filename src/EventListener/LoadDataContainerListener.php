<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle\EventListener;


use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Doctrine\DBAL\Types\Types;

#[AsHook("loadDataContainer")]
class LoadDataContainerListener
{
    protected bool $filterSearch = false;
    private bool $disableMaxKeywordFilter = false;

    public function __construct(array $bundleConfig)
    {
        if (isset($bundleConfig['enable_search_filter']) && true === $bundleConfig['enable_search_filter'])
        {
            $this->filterSearch = true;
        }
        if (isset($bundleConfig['disable_max_keyword_filter']) && true === $bundleConfig['disable_max_keyword_filter'])
        {
            $this->disableMaxKeywordFilter = true;
        }

    }

    /**
     * @param string $table
     */
    public function __invoke(string $table)
    {
        if ('tl_module' !== $table) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_module'];

        if ($this->filterSearch)
        {
            $dca['palettes']['search'] = str_replace('{redirect_legend', '{search_filter_legend},pageMode,filterPages,addPageDepth;{redirect_legend', $dca['palettes']['search']);

            $fields        = [
                'pageMode'     => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_module']['pageMode'],
                    'exclude'   => true,
                    'inputType' => 'radio',
                    'options'   => ['exclude', 'include'],
                    'default'   => 'exclude',
                    'reference' => &$GLOBALS['TL_LANG']['tl_module']['pageMode'],
                    'eval'      => ['tl_class' => 'w50'],
                    'sql'       => "varchar(8) NOT NULL default 'exclude'",
                ],
                'filterPages'  => array_merge_recursive([
                    'label'     => &$GLOBALS['TL_LANG']['tl_module']['filterPages'],
                    'eval'      => ['tl_class' => 'clr'],
                ], $dca['fields']['pages']),
                'addPageDepth' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_module']['addPageDepth'],
                    'exclude'   => true,
                    'inputType' => 'checkbox',
                    'default'   => true,
                    'eval'      => ['tl_class' => 'w50 clr'],
                    'sql'       => "char(1) NOT NULL default '1'",
                ]
            ];

            $dca['fields'] = array_merge($fields, is_array($dca['fields']) ? $dca['fields'] : []);

            unset($dca['fields']['filterPages']['eval']['mandatory']);
        }

        if (!$this->disableMaxKeywordFilter) {
            $dca['palettes']['search'] = str_replace(',fuzzy', ',maxKeywordCount,fuzzy', $dca['palettes']['search']);

            $fields = [
                'maxKeywordCount' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_module']['maxKeywordCount'],
                    'exclude'   => true,
                    'inputType' => 'text',
                    'eval'      => [
                        'rgxp' => 'digit',
                        'tl_class' => 'clr w50',
                        'maxval' => 128,
                    ],
                    'sql'       => [
                        'type' => Types::SMALLINT,
                        'notnull' => true,
                        'unsigned' => true,
                        'default' => 0
                    ]
                ]
            ];
            $dca['fields'] = array_merge($fields, is_array($dca['fields']) ? $dca['fields'] : []);
        }
    }
}
