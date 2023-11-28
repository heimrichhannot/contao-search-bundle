<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle\Command;


use Contao\Automator;
use Contao\Controller;
use Contao\CoreBundle\Command\AbstractLockedCommand;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\RebuildIndex;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use HeimrichHannot\SearchBundle\Event\BeforeGetSearchablePagesEvent;
use Monolog\Logger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated Use contao:crawl instead

 */
class RebuildSearchIndexCommand extends AbstractLockedCommand
{
    const CRAWL_PAGE_PARAMETER = 'crawlpage';

    protected static $defaultName = 'huh:search:index';

    private ContaoFramework $framework;
    /**
     * @var array
     */
    private $packages;
    /**
     * @var bool
     */
    protected $dryRun = false;
    /**
     * @var Logger
     */
    private $searchLogger;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(ContaoFramework $framework, array $packages, Logger $searchLogger, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
        $this->framework = $framework;
        $this->packages = $packages;
        $this->searchLogger = $searchLogger;
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Rebuild the contao search index')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Performs a run without purging the search database.')
            ->addOption('concurrency', null, InputOption::VALUE_OPTIONAL, "Number of parallel requests", 5)
        ;
    }


    /**
     * @inheritDoc
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Rebuild search index");

        if (version_compare($this->packages['contao/core-bundle'], '4.9.0-RC1', '>=')) {
            $io->text("You are using contao version 4.9 or greater. Please use <fg=green>contao:crawl</> instead.");
            $this->searchLogger->addNotice("Rebuild search index command is not supported for contao 4.9 and above.");
            $io->newLine();
            return 0;
        }

        if (!isset($this->packages['guzzlehttp/guzzle']) || substr($this->packages['guzzlehttp/guzzle'], 0, 1) !== '6') {
            $io->error("Guzzle HTTP client (guzzlehttp/guzzle) is not installed! Please consider the readme!");
            $this->searchLogger->addError("Guzzle HTTP client (guzzlehttp/guzzle) is not installed! Please consider the readme!");
            return 1;
        }

        if ($input->hasOption('dry-run') && $input->getOption('dry-run')) {
            $this->dryRun = true;
            $io->note('Dry run enabled, search table will not be purged.');
            $io->newLine();
        }

        if ($io->isVerbose()) {
            $io->text("Initialize contao framework.");
        }
        $this->framework->initialize();

        $pages = $this->getSearchablePage($io);

        // Return if there are no pages
        if (empty($pages))
        {
            $io->error("No pages found.");
            $this->searchLogger->addError("No searchable pages found.");
            return 1;
        }

        $io->text("Found <fg=green>".count($pages)."</> pages.");

        $automator = new Automator();
        if (!$this->dryRun) {
            if ($io->isVerbose()) {
                $io->text("Purge the search tables.");
            }
            $automator->purgeSearchTables();
        }

        $client = new Client([RequestOptions::ALLOW_REDIRECTS => true]);

        $error = 0;
        $success = 0;

        $request = function ($pages) {
            foreach ($pages as $page) {
                yield new Request('GET', $page, ['User-Agent' => 'HeimrichHannotSearchBundle/2.1.1']);
            }
        };
        $io->newLine();
        $io->text("Start the search indexer:");

        $io->newLine();

        if (!$io->isVeryVerbose())
        {
            $progressBar = new ProgressBar($output, count($pages));
            $progressBar->setFormat('verbose');
        }

        $pool = new Pool($client, $request($pages), [
            'concurrency' => $input->getOption('concurrency'),
            'fulfilled' => function(Response $response, $index) use ($io, &$success, $pages, $progressBar) {
                if ($io->isVeryVerbose()) {
                    $io->text("<fg=green>".$pages[$index]."</> [".$response->getStatusCode()." ".$response->getReasonPhrase()."]");
                } else {
                    $progressBar->advance();
                }

                $success++;
            },
            'rejected' => function(RequestException $reason, $index) use ($io, &$error, $pages, $progressBar) {
                if ($io->isVeryVerbose()) {
                    $io->text("<fg=red>".$pages[$index]."</> [".$reason->getCode() ." ".$reason->getResponse()->getReasonPhrase()."]");
                } else {
                    $progressBar->advance();
                }
                $this->searchLogger->addError($pages[$index], [
                    "statuscode" => $reason->getCode(),
                    "reason" => $reason->hasResponse() ? $reason->getResponse()->getReasonPhrase() : $reason->getMessage(),
                ]);
                $error++;
            },
            'options' => [
                'query' => [static::CRAWL_PAGE_PARAMETER => 1],
            ]
        ]);

        $promise = $pool->promise();
        $promise->wait();

        if (!$io->isVeryVerbose())
        {
            $progressBar->finish();
        }

        $io->newLine();
        $io->text("Triggered search indexing for ".$success." page and failed for ".$error." pages.");
        $io->success("Finished rebuilding search index.");
    }

    public function getSearchablePage(SymfonyStyle $io)
    {
        $pages = RebuildIndex::findSearchablePages();

        if ($io->isVerbose()) {
            $io->text("Found <fg=green>".count($pages)."</> from RebuildIndex::findSearchablePages().");
            $io->text("Executing getSearchablePages hook.");
        }

        if (isset($GLOBALS['TL_HOOKS']['getSearchablePages']) && \is_array($GLOBALS['TL_HOOKS']['getSearchablePages']))
        {
            if ($io->isVeryVerbose()) {
                $io->newLine();
            }
            foreach ($GLOBALS['TL_HOOKS']['getSearchablePages'] as $callback)
            {
                /** @var BeforeGetSearchablePagesEvent $event */
                $event = $this->eventDispatcher->dispatch(
                    new BeforeGetSearchablePagesEvent($callback[0], $callback[1], $pages),
                    BeforeGetSearchablePagesEvent::NAME
                );

                $pages = $event->getPages();
                if (!$event->getExecuteHook()) {
                    if ($io->isVeryVerbose()) {
                        $io->text("Canceled executing ".$callback[0].'::'.$callback[1].'() by BeforeGetSearchablePagesEvent.');
                    }
                    continue;
                }

                if ($io->isVeryVerbose()) {
                    $io->text('Executing '.$event->getClass().'::'.$event->getMethod().'()');
                }
                try {
                    $pages = Controller::importStatic($event->getClass())->{$event->getMethod()}($pages);
                } catch (\Exception $e) {
                    $this->searchLogger->error($e->getMessage(), [
                        'getSearchablePages hook entry' => $event->getClass().'::'.$event->getMethod().'()',
                        'files' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }
            if ($io->isVeryVerbose()) {
                $io->newLine();
            }
        }

        return $pages;
    }
}