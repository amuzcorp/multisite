<?php
namespace Amuz\XePlugin\Multisite\Controllers;

use XeFrontend;
use XePresenter;
use XeLang;
use Plugin;
use Amuz\XePlugin\Multisite\Models\Site;
use App\Http\Controllers\Controller as BaseController;

class MultisiteSettingsController extends BaseController
{
    /**
     * ManagerController constructor.
     */
    public function __construct()
    {
//        XePresenter::setSettingsSkinTargetId('multisite');
        XeFrontend::css('plugins/multisite/assets/style.css')->load();
    }

    public function index()
    {
        $title = xe_trans('multisite::multisite');

        // set browser title
        XeFrontend::title($title);

        $Sites = Site::all();

        // output
        return XePresenter::make('multisite::views.settings.index', [
            'title' => $title,
            'Sites' => $Sites
        ]);
    }
}
