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


use HeimrichHannot\SearchBundle\Command\RebuildSearchIndexCommand;
use HeimrichHannot\SearchBundle\Indexer\PdfSearchIndexer;
use HeimrichHannot\UtilsBundle\Util\Utils;

class IndexPageListener
{
    /**
     * @var PdfSearchIndexer
     */
    protected $pdfSearchIndexer;
    /**
     * @var array
     */
    private       $bundleConfig;
    private Utils $utils;

    public function __construct(array $bundleConfig, PdfSearchIndexer $pdfSearchIndexer, Utils $utils)
    {
        $this->bundleConfig = $bundleConfig;
        $this->pdfSearchIndexer = $pdfSearchIndexer;
        $this->utils = $utils;
    }


    /**
     * @Hook("indexPage")
     */
    public function onIndexPage(string $content, array $pageData, array &$indexData): void
    {
        if (isset($this->bundleConfig['disable_search_indexer']) && true === $this->bundleConfig['disable_search_indexer']) {
            $url = $indexData['url'];
            $url = $this->utils->url()->removeQueryStringParameterToUrl(RebuildSearchIndexCommand::CRAWL_PAGE_PARAMETER, $url);
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