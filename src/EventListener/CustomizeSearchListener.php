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
use Contao\Database;
use Contao\Module;
use Contao\ModuleSearch;
use Contao\StringUtil;
use Monolog\Logger;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook("customizeSearch")]
class CustomizeSearchListener
{
    protected bool $enableFilterSearch = false;
    private bool $disableMaxKeywordFilter = false;
    protected string $validWordChars = '';
    protected bool $enableSearchLog = false;


    /**
     * CustomizeSearchListener constructor.
     * @param array $bundleConfig
     */
    public function __construct(
        array                       $bundleConfig,
        private TranslatorInterface $translator,
        private Logger              $searchLogLogger
    )
    {
        if (isset($bundleConfig['enable_search_filter']) && true === $bundleConfig['enable_search_filter']) {
            $this->enableFilterSearch = true;
        }
        if (isset($bundleConfig['disable_max_keyword_filter']) && true === $bundleConfig['disable_max_keyword_filter']) {
            $this->disableMaxKeywordFilter = true;
        }
        if (isset($bundleConfig['valid_word_chars']) && !empty($bundleConfig['valid_word_chars'])) {
            $this->validWordChars = $bundleConfig['valid_word_chars'];
        }
        if (isset($bundleConfig['enable_search_log']) && true === $bundleConfig['enable_search_log']) {
            $this->enableSearchLog = true;
        }
    }

    /**
     * @param array $pageIds
     * @param string $keywords
     * @param string $queryType
     * @param bool $fuzzy
     * @param ModuleSearch|Module $module
     */
    public function __invoke(array &$pageIds, string &$keywords, string $queryType, bool $fuzzy, Module $module): void
    {
        if ($this->enableSearchLog) {
            $this->logSearch($keywords);
        }
        if ($this->enableFilterSearch) {
            $this->filterSearch($pageIds, $module);
        }
        if (!$this->disableMaxKeywordFilter) {
            $this->filterInput($keywords, $module);
        }
    }

    protected function logSearch(string $keywords)
    {
        $this->searchLogLogger->info($keywords);
    }

    protected function filterSearch(array &$pageIds, Module $module)
    {
        $filterPages = StringUtil::deserialize($module->filterPages, true);
        if (!empty($filterPages) && $module->pageMode) {
            if ($module->addPageDepth) {
                $filterPages = array_merge($filterPages, Database::getInstance()->getChildRecords($filterPages, 'tl_page'));
            }

            switch ($module->pageMode) {
                case 'include':
                    $pageIds = $filterPages;
                    break;
                case 'exclude':
                    $pageIds = array_diff($pageIds, $filterPages);
                    break;
            }
        }
    }

    protected function filterInput(string &$keywords, Module $module)
    {
        if ($module->maxKeywordCount < 1) {
            return;
        }
        $words = str_word_count($keywords, 2, $this->validWordChars);
        if (is_array($words) && count($words) > $module->maxKeywordCount) {
            $indexes = array_keys($words);
            $keywords = substr($keywords, 0, ($indexes[$module->maxKeywordCount] - 1));
            $module->Template->maxKeywordsExceededMessage = $this->translator->trans('huh_search.module.max_keywords_exceeded_message', [
                "%max%" => $module->maxKeywordCount,
                "%count%" => count($words),
            ]);
        }
    }
}