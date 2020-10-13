<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle\Indexer;


use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\File;
use Contao\Frontend;
use Contao\Search;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use HeimrichHannot\UtilsBundle\String\StringUtil as HuhStringUtil;

class PdfSearchIndexer
{
    /**
     * @var ContaoFramework
     */
    protected $framework;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var HuhStringUtil
     */
    protected $stringUtil;
    /**
     * @var array
     */
    protected $bundleConfig;

    /**
     * PdfSearchIndexer constructor.
     */
    public function __construct(ContaoFramework $framework, Connection $connection, array $bundleConfig)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->bundleConfig = $bundleConfig;
    }

    public function indexPdfFiles(array $links, $arrParentSet)
    {
        foreach ($links as $strFile) {
            if (($strFile = static::getValidPath($strFile, [Environment::get('host')])) === null) {
                continue;
            }

            $this->addToPDFSearchIndex($strFile, $arrParentSet);
        }
    }

    public function getValidPath($varValue, array $arrHosts = [])
    {
        $arrUrl = parse_url($varValue);

        $strFile = $arrUrl['path'];

        // linked pdf is an valid absolute url
        if (isset($arrUrl['scheme']) && in_array($arrUrl['scheme'], ['http', 'https'])) {
            if (isset($arrUrl['host']) && !in_array($arrUrl['host'], $arrHosts)) {
                $strFile = null;
            }
        }

        // check for download link
        if (isset($arrUrl['query']) && preg_match('#file=(?<path>.*.pdf)#i', $arrUrl['query'], $m)) {
            $strFile = $m['path'];
        }


        // check if file exists
        if ($strFile !== null) {
            $strFile = ltrim(urldecode($strFile), '/');

            if (!file_exists(TL_ROOT . '/' . $strFile)) {
                $strFile = null;
            }
        }

        return $strFile;
    }

    protected function addToPDFSearchIndex($strFile, $arrParentSet): void
    {
        $objFile = new File($strFile);

        if (!$this->isValidPDF($objFile)) {
            return;
        }

        $objModel = $objFile->getModel();

        $arrMeta = Frontend::getMetaData($objModel->meta, $arrParentSet['language']);

        // Use the file name as title if none is given
        if ($arrMeta['title'] == '') {
            $arrMeta['title'] = StringUtil::specialchars($objFile->basename);
        }

        $strHref = Environment::get('base') . Environment::get('request');

        // Remove an existing file parameter
        if (preg_match('/(&(amp;)?|\?)file=/', $strHref)) {
            $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
        }

        $strHref .= ((Config::get('disableAlias') || strpos($strHref, '?') !== false) ? '&amp;' : '?') . 'file=' . System::urlEncode($objFile->value);

        $filesize = round($objFile->size / 1024, 2);

        $arrSet = [
            'pid'       => $arrParentSet['pid'],
            'tstamp'    => time(),
            'title'     => $arrMeta['title'],
            'url'       => $strHref,
            'filesize'  => $filesize,
            'fileHash'  => $objFile->hash,
            'protected' => $arrParentSet['protected'],
            'groups'    => $arrParentSet['groups'],
            'language'  => $arrParentSet['language'],
        ];

        $stmt = $this->connection->executeQuery("SELECT * FROM tl_search WHERE pid=? AND fileHash=?", [$arrSet['pid'], $arrSet['fileHash']]);
        if ($stmt->rowCount() > 0) {
            return;
        }

        if (isset($this->bundleConfig['pdf_indexer']['max_file_size']) && $this->bundleConfig['pdf_indexer']['max_file_size'] > 0 && $this->bundleConfig['pdf_indexer']['max_file_size'] > $arrSet['filesize']) {
            return;
        }

        try {
            // parse only for the first occurrence
            $parser     = new \Smalot\PdfParser\Parser();
            $objPDF     = $parser->parseFile($strFile);
            $strContent = $objPDF->getText();

        } catch (\Exception $e) {
            // Missing object refernce #...
            return;
        }

        // Put everything together
        $strContent = trim(preg_replace('/ +/', ' ', StringUtil::decodeEntities($strContent)));

        // save only first 2000 characters for performance reasons
        $maxCharacters = 2000;
        if (isset($this->bundleConfig['pdf_indexer']['max_indexed_characters']) && $this->bundleConfig['pdf_indexer']['max_indexed_characters'] > 0) {
            $maxCharacters = $this->bundleConfig['pdf_indexer']['max_indexed_characters'];
        }
        $arrSet['content'] = substr($strContent, 0, $maxCharacters);


        $this->framework->initialize();

        /** @var Search $search */
        $search = $this->framework->getAdapter(Search::class);

        try {
            $search->indexPage($arrSet);
        } catch (\Throwable $t) {
            throw new \Exception("Could not add a search index entry: ".$t->getMessage());
        }
    }

    /**
     * @param File $objFile
     * @return bool
     */
    public function isValidPDF($objFile)
    {
        if($objFile->mime != $GLOBALS['TL_MIME']['pdf'][0])
        {
            return false;
        }

        return true;
    }
}