<?php
namespace Amuz\XePlugin\Multisite\Controllers;

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
use Xpressengine\Skin\SkinHandler;
use Xpressengine\Support\Migration;
use Xpressengine\Theme\ThemeHandler;
use Xpressengine\User\Rating;
use Xpressengine\Media\MediaManager;
use Xpressengine\Storage\Storage;

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

        $keyword = $request->get('query');
        $Sites = Site::whereHas('domains', function ($query) use ($keyword){
            $query->where('domain','like','%'.$keyword.'%');
        })->orWhereHas('configSEO', function($query) use ($keyword){
            $query->where('vars','like','%'.$keyword.'%');
        })->get();

        return XePresenter::make('multisite::views.settings.index', [
            'title' => $title,
            'Sites' => $Sites,
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

    public function edit($site_key, $mode = null){
        $title = xe_trans('multisite::multisite');

        $Site = Site::find($site_key);
        if($Site == null){
            return redirect()->route('settings.multisite.index')->with('alert', [
                    'type' => 'failed', 'message' => xe_trans('multisite::siteHostNoneExists')]
            );
        }

        $infos = $this->getSiteInfos();

        $defaultSite = Site::find('default');
        return XePresenter::make('multisite::views.settings.edit', compact('title','site_key', 'mode', 'Site', 'infos', 'defaultSite'));
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

        return redirect()->route('settings.multisite.edit',['site_key' => $site_key])->with('alert', [
            'type' => 'success', 'message' => '성공적으로 업데이트 되었습니다.'
        ]);
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
                ['site_key' => $site_key, 'name' => 'editor', 'grants' => '{"html":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"tool":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"upload":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"download":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]}}'],
                ['site_key' => $site_key, 'name' => 'widgetbox', 'grants' => '[]'],
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


        \XeDB::beginTransaction();
        try {

            if($args['target_site_key'] != 0){
                //이동 할 컨텐츠 준비
                $target_contents = array();
                if(isset($args['contents'])){
                    foreach($args['contents'] as $target_module => $on) $target_contents[] = $target_module;
                }

                //TODO - 이동처리 (일단 문서만)
                //DB::table('instance_route')->whereIn('module',join($target_contents))->where('site_key',$args['site_key'])->update('site_key',$args['target_site_key']);
            }

            //삭제시작
            \XeDB::table('site')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('site_domains')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('menu')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('menu_item')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('permissions')->where('site_key',$args['site_key'])->delete();
            \XeDB::table('instance_route')->where('site_key',$args['site_key'])->delete();

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
                'name' => '기본 회원',
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
