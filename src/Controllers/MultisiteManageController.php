<?php
namespace Amuz\XePlugin\Multisite\Controllers;

use App\Http\Sections\SkinSection;
use XeFrontend;
use XePresenter;
use XeLang;
use XeToggleMenu;
use XeConfig;
use XeDB;

use Illuminate\Contracts\Foundation\Application;
use App\Http\Controllers\Controller as BaseController;
use Amuz\XePlugin\Multisite\Components\Modules\SiteList\SiteListModule;

class MultisiteManageController extends BaseController
{
    /**
     * Application instance
     *
     * @var Application
     */
    private $app;

    /**
     * ManagerController constructor.
     * * @param Application $app Application instance
     */
    public function __construct(Application $app)
    {
        XePresenter::setSettingsSkinTargetId(SiteListModule::getId());
        $this->app = $app;
    }

    /**
     * @param string $pageId page id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function edit($pageId)
    {
        $skinSection = new SkinSection(SiteListModule::getId(), $pageId);

        return XePresenter::make('skin', [
            'pageId' => $pageId,
            'skinSection' => $skinSection,
        ]);
    }
}
