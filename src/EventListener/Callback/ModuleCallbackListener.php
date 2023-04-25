<?php

namespace HeimrichHannot\SearchBundle\EventListener\Callback;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Folder;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ModuleCallbackListener
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @Callback(table="tl_module", target="fields.filterPages.save")
     * @Callback(table="tl_module", target="fields.addPageDepth.save")
     * @Callback(table="tl_module", target="fields.pageMode.save")
     */
    public function onSaveFilterPagesCallback($value, DataContainer $dc = null)
    {
        if (!$dc || !$dc->id) {
            return $value;
        }

        $moduleModel = ModuleModel::findByPk($dc->id);
        if (!$moduleModel || ('search' !== $moduleModel->type)) {
            return $value;
        }

        if ($value !== $moduleModel->{$dc->field}) {
            try {
                $folder = new Folder(StringUtil::stripRootDir($this->parameterBag->get('kernel.cache_dir')).'/contao/search');
            } catch (\Exception $e) {
                return $value;
            }

            $folder->delete();
        }

        return $value;
    }
}