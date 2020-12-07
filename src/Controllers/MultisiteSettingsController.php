<?php
namespace Amuz\XePlugin\Multisite\Controllers;

use XeFrontend;
use XePresenter;
use XeLang;
use Plugin;
use XeDB;
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
     * ManagerController constructor.
     */
    public function __construct()
    {
//        XePresenter::setSettingsSkinTargetId('multisite');
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

            $Site = Site::find($site_key);

            //도메인 객체 붙이기
            $Domain = new SiteDomain();
            $Domain->fill([
                'domain' => sprintf("%s.%s",$site_key,$defaultSite->FeaturedDomain->first()->domain),
                'is_featured' => 'Y'
            ]);
            $Domain->Site()->associate($Site);
            $Domain->save();

            //홈 메뉴 설정
            \DB::table('config')->insert(['site_key' => $site_key, 'name' => 'menu', 'vars' => '[]']);
            \DB::table('config')->insert(['site_key' => $site_key, 'name' => 'site', 'vars' => '[]']);
            \DB::table('config')->insert(['site_key' => 'default', 'name' => 'site.' . $site_key , 'vars' => '[]']);

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
            $menuHandler->setMenuTheme($mainMenu, $defaultMenuTheme, $defaultMenuTheme);
            app('xe.permission')->register($mainMenu->getKey(), $menuHandler->getDefaultGrant(), $site_key);

            $this->setThemeConfig($mainMenu->id);

            //for together
            $this->widgetPageModuleMenuSetup($mainMenu,$site_key,$request->get('site_title'));
            $this->boardModuleMenuSetup($mainMenu, $site_key);

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
            $value = "홈";
            if ($locale != 'ko') {
                $value = "Home";
            }
            XeLang::save($menuTitle, $locale, $value, false);
        }

        $inputs = [
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'title' => $menuTitle,
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

        $menuHandler->setMenuItemTheme($item, $theme, $theme);
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
            'site_key' => 'default',
            'revision' => 'true',
            'division' => 'false',
        ];
        $boardItem = $menuHandler->createItem($mainMenu, $boardInputs, $boardMenuTypeInput);
        $menuHandler->setMenuItemTheme($boardItem, null, null);

        app('xe.permission')->register($menuHandler->permKeyString($boardItem), new Grant, $site_key);

        /** @var SkinHandler $skinHandler */
        $skinHandler = app('xe.skin');
    }

    protected function setThemeConfig($mainMenu)
    {
        /** @var ThemeHandler $themeHandler */
        $themeHandler = app('xe.theme');
        $themeHandler->setThemeConfig('theme/together@together.0', 'mainMenu', $mainMenu);
        $themeHandler->setThemeConfig('theme/together@together.1', 'mainMenu', $mainMenu);
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
        $configEntity = $configManager->get('site.' . $site_key, true);
        $configEntity->set('defaultMenu', $mainMenu->id);
        $configEntity->set('homeInstance', $homeId);
        $configEntity->set('site_title', $site_title);

        $configManager->modify($configEntity);
    }
}
