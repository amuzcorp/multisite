<?php
namespace Amuz\XePlugin\Multisite\Middleware;
use Auth;
use Closure;
use XeSite;

class SetSettingMenusMiddleware
{

    public function __construct()
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //관리자메뉴 등 등록
        if(XeSite::getCurrentSiteKey() == 'default'){
            $this->registerSettingsMenus();
        }
        $this->changeSettingsMenus();

        return $next($request);
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
}
