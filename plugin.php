<?php
namespace Amuz\XePlugin\Multisite;

use Illuminate\Database\Schema\Blueprint;
use Route;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Translation\Translator;
use XeRegister;
use XeSite;
use Schema;

use Amuz\XePlugin\Multisite\Components\Modules\SiteList\SiteListModule;

use Amuz\XePlugin\Multisite\Resources;
use Amuz\XePlugin\Multisite\Models\Site;
use Amuz\XePlugin\Multisite\Observers\SiteObserver;
use Amuz\XePlugin\Multisite\Migrations\SitesMigration;

class Plugin extends AbstractPlugin
{
    public function register()
    {
        Resources::setSiteInfo();
    }
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

        if(XeSite::getCurrentSiteKey() == 'default'){
            $this->registerSitesPermissions();
            $this->registerSettingsMenus();
            $this->registerSettingsRoute();
        }else{
            //remove default admin menu
            \XeRegister::push('settings/menu','extension',['display'=>false]);
        }
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
            'sites' => [
                'title' => xe_trans('multisite::multisite'),
                'tab' => xe_trans('multisite::multisite')
            ],
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
            'ordering' => 100
        ]);
        \XeRegister::push('settings/menu', 'sites.create', [
            'title' => '새 사이트 추가',
            'description' => '사이트를 추가하고 편집합니다.',
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
        Route::get('/create', [
          'as' => 'settings.multisite.create',
          'uses' => 'MultisiteSettingsController@create',
          'settings_menu' => 'sites.create'
        ]);

        Route::get('/edit/{site_key}/{mode?}', [
          'as' => 'settings.multisite.edit',
          'uses' => 'MultisiteSettingsController@edit',
        ]);

        Route::get('moduleSetting/{site_key}', [
            'as' => 'settings.multisite.module.setting',
            'uses' => 'MultisiteSettingsController@moduleSetting'
        ]);

        Route::post('/store', [
              'as' => 'settings.multisite.store',
              'uses' => 'MultisiteSettingsController@store',
        ]);
        Route::post('/update/{site_key}', [
              'as' => 'settings.multisite.update',
              'uses' => 'MultisiteSettingsController@update',
        ]);
        Route::post('/destroy', [
              'as' => 'settings.multisite.destroy',
              'uses' => 'MultisiteSettingsController@destroy',
        ]);
      },['namespace' => 'Amuz\XePlugin\Multisite\Controllers']);

      //set sitelist Module Routes
        Route::settings(SiteListModule::getId(), function () {
            Route::get('edit/{pageId}', ['as' => 'manage.multisite.edit', 'uses' => 'MultisiteManageController@edit']);
            Route::post(
                'update/{pageId}',
                ['as' => 'manage.multisite.update', 'uses' => 'MultisiteManageController@update']
            );
        }, ['namespace' => 'Amuz\XePlugin\Multisite\Controllers']);
    }

    protected function route()
    {
        // implement code
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
        if(Schema::hasColumn('site', 'created_at') == false) {
            Schema::table('site', function (Blueprint $table) {
                $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'))->nullable()->comment('site created date');
                $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->nullable()->comment('site updated date');

                $table->index('created_at');
                $table->index('updated_at');
            });
        }
    }

    /**
     * 해당 플러그인이 최신 상태로 업데이트가 된 상태라면 true, 업데이트가 필요한 상태라면 false를 반환함.
     * 이 메소드를 구현하지 않았다면 기본적으로 최신업데이트 상태임(true)을 반환함.
     *
     * @return boolean 플러그인의 설치 유무,
     */
    public function checkUpdated()
    {
        $isLatest = true;

        if (parent::checkUpdated() == false) return false;
        if(Schema::hasColumn('site', 'created_at') == false) return false;

        return $isLatest;
    }


    public function uninstall()
    {
        (new SitesMigration())->down();
    }
}
