<?php
namespace Amuz\XePlugin\Multisite\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\Filesystem;
use XeFrontend;
use XePresenter;
use XeLang;
use XeToggleMenu;
use XeConfig;
use XeDB;

use Illuminate\Contracts\Foundation\Application;
use Amuz\XePlugin\Multisite\Models\Site;
use Amuz\XePlugin\Multisite\Models\SiteDomain;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Config\ConfigManager;
use Xpressengine\Http\Request;
use Xpressengine\Menu\MenuHandler;
use Xpressengine\Menu\Models\Menu;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Permission\Grant;
use Xpressengine\Plugin\Exceptions\PluginActivationFailedException;
use Xpressengine\Plugins\Board\Plugin\Resources as BoardResources;
use Xpressengine\Plugins\Comment\Handler as commentHandler;
use Xpressengine\Presenter\Html\Tags\Html;
use Xpressengine\Skin\SkinHandler;
use Xpressengine\Support\Migration;
use Xpressengine\Theme\ThemeHandler;
use Xpressengine\User\Models\User;
use Xpressengine\User\Models\UserGroup;
use Xpressengine\User\Rating;
use Xpressengine\Media\MediaManager;
use Xpressengine\Storage\Storage;
use Xpressengine\Routing\InstanceRoute;

class MultisiteSettingsController extends BaseController
{
    /**
     * Application instance
     *
     * @var Application
     */
    private $app;

    /**
     * Storage instance
     *
     * @var Storage
     */
    protected $storage;

    /**
     * MediaManager instance
     *
     * @var MediaManager
     */
    protected $media;

    /**
     * ManagerController constructor.
     * *
     * @param Application $app Application instance
     * @param Storage $storage
     * @param MediaManager $media
     */
    public function __construct(Application $app, Storage $storage, MediaManager $media)
    {
        $this->app = $app;
        $this->storage = $storage;
        $this->media = $media;

        XeFrontend::css('plugins/multisite/assets/style.css')->load();
        XeFrontend::js('plugins/multisite/assets/amuz_common.js')->load();
    }

    public function index(Request $request)
    {
        $title = xe_trans('multisite::multisite');

        // set browser title
        XeFrontend::title($title);
        $list_count = $request->get('list_count') ? $request->get('list_count') : 10;

        //get keyword translations
        $keyword = $request->get('query');
        $translations = \DB::table('translation')->where('namespace','user')->where('locale',\XeLang::getLocale())->where('value','like','%'.$keyword.'%')->pluck('item');

        //activated site & use_list
        \DB::enableQueryLog(); // Enable query log

        $status_list = ['activated','deactivated'];
        $collection = Site::OrderBy('created_at','desc');

        if(in_array($request->get('status'),$status_list)){
            $collection = $collection->where('status',$request->get('status'));
        }

        //set search keyword
        $collection = $collection->where(function($q) use ($keyword, $translations){
            $q->whereHas('domains', function ($query) use ($keyword){
                $query->where('domain','like','%'.$keyword.'%');
            })->orWhereHas('configSEO', function($query) use ($keyword,$translations){
                $toJson = str_replace('"','',json_enc($keyword,1,-1));
                $escapeBackSlash = str_replace('\\','%',$toJson);
                $query->where('vars','like','%'.$escapeBackSlash.'%');
                foreach($translations as $key => $item){
                    $query->orWhere('vars','like','%'.$item.'%');
                }
            });
        });

        //번역으로 해당사이트이름을 가진사이트의 id가 있으면 받아두고, 최종 where절에 or로 붙여서 해결. ㅡㅡ
        if(count($translations) > 0) {
            $has_keyword_sites = \DB::table('config')->where('site_key', 'default')->where('name', 'like', 'site.%')->Where(function ($query) use ($translations) {
                foreach ($translations as $translation_item) {
                    $query->orwhere('vars', 'like', '%user::' . $translation_item . '%');
                }
            })->get()->pluck('name');
            foreach($has_keyword_sites as $key => $val) $has_keyword_sites[$key] = explode(".",$val)[1];

            if(count($has_keyword_sites) > 0){
                $collection = $collection->orWhere(function ($query) use ($has_keyword_sites) {
                    $query->whereIn('site_key', $has_keyword_sites);
                });
            }
        }

        $Sites = $collection->paginate($list_count);
//        dd(\DB::getQueryLog()); // Show results of log

        //사이트설정들이 완전하지 않아서 추가로발견되면 계속적으로 추가해줘야함.
        //최종 쿼리결과에 픽스할 사항이있으면 여기에 조금씩 추가.
        //기본적으로 주석처리하고, 필요할때 풀어서 사용 권장
        /*
        if(count($Sites) > 0){
            foreach($Sites as $Site){
                $has_group = UserGroup::Where('site_key',$Site->site_key)->first();
                if($has_group == null){
                    $this->app['xe.site']->setCurrentSite($Site);
                    $this->runRegister($Site->site_key);
                }
            }

            $this->app['xe.site']->setCurrentSite(Site::find('default'));
        }
        */

        return XePresenter::make('multisite::views.settings.index', [
            'title' => $title,
            'Sites' => $Sites,
            'list_count' => $list_count,
            'keyword' => $keyword
        ]);
    }

    static public function getSiteInfos(){
        $registered = \XeRegister::get('multisite/site_info');
        $infos = array();
        foreach ($registered as $key => $val){
            $keys = explode(".",$key);
            if($keys[0] != "setting") continue;

            if(!isset($infos[$keys[1]])) $infos[$keys[1]] = array();
            if(count($keys) > 2) {
                $val["config_id"] = $keys[2];
                if(!isset($infos[$keys[1]]['fields'])) $infos[$keys[1]]['fields'] = array();
                $infos[$keys[1]]['fields'][$keys[2]] = $val;
            }else{
                $val["config_id"] = $keys[1];
                $infos[$keys[1]] = $val;
            }
        }
        uasort($infos, function($a, $b){
            return $a['ordering'] - $b['ordering'];
        });
        return $infos;
    }

    public function mysite($mode = null){
        $site_key = \XeSite::getCurrentSiteKey();
        return $this->edit($site_key, $mode, 'settings.multisite.mysite');
    }

    public function edit($site_key, $mode = null, $target_route = 'settings.multisite.edit'){
        $title = xe_trans('multisite::multisite');

        $Site = Site::find($site_key);
        if($Site == null){
            return redirect()->route($target_route)->with('alert', [
                    'type' => 'failed', 'message' => xe_trans('multisite::siteHostNoneExists')]
            );
        }

        $html = new Html();
        $html->content('<script>var site_key = "'.$site_key.'";</script>');
        $html->prependTo('head')->load();

        //call permission handler
        $permissionHandler = app('xe.permission');
        $permissionGroups = [
            "사이트 접근권한 설정" => [
                [
                    "title" => "사이트 접속권한 (폐쇄형 사이트인 경우 사용)",
                    "id" => "multisite",
                    "default" => ["access" => ["rating" => "guest","group" => [],"user" => [],"except" => []]],
                ],
                [
                    "title" => "소유자",
                    "id" => "multisite.owner",
                    "default" => ["access" => ["rating" => "super","group" => [],"user" => [],"except" => []]],
                ],
                [
                    "title" => "관리자 접근권한",
                    "id" => "multisite.manager",
                    "default" => ["access" => ["rating" => "super","group" => [],"user" => [],"except" => []]],
                ]
            ]
        ];
        foreach ($permissionGroups as $tab => &$group) {
            foreach ($group as $key => &$item) {
                $permission = $permissionHandler->get('settings.'.$item['id'],$site_key);
                if ($permission === null) {
                    $permission = $permissionHandler->register('settings.'.$item['id'], new Grant($item['default']),$site_key);
                }
                $item['id'] = 'settings.'.$item['id'];
                $item['permission'] = $permission;
            }
        }

        //for domain
        $output = [];
        switch($mode){
            case 'meta' :
                $output['infos'] = $this->getSiteInfos();
                break;
            case 'domains' :
                $output['domains'] = SiteDomain::where('site_key',$site_key)->orderBy('is_featured','DESC')->get();
                $instances = InstanceRoute::where('site_key',$site_key)->get();
                $output['menu_instances'] = [];
                foreach($instances as $instance)
                    $output['menu_instances'][$instance->instance_id] = $instance->MenuItem->getLinkAttribute();

                if(\Request::get('target_domain')){
                    $output['domain'] = SiteDomain::find(\Request::get('target_domain'));
                    if(isset($output['domain']->site_key) && $output['domain']->site_key != $site_key) $output['domain'] = new SiteDomain();
                }
                break;
            case 'users' :
                // get site groups
                $output['groups'] = UserGroup::where('site_key',$site_key)->get();

                //set current site groups
                $group_ids = $output['groups']->pluck('id');
                $collection = User::whereHas(
                    'allSiteGroups',
                    function (Builder $q) use ($group_ids) {
                        $q->whereIn('group_id', $group_ids);
                    }
                );

                $output['allUserCount'] = $collection->count();

                // resolve search keyword
                // keyfield가 지정되지 않을 경우 email, display_name, login_id를 대상으로 검색함
                $request = request();
                $field = $request->get('keyfield') ?: 'email,display_name,login_id';

                if ($keyword = trim($request->get('keyword'))) {
                    $collection = $collection->where(
                        function (Builder $q) use ($field, $keyword) {
                            foreach (explode(',', $field) as $f) {
                                $q->orWhere($f, 'like', '%'.$keyword.'%');
                            }
                        }
                    );
                }

                $output['users'] = $collection->orderBy('created_at', 'desc')->paginate()->appends(request()->query());
                $output['user_config'] = app('xe.config')->get('user.register');
                break;
            case 'managers' :
                $output['permissionGroups'] = $permissionGroups;
                break;
            case 'menu' :
                $getMenu = \XeRegister::get('settings/menu');
                ksort($getMenu);

                //메뉴 엑세스권한
                $permission = $permissionHandler->get('multisite.menus',$site_key);
                $translator = app('xe.translator');
                $config = app('xe.config');
                $setting_menu_config = $config->get('setting_menus',false,$site_key);
                if($setting_menu_config == null) $setting_menu_config = $config->set('setting_menus',[],false,null,$site_key);
                if ($permission === null) $permissionHandler->register('multisite.menus', new Grant(),$site_key);

                foreach ($getMenu as $id => $item) {
                    //if has config, replace $item
                    $item_config = $config->get('setting_menus.'.$id,false,$site_key);
                    if($item_config == null){
                        $item_config = $config->set('setting_menus.'.$id,$item,false,null,$site_key);
                        $config->modify($item_config);
                    }else{
                        foreach($item_config as $key => $val) $item[$key] = $val;
                    }

                    //플러그인에서 삭제한경우, is_off가 선언되지 않고 display만 false가 됨
                    if(array_get($item,'display',false) == false && !isset($item['is_off'])){
                        unset($getMenu[$id]);
                        continue;
                    }

                    $item['display'] = array_get($item,'display',false);
                    $item['title'] = array_get($item,'title','Deleted by Plugin');
                    $item['ordering'] = array_get($item,'ordering',9999);
                    $item['icon'] = array_get($item,'icon','xi-bars');
                    $item['is_off'] = array_get($item,'is_off','N');

                    if(!isset($item['title_lang'])){
                        $title_lang = $translator->genUserKey();
                        foreach ($translator->getLocales() as $locale) {
                            $value = xe_trans($item['title'],[],$locale);
                            XeLang::save($title_lang, $locale, $value);
                        }
                        $item['title_lang'] = $title_lang;
                    }

                    //save for default options
                    $item_config = $config->set('setting_menus.'.$id,$item,false,null,$site_key);
                    $config->modify($item_config);

                    $permission = $permissionHandler->get('multisite.menus.'.$id,$site_key);
                    if ($permission === null) $permission = $permissionHandler->register('multisite.menus.'.$id, new Grant(["access" => ["rating" => "manager","group" => [],"user" => [],"except" => []]]),$site_key);

                    $item['menuGroup'] = str_replace('.','_',$id);
                    $item['config_key'] = 'setting_menus.'.$id;
                    $item['permission_key'] = 'multisite.menus.'.$id;
                    $item['permission'] = $permission;
                    $item['title'] = $item['title_lang'];

                    $getMenu[$id] = $item;
                }
                $config->modify($setting_menu_config);

                foreach (  $getMenu as  $key => $value ) {
                    $key = str_replace(".", ".child.", $key);
                    array_set($menus, $key, $value);
                }
                uasort($menus, function($a, $b){
                    return $a['ordering'] - $b['ordering'];
                });
                $output['menus'] = $menus;
                break;
        }


        $defaultSite = Site::find('default');
        return XePresenter::make('multisite::views.settings.edit', compact('title','site_key', 'mode', 'Site', 'output', 'defaultSite', 'target_route'));
    }

    public function updateSettingMenusConfig(Request $request,$site_key,$config_id)
    {
        $config = app('xe.config');
        $itemConfig = $config->get($config_id,false,$site_key);
        if($itemConfig == null)
            return XePresenter::makeApi(['alert_type' => 'danger', 'message' => '잘못된 설정의 변경을 시도합니다.']);

        $item = $request->only('icon','is_off','ordering','description');
        $item['title_lang'] = $request->get('title');
        foreach($item as $key => $val) $itemConfig[$key] = $val;

        app('xe.config')->modify($itemConfig);
        return XePresenter::makeApi(['alert_type' => 'success', 'message' => '설정이 저장되었습니다.']);
    }

    public function updateSitePermissions(Request $request,$site_key,$permission_id)
    {
        $permissionHandler = app('xe.permission');
        $permissionHandler->register($permission_id, $this->createAccessGrant(
            $request->only(['accessRating', 'accessGroup', 'accessUser', 'accessExcept'])
        ),$request->get('site_key'));

        return XePresenter::makeApi(['alert_type' => 'success', 'message' => '엑세스 권한이 저장되었습니다.']);
    }

    /**
     * Create the grant of access
     *
     * @param array $inputs to create grant params array
     * @return Grant
     */
    protected function createAccessGrant(array $inputs)
    {
        $grant = new Grant;

        $rating = array_get($inputs, 'accessRating', Rating::SUPER);
        $group = $this->innerParamParsing($inputs['accessGroup']);
        $user = $this->innerParamParsing($inputs['accessUser']);
        $except = $this->innerParamParsing($inputs['accessExcept']);

        $grant->add('access', 'rating', $rating);
        $grant->add('access', 'group', $group);
        $grant->add('access', 'user', $user);
        $grant->add('access', 'except', $except);

        return $grant;
    }

    /**
     * Parse the given parameter.
     *
     * @param string $param parameter
     * @return array
     */
    protected function innerParamParsing($param)
    {
        if (empty($param)) {
            return [];
        }

        $ret = explode(',', $param);
        return array_filter($ret);
    }


    public function deleteDomain(Request $request, $site_key)
    {
//        dd($request->get('id'));
        \DB::table('site_domains')->where('site_key',$site_key)->where('is_featured','N')->whereIn('domain',$request->get('id'))->delete();
        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function createDomain(Request $request, $site_key)
    {
        $domain = SiteDomain::find($request->get('domain'));
        $args = $request->except('_token','domain');
        if(is_null($domain)){
            //create
            $domain = new SiteDomain();
            $domain->fill(['domain' => $request->get('domain')]);
            $domain->fill($args);
            $domain->fill(['site_key' => $site_key, 'is_featured' => 'N']);
            $domain->save();
        }else if(isset($domain->site_key) && $domain->site_key == $site_key){
            //update
            foreach($args as $key => $val) $domain->{$key} = $val;
            $domain->save();
        }else{
            return redirect()->back()->with('alert', ['type' => 'failed', 'message' => '잘못된 도메인이 요청되었습니다.']);
        }

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }


    public function updateDefaultDomain(Request $request, $site_key){
        $domain = SiteDomain::find($request->get('featured_domain'));

        if(isset($domain->site_key) && $domain->site_key == $site_key){
            \DB::table('site_domains')->where('site_key',$site_key)->update(['is_featured' => 'N']);

            $domain = SiteDomain::find($request->get('featured_domain'));
            $domain->is_featured = "Y";
            $domain->index_instance = null;
            $domain->save();
        }else{
            return redirect()->back()->with('alert', ['type' => 'failed', 'message' => '잘못된 도메인이 요청되었습니다.']);
        }

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function addSiteUser(Request $request, $site_key){
        $target_group = UserGroup::where('site_key',$site_key)->where('id',$request->get('group_id'))->first();
        if($target_group == null)
            return redirect()->back()->with('alert', ['type' => 'failed', 'message' => '올바른 그룹을 선택하세요.']);

        $target_user = User::where('login_id',$request->get('user_id'))->orWhere('email',$request->get('user_id'))->first();
        if($target_user == null)
            return redirect()->back()->with('alert', ['type' => 'failed', 'message' => '추가할 회원 대상이 잘못되었습니다.']);

        $target_user->joinGroups($target_group->id);
        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);

    }

    public function update(Request $request, $site_key){
        $Site = Site::find($site_key);
        if($Site == null){
            return redirect()->route('settings.multisite.index')->with('alert', [
                    'type' => 'failed', 'message' => xe_trans('multisite::siteHostNoneExists')]
            );
        }

        //set parent config
        $meta_config = \DB::table('config')->where('name', 'site_meta')
            ->where('site_key', $site_key)->first();
        if($meta_config == null){
            \DB::table('config')->insert(['name' => 'site_meta', 'vars' => '[]', 'site_key' => $site_key]);
        }

        //arrange configs
        $infos = $this->getSiteInfos();
        $config_values = array();
        foreach($infos as $parent_id => $info){
            $config_values[$parent_id] = array();
            foreach ($info['fields'] as $config_id => $field) {
                if($request->file($config_id) !== null){
                    $file = \XeStorage::upload($request->file($config_id), 'public/sites/'.$site_key.'/meta');
                    $image = \XeMedia::make($file);
                    $this->storage->unBindAll($site_key . "." . $config_id, true);
                    $this->storage->bind($site_key . "." . $config_id, $image);
                    $config_values[$parent_id][$config_id] = $image->getKey();
                }else if($request->get($config_id) === '__delete_file__') {
                    $this->storage->unBindAll($site_key . "." . $config_id, true);
                }else{
                    //text
                    $config_values[$parent_id][$config_id] = $request->get($config_id);
                }
            }
        }

        XeDB::beginTransaction();
        try {
            foreach ($config_values as $key => $value) {
                app('xe.config')->set('site_meta.' . $key, $value, false, null, $site_key);
            }
        }catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();


        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function create(){
        $Site = new Site();
        $title = xe_trans('multisite::multisite');

        $defaultSite = Site::find('default');
        $infos = $this->getSiteInfos();

        return XePresenter::make('multisite::views.settings.create', compact('title','Site','defaultSite','infos'));
    }

    public function store(Request $request){
        $defaultSite = Site::find('default');
        $site_key = strtolower($request->get('host'));
        //존재하는 사이트인지 확인
        $Site = Site::find($site_key);
        if($Site != null){
            return redirect()->back()->with('alert', [
                'type' => 'failed', 'message' => xe_trans('multisite::siteHostExists')]
            )->withInput($request->all());
        }

        XeDB::beginTransaction();
        try {
            //사이트 생성
            $Site = new Site();
            $Site->fill([
                'site_key' => $site_key,
                'host' => sprintf("%s.%s",$site_key,$defaultSite->FeaturedDomain->first()->domain),
            ]);
            $Site->save();

            //permission,config 때문에 잠시 스푸핑
            $Site = Site::find($site_key);
            $this->app['xe.site']->setCurrentSite($Site);

            //도메인 객체 붙이기
            $Domain = new SiteDomain();
            $Domain->fill([
                'domain' => sprintf("%s.%s",$site_key,$defaultSite->FeaturedDomain->first()->domain),
                'is_featured' => 'Y'
            ]);
            $Domain->Site()->associate($Site);
            $Domain->save();

            //필수적인 상위권한만 추가
            \DB::table('permissions')->insert([
                ['site_key' => $site_key, 'name' => 'module/board@board', 'grants' => '{"create":{"rating":"user","group":[],"user":[],"except":[]},"read":{"rating":"guest","group":[],"user":[],"except":[]},"list":{"rating":"guest","group":[],"user":[],"except":[]},"manage":{"rating":"manager","group":[],"user":[],"except":[]}}'],
                ['site_key' => $site_key, 'name' => 'comment', 'grants' => '{"create":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"manage":{"rating":"manager","group":[],"user":[],"except":[],"vgroup":[]}}'],
                ['site_key' => $site_key, 'name' => 'editor', 'grants' => '{"html":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"tool":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"upload":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"download":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]}}']
            ]);
            //parent configs 설정
            \DB::table('config')->insert([
                ['site_key' => 'default', 'name' => 'site.' . $site_key , 'vars' => '[]'],
                ['name' => 'site_meta', 'vars' => '[]', 'site_key' => $site_key]
            ]);

            //코어 마이그레이션 실행
            $this->runMigrations($site_key);

            //회원그룹 생성, 가입설정만들기
            $this->runRegister($site_key);

            //사이트 테마설정
            // set site default theme
            $theme = ['desktop' => $request->get('theme_desktop'), 'mobile' => $request->get('theme_mobile')];
            app('xe.theme')->setSiteTheme($theme);

            // 기본 메뉴 추가 (main) 추가.
            /** @var MenuHandler $menuHandler */
            $menuHandler = app('xe.menu');

            // 기본 메뉴 config  설정
            $mainMenu = $menuHandler->createMenu([
                'title' => 'Main Menu',
                'description' => 'Main Menu',
                'site_key' => $site_key
            ]);
            $menuHandler->setMenuTheme($mainMenu, $request->get('theme_desktop'), $request->get('theme_mobile'));
            app('xe.permission')->register($mainMenu->getKey(), $menuHandler->getDefaultGrant(), $site_key);

            $theme_plugin = $this->setThemeConfig($mainMenu->id, $request->get('theme_desktop'), $request->get('theme_mobile'), $site_key);

            //Merge Plugin , theme plugin
            $plugins = $request->get('extensions') ? $request->get('extensions') : array();
            $plugins = array_merge($plugins,$theme_plugin);
            $plugins = array_keys($plugins);

            //do Activate Plugins
            $vars = ['list' => []];
            $need_default_plugin_functions = ['comment','board'];
            foreach ($plugins as $id) {
                $entity = \XePlugin::getPlugin($id);
                $installedVersion = $entity->getInstalledVersion();
                try {
                    $entity->activate($installedVersion);
                } catch (\Exception $e) {
                    throw new PluginActivationFailedException([], null, $e);
                }

                //기본플러그인에서 멀티사이트에 대한 고려가 안되어 별도의 함수실행이 필요한 경우 (activate() 만으로 동작이 안될때)
                if(in_array($id,$need_default_plugin_functions)) $this->pluginActivateforMultisite($id);

                //플러그인 업데이트 따라가기
                if(!$entity->checkUpdated($installedVersion)){
                    $entity->update($installedVersion);
                }
                //set status
                $vars['list'][$id]['status'] = 'activated';
                $vars['list'][$id]['version'] = $entity->getVersion();
            }

            \DB::table('config')->where('name', 'plugin')->where('site_key', $site_key)->update(
                ['vars' => json_enc($vars)]
            );
            //for Menu Item and theme
            $this->widgetPageModuleMenuSetup($mainMenu,$site_key,$request->get('site_title'), $request->get('theme_desktop'), $request->get('theme_mobile'));
            $this->boardModuleMenuSetup($mainMenu, $site_key, $request->get('theme_desktop'), $request->get('theme_mobile'));


            $this->app['xe.site']->setCurrentSite($defaultSite);

            //arrange meta infos
            $infos = $this->getSiteInfos();
            $config_values = array();
            foreach($infos as $parent_id => $info){
                $config_values[$parent_id] = array();
                foreach ($info['fields'] as $config_id => $field) {
                    if($request->file($config_id) !== null){
                        $file = \XeStorage::upload($request->file($config_id), 'public/sites/'.$site_key.'/meta');
                        $image = \XeMedia::make($file);
                        $this->storage->unBindAll($site_key . "." . $config_id, true);
                        $this->storage->bind($site_key . "." . $config_id, $image);
                        $config_values[$parent_id][$config_id] = $image->getKey();
                    }else if($request->get($config_id) === '__delete_file__') {
                        $this->storage->unBindAll($site_key . "." . $config_id, true);
                    }else{
                        //text
                        $config_values[$parent_id][$config_id] = $request->get($config_id);
                    }
                }
            }

            //set metainfos
            foreach ($config_values as $key => $value) {
                app('xe.config')->set('site_meta.' . $key, $value, false, null, $site_key);
            }

        } catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();

        return redirect()->route('settings.multisite.index')->with('alert', [
            'type' => 'success', 'message' => xe_trans('multisite::createdNewSite')
        ]);
    }

    public function destroy(Request $request){
        $args = $request->all();

        if($args['site_key'] == 'default') return redirect()->route('settings.multisite.index')->with('alert', [
            'type' => 'error', 'message' => '기본사이트는 삭제가 불가능합니다.'
        ]);

        \XeDB::beginTransaction();
        try {

            if($args['target_site_key'] != 0){
                //이동 할 컨텐츠 준비
                $target_contents = array();
                if(isset($args['contents'])){
                    foreach($args['contents'] as $target_module => $on) $target_contents[] = $target_module;
                }

                //TODO - 이동처리 문서/CPT/첨부파일
                //DB::table('instance_route')->whereIn('module',join($target_contents))->where('site_key',$args['site_key'])->update('site_key',$args['target_site_key']);

                //TODO - 삭제처리 문서/CPT/첨부파일
            }

            //삭제시작
            \XeDB::table('site')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('site_domains')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('menu')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('menu_item')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('permissions')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('instance_route')->where('site_key',$args['site_key'])->delete();

            \XeDB::table('widgetbox')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('user_group')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('user_terms')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('user_term_agrees')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('config')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('config')->where('name','site.'.$args['site_key'])->delete();

        } catch (Exception $e) {
            \XeDB::rollBack();

            throw $e;
        }
        \XeDB::commit();

        return redirect()->route('settings.multisite.index')->with('alert', [
            'type' => 'success', 'message' => xe_trans('multisite::deletedNewSite')
        ]);
    }

    public function runRegister($site_key = 'default'){
        $registerConfig = app('xe.config')->get('user.register');
        // add default user groups
        $joinGroup = app('xe.user')->groups()->create(
            [
                'name' => $site_key . ' 사이트의 기본 회원',
                'description' => 'Default Group In Multisite ['.$site_key.']'
            ]
        );
        $registerConfig->set('joinGroup', $joinGroup->id);

        $displayNameCaption = XeLang::genUserKey();
        foreach (XeLang::getLocales() as $locale) {
            $value = "닉네임";
            if ($locale != 'ko') {
                $value = "Nickname";
            }
            XeLang::save($displayNameCaption, $locale, $value);
        }
        $registerConfig->set('display_name_caption', $displayNameCaption);
        app('xe.config')->modify($registerConfig);
        // 생성자를 관리자로 추가
        auth()->user()->joinGroups($joinGroup);
    }

    public function runMigrations($site_key){
        /** @var Filesystem $filesystem */
        $filesystem = app('files');
        $files = $filesystem->files(base_path('migrations'));

        usort($files, function ($pre, $post) {
            return $pre->getFileName() > $post->getFileName();
        });
        foreach ($files as $file) {
            $class = "\\Xpressengine\\Migrations\\".basename($file, '.php');
            $this->migrations[] = $migration = new $class();
            /** @var Migration $migration */
            if (method_exists($migration, 'installed')) {
                $migration->installed($site_key);
            }
        }
    }

    public function widgetPageModuleMenuSetup($mainMenu,$site_key,$site_title,$desktop_theme, $mobile_theme)
    {
        /** @var MenuHandler $menuHandler */
        $menuHandler = app('xe.menu');

        $menuTitle = XeLang::genUserKey();
        foreach (XeLang::getLocales() as $locale) {
            $value = "멀티사이트의 첫화면";
            if ($locale != 'ko') {
                $value = "Homepage of Multisite";
            }
            XeLang::save($menuTitle, $locale, $value, false);
        }

        $inputs = [
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'title' => $menuTitle,
            'site_key' => $site_key,
            'url' => 'home',
            'description' => 'home',
            'target' => '',
            'type' => 'widgetpage@widgetpage',
            'ordering' => '1',
            'activated' => '1',
        ];

        $menuTypeInput = [
            'pageTitle' => 'Welcome Multisite with XE3',
            'comment' => false,
            'siteKey' => $site_key
        ];

        $item = $menuHandler->createItem($mainMenu, $inputs, $menuTypeInput);

        $menuHandler->setMenuItemTheme($item, $desktop_theme, $mobile_theme, $site_key);
        app('xe.permission')->register($menuHandler->permKeyString($item), new Grant, $site_key);

        $this->siteDefaultConfig($mainMenu, $item->id, $site_key, $site_title);

        return $item;
    }

    /**
     * boardModuleMenuSetup
     *
     * @param Menu $mainMenu
     *
     * @return MenuItem
     */
    public function boardModuleMenuSetup($mainMenu, $site_key, $desktop_theme, $mobile_theme)
    {
        // board, menu item 추가.
        /** @var MenuHandler $menuHandler */
        $menuHandler = app('xe.menu');
        $boardBoardTitle = XeLang::genUserKey();
        foreach (XeLang::getLocales() as $locale) {
            $value = "Board";
            if ($locale != 'ko') {
                $value = "Board";
            }
            XeLang::save($boardBoardTitle, $locale, $value, false);
        }

        $boardInputs = [
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'title' => $boardBoardTitle,
            'site_key' => $site_key,
            'url' => 'board',
            'description' => 'board',
            'target' => '',
            'type' => 'board@board',
            'ordering' => '3',
            'activated' => '1',
        ];
        $boardMenuTypeInput = [
            'page_title' => 'Multisite Board',
            'board_name' => 'Board',
            'site_key' => $site_key,
            'revision' => 'true',
            'division' => 'false',
        ];
        $boardItem = $menuHandler->createItem($mainMenu, $boardInputs, $boardMenuTypeInput);
        $menuHandler->setMenuItemTheme($boardItem, $desktop_theme, $desktop_theme);

        app('xe.permission')->register($menuHandler->permKeyString($boardItem), new Grant, $site_key);

        /** @var SkinHandler $skinHandler */
        $skinHandler = app('xe.skin');
    }

    protected function setThemeConfig($mainMenu, $desktop_theme, $mobile_theme, $site_key)
    {
        $desktop_theme_parent = explode(".",$desktop_theme)[0];
        $mobile_theme_parent = explode(".",$mobile_theme)[0];
        //duplicate from default site theme
//        $desktop_val = \DB::table('config')->where('site_key','default')->where('name','theme.settings.' . $desktop_theme)->first();
//        $mobile_val = \DB::table('config')->where('site_key','default')->where('name','theme.settings.' . $mobile_theme)->first();
//        $desktop_val->site_key = $site_key;
//        $mobile_val->site_key = $site_key;
//        dd($mobile_val);

        $themes = array_unique([$desktop_theme_parent, $mobile_theme_parent]);
        $theme_plugins = array();
        foreach($themes as $theme){
            \DB::table('config')->insert([
                ['site_key' => $site_key, 'name' => 'theme.settings.' . $theme , 'vars' => '[]'],
            ]);
            //theme/together@together.0
            $plugin_name = substr($theme,strlen('theme/'),strpos($theme,"@") - strlen('theme/'));
            $theme_plugins[$plugin_name] = true;
        }

        /** @var ThemeHandler $themeHandler */
        $themeHandler = app('xe.theme');
        $themeHandler->setThemeConfig($desktop_theme, 'mainMenu', $mainMenu);
        $themeHandler->setThemeConfig($mobile_theme, 'mainMenu', $mainMenu);
        return $theme_plugins;
    }


    /**
     * site default config setup
     *
     * @param Menu   $mainMenu menu
     * @param string $homeId   home instance id
     * @return void
     */
    public function siteDefaultConfig($mainMenu, $homeId, $site_key, $site_title)
    {
        /**
         * @var $configManager ConfigManager
         */
        $configManager = app('xe.config');
        //site.{site_key} 설정은 무조건 default라더라..
        $configEntity = $configManager->get('site.' . $site_key, true, 'default');
        $configEntity->set('defaultMenu', $mainMenu->id);
        $configEntity->set('homeInstance', $homeId);
        $configEntity->set('site_title', $site_title);

        $configManager->modify($configEntity);
    }


    public function pluginActivateForMultisite($plugin_id){
        switch($plugin_id){
            case "comment" :
                // put translation source
                XeLang::putFromLangDataSource('comment', base_path('plugins/comment/langs/lang.php'));

                XeDB::transaction(function () {
                    /** @var commentHandler $handler */
                    $handler = app(commentHandler::class);

                    $grant = new Grant();
                    $grant->set('create', [
                        Grant::RATING_TYPE => Rating::USER,
                        Grant::GROUP_TYPE => [],
                        Grant::USER_TYPE => [],
                        Grant::EXCEPT_TYPE => [],
                        Grant::VGROUP_TYPE => []
                    ]);
                    $grant->set('manage', [
                        Grant::RATING_TYPE => Rating::MANAGER,
                        Grant::GROUP_TYPE => [],
                        Grant::USER_TYPE => [],
                        Grant::EXCEPT_TYPE => [],
                        Grant::VGROUP_TYPE => []
                    ]);
                    app('xe.permission')->register($handler->getKeyForPerm(), $grant);
                    // 기본 설정
                    XeConfig::set('comment', $handler->getDefaultConfig());

                    XeToggleMenu::setActivates('comment', null, []);
                });
                break;
            case 'board' :
                BoardResources::createDefaultConfig();
                BoardResources::createShareConfig();
                BoardResources::putLang();
                break;
        } // endswitch
    }
}
