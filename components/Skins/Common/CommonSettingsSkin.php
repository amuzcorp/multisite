<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Skins\Cpt\Settings\Common;

use Xpressengine\Presenter\Presenter;
use Xpressengine\Skin\AbstractSkin;
use View;
use XePresenter;

class CommonSettingsSkin extends AbstractSkin
{
    protected static $skinAlias = 'dynamic_factory/components/Skins/Cpt/Settings/Common/views';

    public function render()
    {
        $contentView = View::make(
            sprintf('%s.%s', static::$skinAlias, $this->view),
            $this->data
        );

        $parts = pathinfo($contentView->getPath());
        $names = explode('/', $parts['dirname']);
        $subPath =array_pop($names);
        $active = substr($parts['filename'], 0, stripos($parts['filename'], '.'));
        $this->data['_active'] = $active;

        if (XePresenter::getRenderType() == Presenter::RENDER_CONTENT) {
            $view = $contentView;
        } elseif($subPath === 'global' || $subPath === 'module') {
            // wrapped by _frame.blade.php
            $this->data['afea'] = 1;
            $view = View::make(sprintf('%s.%s._frame', static::$skinAlias, $subPath), $this->data);
            $view->content = $contentView->render();
        } else {
            $view = $contentView;
        }

        return $view;
    }
}
