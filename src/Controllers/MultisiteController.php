<?php

namespace Amuz\XePlugin\Multisite\Controllers;

use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Overcode\XePlugin\DynamicFactory\Services\CptDocService;
use Auth;
use XeFrontend;
use XePresenter;
use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Xpressengine\Http\Request;
use Xpressengine\Routing\InstanceConfig;

class MultisiteController extends Controller
{
    protected $instanceId;

    public $cptUrlHandler;

    public $configHandler;

    public $dfDocHandler;

    public $config;

    protected $taxonomyHandler;

    public function __construct(
        CptModuleConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler,
        DynamicFactoryDocumentHandler $dfDocHandler
    )
    {
        $instanceConfig = InstanceConfig::instance();
        $this->instanceId = $instanceConfig->getInstanceId();

        $this->configHandler = $configHandler;
        $this->cptUrlHandler = $cptUrlHandler;
        $this->dfDocHandler = $dfDocHandler;
        $this->config = $configHandler->get($this->instanceId);
        if ($this->config !== null) {
            $cptUrlHandler->setInstanceId($this->config->get('instanceId'));
            $cptUrlHandler->setConfig($this->config);
        }
        $this->taxonomyHandler = app('overcode.df.taxonomyHandler');

        XePresenter::setSkinTargetId(CptModule::getId());
        XePresenter::share('configHandler', $configHandler);
        XePresenter::share('cptUrlHandler', $cptUrlHandler);
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);
    }

    public function index(CptDocService $service, Request $request)
    {
        \XeFrontend::title($this->getSiteTitle());

        $cpt_id = $this->config->get('cpt_id');

        $dfConfig = app('overcode.df.configHandler')->getConfig($cpt_id);
        $column_labels = app('overcode.df.configHandler')->getColumnLabels($dfConfig);

        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt_id);
        $categories = [];

        foreach($taxonomies as $taxonomy) {
            $categories[$taxonomy->id]['group'] = $this->taxonomyHandler->getTaxFieldGroup($taxonomy->id);
            $categories[$taxonomy->id]['items'] = $this->taxonomyHandler->getCategoryItemAttributes($taxonomy->id);
        }

        $paginate = $service->getItems($request, $this->config);

        return XePresenter::makeAll('index', [
            'paginate' => $paginate,
            'dfConfig' => $dfConfig,
            'column_labels' => $column_labels,
            'taxonomies' => $taxonomies,
            'categories' => $categories
        ]);
    }

    public function show(
        CptDocService $service,
        Request $request,
        $menuUrl,
        $id
    )
    {
        $user = Auth::user();

        $item = $service->getItem($id, $user, $this->config);

        // 글 조회수 증가
        if ($item->display == CptDocument::DISPLAY_VISIBLE) {
            $this->dfDocHandler->incrementReadCount($item, Auth::user());
        }

        $dyFacConfig = app('overcode.df.configHandler')->getConfig($this->config->get('cpt_id'));
        $fieldTypes = $service->getFieldTypes($dyFacConfig);

        $dynamicFieldsById = [];
        foreach ($fieldTypes as $fieldType) {
            $dynamicFieldsById[$fieldType->get('id')] = $fieldType;
        }

        return XePresenter::make('show', [
            'item' => $item,
            'fieldTypes' => $fieldTypes,
            'dynamicFieldsById' => $dynamicFieldsById
        ]);
    }

    private function getSiteTitle()
    {
        $siteTitle = \XeFrontend::output('title');

        $instanceConfig = InstanceConfig::instance();
        $menuItem = $instanceConfig->getMenuItem();

        $title = xe_trans($menuItem['title']) . ' - ' . xe_trans($siteTitle);
        $title = strip_tags(html_entity_decode($title));

        return $title;
    }

    /**
     * 문자열을 넘겨 slug 반환
     *
     * @param Request $request request
     * @return mixed
     */
    public function hasSlug(Request $request)
    {
        $slugText = DfSlug::convert('', $request->get('slug'));
        $slug = DfSlug::make($slugText, $request->get('id'));

        return XePresenter::makeApi([
            'slug' => $slug
        ]);
    }

    public function slug(CptDocService $service, Request $request, $menuUrl, $strSlug)
    {
        $cpt_id = $this->config->get('cpt_id');

        $slug = DfSlug::where('slug', $strSlug)->where('instance_id', $cpt_id)->first();

        if ($slug === null) {
            throw new NotFoundDocumentException;
        }

        return $this->show($service, $request, $menuUrl, $slug->target_id);
    }
}
