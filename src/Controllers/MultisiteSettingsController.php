<?php
namespace Amuz\XePlugin\Multisite\Controllers;

use Illuminate\Filesystem\Filesystem;
use XeFrontend;
use XePresenter;
use XeLang;
use Plugin;
use XeDB;

use Illuminate\Contracts\Foundation\Application;
use Amuz\XePlugin\Multisite\Models\Site;
use Amuz\XePlugin\Multisite\Models\SiteDomain;
use Amuz\XePlugin\Multisite\Models\SiteConfig;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Config\ConfigManager;
use Xpressengine\Http\Request;
use Xpressengine\Menu\MenuHandler;
use Xpressengine\Menu\Models\Menu;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Permission\Grant;
use Xpressengine\Plugins\Board\Components\Skins\Board\Blog\BlogSkin;
use Xpressengine\Plugins\Board\Components\Skins\Board\Gallery\GallerySkin;
use Xpressengine\Skin\SkinHandler;
use Xpressengine\Support\Migration;
use Xpressengine\Theme\ThemeHandler;

class MultisiteSettingsController extends BaseController
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
//        XePresenter::setSettingsSkinTargetId('multisite');
        $this->app = $app;
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

    public function edit($site_key, $mode = null){
        $title = xe_trans('multisite::multisite');

        $Site = Site::find($site_key);
        if($Site == null){
            return redirect()->route('settings.multisite.index')->with('alert', [
                    'type' => 'failed', 'message' => xe_trans('multisite::siteHostNoneExists')]
            );
        }

        $defaultSite = Site::find('default');
        return XePresenter::make('multisite::views.settings.edit', compact('title','site_key', 'mode', 'Site','defaultSite'));
    }

    public function create(){
        $Site = new Site();
        $title = xe_trans('multisite::multisite');

        $defaultSite = Site::find('default');

        return XePresenter::make('multisite::views.settings.create', compact('title','Site','defaultSite'));
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

            //사이트 테마설정

            //필수적인 상위권한만 추가
            \DB::table('permissions')->insert([
                ['site_key' => $site_key, 'name' => 'module/board@board', 'grants' => '{"create":{"rating":"user","group":[],"user":[],"except":[]},"read":{"rating":"guest","group":[],"user":[],"except":[]},"list":{"rating":"guest","group":[],"user":[],"except":[]},"manage":{"rating":"manager","group":[],"user":[],"except":[]}}'],
                ['site_key' => $site_key, 'name' => 'comment', 'grants' => '{"create":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"manage":{"rating":"manager","group":[],"user":[],"except":[],"vgroup":[]}}'],
                ['site_key' => $site_key, 'name' => 'editor', 'grants' => '{"html":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"tool":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"upload":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]},"download":{"rating":"user","group":[],"user":[],"except":[],"vgroup":[]}}'],
            ]);
            //parent configs 설정
            \DB::table('config')->insert([
                ['site_key' => 'default', 'name' => 'site.' . $site_key , 'vars' => '[]'],
            ]);

            //코어 마이그레이션 실행
            $this->runMigrations($site_key);

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
            $menuHandler->setMenuTheme($mainMenu, $request->get('theme_desktop'), $request->get('theme_mobile'), $site_key);
            app('xe.permission')->register($mainMenu->getKey(), $menuHandler->getDefaultGrant(), $site_key);

            $this->setThemeConfig($mainMenu->id, $request->get('theme_desktop'), $request->get('theme_mobile'), $site_key);

            //for together
            $this->widgetPageModuleMenuSetup($mainMenu,$site_key,$request->get('site_title'), $request->get('theme_desktop'), $request->get('theme_mobile'));
            $this->boardModuleMenuSetup($mainMenu, $site_key, $request->get('theme_desktop'), $request->get('theme_mobile'));

            $this->app['xe.site']->setCurrentSite($defaultSite);
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
        $menuHandler->setMenuItemTheme($boardItem, $desktop_theme, $desktop_theme, $site_key);

        app('xe.permission')->register($menuHandler->permKeyString($boardItem), new Grant, $site_key);

        /** @var SkinHandler $skinHandler */
        $skinHandler = app('xe.skin');
    }

    protected function setThemeConfig($mainMenu, $desktop_theme, $mobile_theme, $site_key)
    {
        //set parent Config
//        \DB::table('config')->insert([
//            ['site_key' => $site_key, 'name' => 'theme.settings' , 'vars' => '[]'],
//        ]);
        $themes = array_unique([$desktop_theme, $mobile_theme]);
        foreach($themes as $theme){
            $theme_tree = explode(".",$theme);
            \DB::table('config')->insert([
                ['site_key' => $site_key, 'name' => 'theme.settings.' . $theme_tree[0] , 'vars' => '[]'],
            ]);
        }

        /** @var ThemeHandler $themeHandler */
        $themeHandler = app('xe.theme');
        $themeHandler->setThemeConfig($desktop_theme, 'mainMenu', $mainMenu, $site_key);
        $themeHandler->setThemeConfig($mobile_theme, 'mainMenu', $mainMenu, $site_key);
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
}
