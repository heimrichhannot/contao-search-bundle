<?php

namespace HeimrichHannot\SearchBundle\Controller\ContentElement;

use Contao\ContentHyperlink;
use Contao\ContentModel;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Input;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ContentElement(RelatedSearchLinkElementController::TYPE, category="links", template="ce_related_search_link")
 */
class RelatedSearchLinkElementController extends ContentHyperlink
{
    public const TYPE = 'related_search_link';

    private Utils $utils;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(Utils $utils) {
        $this->utils = $utils;
    }

    public function __invoke(ContentModel $model, string $section): Response
    {
        parent::__construct($model, $section);

        return new Response($this->generate());
    }

    protected function compile()
    {
        parent::compile();
        $query = '';
        $parameter = Input::get('keywords', false, true);
        if (!empty($parameter)) {
            $query .= 'keywords='.$parameter;
        }
        $parameter = Input::get('query_type', false, true);
        if (!empty($parameter)) {
            $query .= '&query_type='.$parameter;
        }
        $this->Template->href = $this->utils->url()->addQueryStringParameterToUrl($query, $this->url);
    }

    
}