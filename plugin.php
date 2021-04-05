<?php
namespace Amuz\XePlugin\Multisite;

use Amuz\XePlugin\Multisite\Middleware\SetSiteGrantMiddleware;
use Amuz\XePlugin\Multisite\Models\SiteDomain;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\URL;
use Route;
use Xpressengine\Permission\Instance as PermissionInstance;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Site\SiteHandler;
use Xpressengine\Translation\Translator;
use XeRegister;
use XeSite;
use Schema;
use Auth;

use Amuz\XePlugin\Multisite\Components\Modules\SiteList\SiteListModule;

use Amuz\XePlugin\Multisite\Resources;
use Amuz\XePlugin\Multisite\Models\Site;
use Amuz\XePlugin\Multisite\Observers\SiteObserver;
use Amuz\XePlugin\Multisite\Migrations\SitesMigration;

class Plugin extends AbstractPlugin
{
    public function register()
    {
        //정상적인 사이트정보인지 먼저 체크
        $this->setSiteDomainInfo();

        //관리자메뉴 등 등록
        if(XeSite::getCurrentSiteKey() == 'default'){
            $this->registerSettingsMenus();
        }
        $this->changeSettingsMenus();

        //메타정보 등록
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
            $this->registerSettingsRoute();
        }
        $this->registerSitesSettingsRoute();

        //setMiddleWare
        app('router')->pushMiddlewareToGroup('web', SetSiteGrantMiddleware::class);
    }

    public static function putLang()
    {
        /** @var Translator $trans */
        $trans = app('xe.translator');
        $trans->putFromLangDataSource('multisite', base_path('plugins/multisite/langs/lang.php'));
    }

    public function setSiteDomainInfo(){
        $request = request();
        $current_domain = $request->getHttpHost();
        $current_path = $request->getRequestUri();

        //등록안된 도메인이면 404 띄움.
        $domain = SiteDomain::find($current_domain);
        if(is_null($domain)) abort(404);

        //같은사이트의 대표도메인을 가져옴
        $featured_domain = SiteDomain::where('is_featured','Y')->where('site_key',$domain->site_key)->first();
        $featured_domain_scheme = $featured_domain->is_ssl == "Y" ? 'https://' : 'http://';

        //대표 도메인이 아닌데, 주요 도메인으로 리다이렉트가 걸려있는 경우
        if($domain->is_featured != "Y" && $domain->is_redirect_to_featured == "Y"){
            $redirect_to = $featured_domain_scheme.$featured_domain->domain.$current_path;
            header("Location: ".$redirect_to);
            exit();
        }

        //scheme set
        $scheme = $domain->is_ssl == "Y" ? 'https://' : 'http://';
        $cur_scheme = $this->checkSSL() ?  'https://' : 'http://';

        //현재 접속한 도메인의 스키마가 맞지 않는경우
        //CORS에서 로드밸런서의 https여부를 검증해주므로 여기선 안함
        if($scheme != $cur_scheme){
            $redirect_to = $scheme.$domain->domain.$current_path;
            header("Location: ".$redirect_to);
            exit();
        }

        //대표도메인이 아닌경우 home instance 변경해서 호출
        if($domain->is_featured != "Y" && $domain->index_instance != null){
            intercept(
                SiteHandler::class . '@getHomeInstanceId',
                'multisite::replaceHomeInstance',
                function ($func, $siteKey = null) use ($domain){
                    return $domain->index_instance;
                }
            );
        }

        //접속한 도메인의 사이트키로 새 사이트모델을 세팅 하고 끝냄
        $curSite = Site::find($domain->site_key);
        $site_handler = app('xe.site');
        $site_handler->setCurrentSite($curSite);
    }

    public function checkSSL(){
        $is_ssl = false;
        //SSL Load balancing fix
        if ((isset($_ENV["HTTPS"]) && ("on" == $_ENV["HTTPS"]))
            || (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && (strpos($_SERVER["HTTP_X_FORWARDED_SSL"], "1") !== false))
            || (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && (strpos($_SERVER["HTTP_X_FORWARDED_SSL"], "on") !== false))
            || (isset($_SERVER["HTTP_CF_VISITOR"]) && (strpos($_SERVER["HTTP_CF_VISITOR"], "https") !== false))
            || (isset($_SERVER["HTTP_CLOUDFRONT_FORWARDED_PROTO"]) && (strpos($_SERVER["HTTP_CLOUDFRONT_FORWARDED_PROTO"], "https") !== false))
            || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && (strpos($_SERVER["HTTP_X_FORWARDED_PROTO"], "https") !== false))
            || (isset($_SERVER["HTTP_X_PROTO"]) && (strpos($_SERVER["HTTP_X_PROTO"], "SSL") !== false))
        ) {
            $_SERVER["HTTPS"] = "on";
            URL::forceScheme('https');
            $is_ssl = true;
        }else if(request()->isSecure()){
            $is_ssl = true;
        }
        return $is_ssl;
    }


    /**
     * Register Plugin Settings Menus
     *
     * @return void
     */
    private function registerSettingsMenus(){
        \XeRegister::push('settings/menu', 'sites', [
            'title' => '멀티사이트',
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
     * Replace Plugin Settings Menus
     *
     * @return void
     */
    private function changeSettingsMenus(){
        \XeRegister::push('settings/menu', 'sitemap', [
            'title' => '사이트',
            'display' => true,
            'description' => 'xe::siteMapDescription',
            'ordering' => 2000
        ]);
        \XeRegister::push('settings/menu', 'sitemap.site', [
            'title' => '사이트 설정',
            'display' => true,
            'description' => 'xe::siteMapDescription',
            'ordering' => 2000
        ]);
        //기본설정 이동으로 기존설정 제거
        \XeRegister::push('settings/menu', 'setting.default', [
            'display' => false,
        ]);
        \XeRegister::push('settings/menu', 'setting.permission', [
            'display' => false,
        ]);
    }

    /**
     * Register Plugin Settings Route
     *
     * @return void
     */
    protected function registerSitesSettingsRoute()
    {
      Route::settings(static::getId(), function() {

          Route::get('/mysite/{mode?}', [
              'as' => 'settings.multisite.mysite',
              'uses' => 'MultisiteSettingsController@mysite',
              'settings_menu' => 'sitemap.site'
          ]);

          Route::get('/edit/{site_key}/{mode?}', [
              'as' => 'settings.multisite.edit',
              'uses' => 'MultisiteSettingsController@edit',
          ]);

          Route::post('/update/{site_key}', [
              'as' => 'settings.multisite.update',
              'uses' => 'MultisiteSettingsController@update',
          ]);

          Route::post('/createDomain/{site_key}/{domain?}', [
              'as' => 'settings.multisite.create.domain',
              'uses' => 'MultisiteSettingsController@createDomain',
          ]);
          Route::delete('/deleteDomain/{site_key}', [
              'as' => 'settings.multisite.delete.domain',
              'uses' => 'MultisiteSettingsController@deleteDomain',
          ]);
          Route::post('/updateDefaultDomain/{site_key}', [
              'as' => 'settings.multisite.update.domain.default',
              'uses' => 'MultisiteSettingsController@updateDefaultDomain',
          ]);

          Route::post('/addSiteUser/{site_key}', [
              'as' => 'settings.multisite.add.user',
              'uses' => 'MultisiteSettingsController@addSiteUser',
          ]);
          Route::post('/updateSettingMenus/{site_key}/{config_id}', [
              'as' => 'settings.multisite.update.setting_menus',
              'uses' => 'MultisiteSettingsController@updateSettingMenusConfig',
          ]);
          Route::post('/updateSitePermissions/{site_key}/{permission_id}', [
              'as' => 'settings.multisite.update.permissions',
              'uses' => 'MultisiteSettingsController@updateSitePermissions',
          ]);
      },['namespace' => 'Amuz\XePlugin\Multisite\Controllers']);
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

        Route::get('moduleSetting/{site_key}', [
            'as' => 'settings.multisite.module.setting',
            'uses' => 'MultisiteSettingsController@moduleSetting'
        ]);

        Route::post('/store', [
              'as' => 'settings.multisite.store',
              'uses' => 'MultisiteSettingsController@store',
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
        //site 테이블에 타임스탬프 추가
        if(Schema::hasColumn('site', 'created_at') == false) {
            Schema::table('site', function (Blueprint $table) {
                $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'))->nullable()->comment('site created date');
                $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->nullable()->comment('site updated date');

                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        //site 상태 추가
        if(Schema::hasColumn('site', 'status') == false) {
            Schema::table('site', function (Blueprint $table) {
                $table->string('status', 20)->default('activated')->comment('site status. activated/deactivated');
                $table->index('status');
            });
        }
        if(Schema::hasColumn('user_terms', 'site_key') == false) {
            Schema::table('user_terms', function (Blueprint $table) {
                $table->string('site_key', 50)->nullable()->default('default')->comment('site key. for multi web site support.');
                $table->index('site_key');
            });
        }
        if(Schema::hasColumn('user_term_agrees', 'site_key') == false) {
            Schema::table('user_term_agrees', function (Blueprint $table) {
                $table->string('site_key', 50)->nullable()->default('default')->comment('site key. for multi web site support.');
                $table->index('site_key');
            });
        }
        if(Schema::hasColumn('widgetbox', 'site_key') == false) {
            Schema::table('widgetbox', function (Blueprint $table) {
                $table->dropPrimary('id');
                $table->primary(['site_key','id']);

                $table->string('site_key', 50)->nullable()->default('default')->comment('site key. for multi web site support.');
                $table->index('site_key');
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
        if(Schema::hasColumn('site', 'status') == false) return false;
        if(Schema::hasColumn('user_terms', 'site_key') == false) return false;
        if(Schema::hasColumn('user_term_agrees', 'site_key') == false) return false;
        if(Schema::hasColumn('widgetbox', 'site_key') == false) return false;

        return $isLatest;
    }


    public function uninstall()
    {
        (new SitesMigration())->down();
    }
}
