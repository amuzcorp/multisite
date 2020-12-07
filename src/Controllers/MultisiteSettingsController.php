<?php
namespace Amuz\XePlugin\Multisite\Controllers;

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

    public function create(){
        $Site = new Site();
        $title = xe_trans('multisite::multisite');

        $defaultSite = Site::find('default');

        return XePresenter::make('multisite::views.settings.create', compact('title','Site','defaultSite'));
    }

    public function store(Request $request){
        $defaultSite = Site::find('default');
        $site_key = strtolower($request->get('site_key'));
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

                ['site_key' => $site_key, 'name' => 'menu', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'site', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'plugin', 'vars' => '{"list":{"board":{"status":"activated","version":"1.0.14"},"together":{"status":"activated","version":"1.0.5"},"claim":{"status":"activated","version":"1.0.3"},"ckeditor":{"status":"activated","version":"1.0.9"},"comment":{"status":"activated","version":"1.0.5"},"page":{"status":"activated","version":"1.0.3"},"news_client":{"status":"activated","version":"1.0.4"},"widget_page":{"status":"activated","version":"1.0.2"},"banner":{"status":"activated","version":"1.0.5"},"multisite":{"status":"activated","version":"1.0.0"}}}'],
                ['site_key' => $site_key, 'name' => 'settings', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'counter', 'vars' => '{}'],

                ['site_key' => $site_key, 'name' => 'media_library', 'vars' => '{"container":{}, "file":{"dimensions":{"MAX":{"width":4000, "height":4000}}}}'],
                ['site_key' => $site_key, 'name' => 'document', 'vars' => '{"instanceId":0,"instanceName":0,"division":false,"revision":false,"comment":true,"assent":true,"nonmember":false,"reply":false}'],
                ['site_key' => $site_key, 'name' => 'comment', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'comment_map', 'vars' => '[]'],

                ['site_key' => $site_key, 'name' => 'module/board@board', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'skins', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'skins.selected', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'skins.configs', 'vars' => '[]'],

                ['site_key' => $site_key, 'name' => 'theme', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'theme.settings', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'theme.settings.theme/together@together', 'vars' => '[]'],

                ['site_key' => $site_key, 'name' => 'user', 'vars' => '[]'],
                ['site_key' => $site_key, 'name' => 'user.common', 'vars' => '{"useCaptcha":false,"webmasterName":"webmaster","webmasterEmail":"webmaster@domain.com"}'],
                ['site_key' => $site_key, 'name' => 'user.register', 'vars' => '{"secureLevel":"low","joinable":true,"register_process":"activated","term_agree_type":"pre","display_name_unique":false,"use_display_name":true,"password_rules":"min:6|alpha|numeric|special_char"}'],
                ['site_key' => $site_key, 'name' => 'toggleMenu@user', 'vars' => '{"activate":["user\/toggleMenu\/xpressengine@profile","user\/toggleMenu\/xpressengine@manage"]}'],

                ['site_key' => $site_key, 'name' => 'dynamicField','vars' => '{"required":false,"sortable":false,"searchable":false,"use":true,"tableMethod":false}']
            ]);

            // set site default theme
            $theme = ['desktop' => 'theme/together@together.0', 'mobile' => 'theme/together@together.0'];
            app('xe.theme')->setSiteTheme($theme);

            // 기본 메뉴 추가 (main) 추가.
            /** @var MenuHandler $menuHandler */
            $menuHandler = app('xe.menu');

            // 기본 메뉴 config  설정 (theme)
            $defaultMenuTheme = 'theme/together@together.0';

            $mainMenu = $menuHandler->createMenu([
                'title' => 'Main Menu',
                'description' => 'Main Menu',
                'site_key' => $site_key
            ]);
            $menuHandler->setMenuTheme($mainMenu, $defaultMenuTheme, $defaultMenuTheme, $site_key);
            app('xe.permission')->register($mainMenu->getKey(), $menuHandler->getDefaultGrant(), $site_key);

            $this->setThemeConfig($mainMenu->id, $site_key);

            //for together
            $this->widgetPageModuleMenuSetup($mainMenu,$site_key,$request->get('site_title'));
            $this->boardModuleMenuSetup($mainMenu, $site_key);

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


    public function widgetPageModuleMenuSetup($mainMenu,$site_key,$site_title)
    {
        $theme = 'theme/together@together.1';

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

        $menuHandler->setMenuItemTheme($item, $theme, $theme, $site_key);
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
    public function boardModuleMenuSetup($mainMenu, $site_key)
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
            'page_title' => 'XpressEngine3 Board',
            'board_name' => 'Board',
            'site_key' => $site_key,
            'revision' => 'true',
            'division' => 'false',
        ];
        $boardItem = $menuHandler->createItem($mainMenu, $boardInputs, $boardMenuTypeInput);
        $menuHandler->setMenuItemTheme($boardItem, null, null, $site_key);

        app('xe.permission')->register($menuHandler->permKeyString($boardItem), new Grant, $site_key);

        /** @var SkinHandler $skinHandler */
        $skinHandler = app('xe.skin');
    }

    protected function setThemeConfig($mainMenu, $site_key)
    {
        /** @var ThemeHandler $themeHandler */
        $themeHandler = app('xe.theme');
        $themeHandler->setThemeConfig('theme/together@together.0', 'mainMenu', $mainMenu, $site_key);
        $themeHandler->setThemeConfig('theme/together@together.1', 'mainMenu', $mainMenu, $site_key);
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
