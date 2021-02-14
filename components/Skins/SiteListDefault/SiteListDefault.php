<?php
namespace Amuz\XePlugin\Multisite\Components\Skins\SiteListDefault;

use Xpressengine\Skin\GenericSkin;
use View;
use Gate;
use XeFrontend;
use XeRegister;
use XePresenter;
Use XeSkin;
use Xpressengine\Presenter\Presenter;

class SiteListDefault extends GenericSkin
{
    protected static $path = 'multisite/components/Skins/SiteListDefault';

    /**
     * render
     *
     * @return \Illuminate\Contracts\Support\Renderable|string
     */
    public function render()
    {
        // set skin path
        $this->data['_skinPath'] = static::$path;
        $this->data['isManager'] = $this->isManager();

        /**
         * If view file is not exists to extended skin component then change view path to CommonSkin's path.
         * CommonSkin extends by other Skins. Extended Skin can make own blade files.
         * If not make blade file then use to CommonSkin's blade files.
         */
        if (View::exists(sprintf('%s/views/%s', static::$path, $this->view)) == false) {
            static::$path = self::$path;
        }

        $contentView = parent::render();

        /**
         * If render type is not for Presenter::RENDER_CONTENT
         * then use CommonSkin's '_frame.blade.php' for layout.
         * '_frame.blade.php' has assets load script like js, css.
         */
        if (XePresenter::getRenderType() == Presenter::RENDER_CONTENT) {
            $view = $contentView;
        } else {
            // wrapped by _frame.blade.php
            if (View::exists(sprintf('%s/views/_frame', static::$path)) === false) {
                static::$path = self::$path;
            }
            $view = View::make(sprintf('%s/views/_frame', static::$path), $this->data);
            $view->content = $contentView;
        }

        return $view;
    }


    /**
     * is manager
     *
     * @return bool
     */
    protected function isManager()
    {
        return false;
    }
}
