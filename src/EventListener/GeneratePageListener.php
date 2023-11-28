<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle\EventListener;


use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use HeimrichHannot\SearchBundle\Command\RebuildSearchIndexCommand;

/**
 * @Hook("generatePage")
 */
class GeneratePageListener
{
    private array $bundleConfig;

    public function __construct(array $bundleConfig)
    {
        $this->bundleConfig = $bundleConfig;
    }

    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        if (true === ($this->bundleConfig['disable_search_indexer'] ?? false)) {
            trigger_deprecation("heimrichhannot/contao-search-bundle", "2.9.0", "Using huh_search.disable_search_indexer is deprecated and will be removed in next major version. Please use the contao core settings instead.");
            if (Input::get(RebuildSearchIndexCommand::CRAWL_PAGE_PARAMETER) !== '1') {
                $pageModel->noSearch = '1;';
            }
        }
    }
}