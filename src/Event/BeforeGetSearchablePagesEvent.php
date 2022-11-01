<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\SearchBundle\Event;


use Symfony\Contracts\EventDispatcher\Event;

class BeforeGetSearchablePagesEvent extends Event
{
    const NAME = 'huh.search.before_get_searchable_pages';

    /**
     * @var string
     */
    private $class;
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $pages;
    /**
     * @var bool
     */
    protected $executeHook = true;

    /**
     * BeforeGetSearchablePagesEvent constructor.
     */
    public function __construct(string $class, string $method, array $pages)
    {
        $this->class = $class;
        $this->method = $method;
        $this->pages = $pages;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param array $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return bool
     */
    public function getExecuteHook(): bool
    {
        return $this->executeHook;
    }
}