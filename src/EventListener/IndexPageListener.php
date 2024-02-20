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


use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use HeimrichHannot\SearchBundle\Indexer\PdfSearchIndexer;

#[AsHook("indexPage")]
class IndexPageListener
{
    public function __construct(private array $bundleConfig, protected PdfSearchIndexer $pdfSearchIndexer)
    {
    }

    public function __invoke(string $content, array $pageData, array &$indexData): void
    {
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