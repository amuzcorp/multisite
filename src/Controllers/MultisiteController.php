<?php
namespace Amuz\XePlugin\Multisite\Controllers;

use Amuz\XePlugin\Multisite\Models\Site;
use Xpressengine\Http\Request;
use XeFrontend;
use XePresenter;
use Amuz\XePlugin\Multisite\Components\Modules\SiteList\SiteListModule;
use Amuz\XePlugin\Multisite\Plugin;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Routing\InstanceConfig;

class MultisiteController extends BaseController
{
    protected $instanceId;
    public $config;

    public function __construct(
    )
    {
        $instanceConfig = InstanceConfig::instance();
        $this->instanceId = $instanceConfig->getInstanceId();

        XePresenter::setSkinTargetId(SiteListModule::getId());
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);
    }
    public function index(Request $request)
    {
        $title = xe_trans('multisite::multisite');

        // set browser title
        XeFrontend::title($title);

        $keyword = $request->get('query');
        $Sites = Site::whereHas('domains', function ($query) use ($keyword){
            $query->where('domain','like','%'.$keyword.'%');
        })->orWhereHas('configSEO', function($query) use ($keyword){
            $query->where('vars','like','%'.$keyword.'%');
        })->get();

        // set browser title
        XeFrontend::title($title);

        // load css file
        XeFrontend::css(Plugin::asset('assets/style.css'))->load();

        // output
        return XePresenter::makeAll('index', ['title' => $title, 'Sites' => $Sites, 'keyword' => $keyword]);
    }

    public function show($instance_id, $site_key){
        $Site = Site::find($site_key);
        if($Site == null){
            return redirect()->back()->with('alert', [
                    'type' => 'failed', 'message' => xe_trans('multisite::siteHostNoneExists')]
            );
        }

        $defaultSite = Site::find('default');
        return XePresenter::makeAll('show', compact('site_key',  'Site','defaultSite'));
    }
}
