<?php
namespace Amuz\XePlugin\Multisite;

use Route;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Translation\Translator;
use XeRegister;

use Amuz\XePlugin\Multisite\Models\Site;
use Amuz\XePlugin\Multisite\Observers\SiteObserver;
use Amuz\XePlugin\Multisite\Migrations\SitesMigration;

class Plugin extends AbstractPlugin
{
    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        Site::observe(SiteObserver::class);

        $this->putLang(); //요건나중에 update로 옮길것

        // implement code
        $this->route();
        $this->registerSitesPermissions();
        $this->registerSettingsMenus();
        $this->registerSettingsRoute();
    }

    public static function putLang()
    {
        /** @var Translator $trans */
        $trans = app('xe.translator');
        $trans->putFromLangDataSource('multisite', base_path('plugins/multisite/langs/lang.php'));
    }

    /**
     * Register the settings permission.
     *
     * @return void
     */
    private function registerSitesPermissions(){
        $permissions = [
            'sites.list' => [
                'title' => xe_trans('multisite::accessSiteList'),
                'tab' => xe_trans('multisite::multisite')
            ],
            'sites.edit' => [
                'title' => xe_trans('multisite::editSiteInfo'),
                'tab' => xe_trans('multisite::multisite')
            ]
        ];
        foreach ($permissions as $id => $permission) {
            \XeRegister::push('settings/permission', $id, $permission);
        }
    }


    /**
     * Register Plugin Settings Menus
     *
     * @return void
     */
    private function registerSettingsMenus(){
        \XeRegister::push('settings/menu', 'sites', [
            'title' => '사이트 관리',
            'description' => '사이트를 생성하고 관리합니다.',
            'display' => true,
            'ordering' => 200
        ]);
        \XeRegister::push('settings/menu', 'sites.index', [
            'title' => '사이트 목록',
            'description' => '생성된 사이트목록을 열람합니다.',
            'display' => true,
            'ordering' => 200
        ]);
    }

    /**
     * Register Plugin Settings Route
     *
     * @return void
     */
    protected function registerSettingsRoute()
    {
        Route::settings(static::getId(), function() {
            Route::get('/', [
                'as' => 'settings.multisite.index',
                'uses' => 'MultisiteSettingsController@index',
                'settings_menu' => 'sites.index'
            ]);
      },['namespace' => 'Amuz\XePlugin\Multisite\Controllers']);
    }

    protected function route()
    {
        // implement code

        Route::fixed(
            $this->getId(),
            function () {
                Route::get('/', [
                    'as' => 'multisite::index','uses' => 'Amuz\XePlugin\Multisite\Controller@index'
                ]);
            }
        );

    }

    /**
     * 플러그인이 활성화될 때 실행할 코드를 여기에 작성한다.
     *
     * @param string|null $installedVersion 현재 XpressEngine에 설치된 플러그인의 버전정보
     *
     * @return void
     */
    public function activate($installedVersion = null)
    {
        // implement code
//        (new SitesMigration())->up();
    }

    /**
     * 플러그인을 설치한다. 플러그인이 설치될 때 실행할 코드를 여기에 작성한다
     *
     * @return void
     */
    public function install()
    {
        // implement code
        (new SitesMigration())->up();
    }

    /**
     * 해당 플러그인이 설치된 상태라면 true, 설치되어있지 않다면 false를 반환한다.
     * 이 메소드를 구현하지 않았다면 기본적으로 설치된 상태(true)를 반환한다.
     *
     * @return boolean 플러그인의 설치 유무
     */
    public function checkInstalled()
    {
        // implement code
        return parent::checkInstalled();
    }

    /**
     * 플러그인을 업데이트한다.
     *
     * @return void
     */
    public function update()
    {
        // implement code
    }

    /**
     * 해당 플러그인이 최신 상태로 업데이트가 된 상태라면 true, 업데이트가 필요한 상태라면 false를 반환함.
     * 이 메소드를 구현하지 않았다면 기본적으로 최신업데이트 상태임(true)을 반환함.
     *
     * @return boolean 플러그인의 설치 유무,
     */
    public function checkUpdated()
    {
        // implement code

        return parent::checkUpdated();
    }


    public function uninstall()
    {
        (new SitesMigration())->down();
    }
}
