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
use HeimrichHannot\SearchBundle\Command\RebuildSearchIndexCommand;
use HeimrichHannot\SearchBundle\Indexer\PdfSearchIndexer;
use HeimrichHannot\UtilsBundle\Util\Utils;

/**
 * @Hook("indexPage")
 */
class IndexPageListener
{
    protected PdfSearchIndexer $pdfSearchIndexer;
    private array $bundleConfig;
    private Utils $utils;

    public function __construct(array $bundleConfig, PdfSearchIndexer $pdfSearchIndexer, Utils $utils)
    {
        $this->bundleConfig = $bundleConfig;
        $this->pdfSearchIndexer = $pdfSearchIndexer;
        $this->utils = $utils;
    }

    public function __invoke(string $content, array $pageData, array &$indexData): void
    {
        if (true === ($this->bundleConfig['disable_search_indexer'] ?? false)) {
            trigger_deprecation("heimrichhannot/contao-search-bundle", "2.9.0", "Using huh_search.disable_search_indexer is deprecated and will be removed in next major version. Please use the contao core settings instead.");

            $url = $indexData['url'];
            $url = $this->utils->url()->removeQueryStringParameterFromUrl(RebuildSearchIndexCommand::CRAWL_PAGE_PARAMETER, $url);
            $indexData['url'] = $url;
        }
        if (isset($this->bundleConfig['pdf_indexer']['enabled']) && true === $this->bundleConfig['pdf_indexer']['enabled']) {
            if (str_ends_with($pageData['url'], '.pdf')) {
                $indexData['fileHash'] = $pageData['fileHash'];
            } else {
                if (preg_match_all('/href="(?<links>[^\"<]+\.pdf[^"]*)"/i', $content, $matches))
                {
                    $this->pdfSearchIndexer->indexPdfFiles($matches['links'], $indexData);
                }
            }
        }
    }
}