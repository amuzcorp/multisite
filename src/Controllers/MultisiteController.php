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

        $filter_target = $request->get('target');
        $filter_value = $request->get('filter');

        $keyword = $request->get('query');

        $translations = \DB::table('translation')->where('namespace','user')->where('locale',\XeLang::getLocale())->where('value','like','%'.$keyword.'%')->pluck('item');

        \DB::enableQueryLog(); // Enable query log
        $collection = Site::whereDoesntHave('configMeta', function($query){
            $query->where('vars','like','%"use_list":"N"%');
        });
        //set filter
        if($filter_target !== null && $filter_value !== null){
            $collection->whereHas('configMeta', function($query) use($filter_target,$filter_value){
                $query->where('vars','like','%"'. $filter_target .'":"'. $filter_value .'"%');
            });
        }
        //set search keyword
        $Sites = $collection->where(function($q) use ($keyword, $translations){
            $q->whereHas('domains', function ($query) use ($keyword){
                $query->where('domain','like','%'.$keyword.'%');
            })->orWhereHas('configMeta', function($query) use ($keyword,$translations){
                $toJson = str_replace('"','',json_enc($keyword,1,-1));
                $escapeBackSlash = str_replace('\\','%',$toJson);
                $query->where('vars','like','%'.$escapeBackSlash.'%');
                foreach($translations as $key => $item){
                    $query->orWhere('vars','like','%'.$item.'%');
                }
            });
        })->paginate(30);
//        dd(\DB::getQueryLog()); // Show results of log

        // set browser title
        XeFrontend::title($title);

        // load css file
        XeFrontend::css(Plugin::asset('assets/style.css'))->load();

        // output
        return XePresenter::makeAll('index', [
            'title' => $title, 'Sites' => $Sites, 'keyword' => $keyword,
            'target' => $filter_target, 'filter' => $filter_value
        ]);
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
