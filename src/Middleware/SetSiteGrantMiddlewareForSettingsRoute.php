<?php
namespace Amuz\XePlugin\Multisite\Middleware;
use Auth;
use Closure;
use Xpressengine\Http\Request;
use Xpressengine\Settings\SettingsMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Foundation\Application;
use Xpressengine\Permission\Instance as PermissionInstance;

class SetSiteGrantMiddlewareForSettingsRoute
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var GateContract
     */
    protected $gate;

    /**
     * @var Config Manager
     */
    protected $config;

    /**
     * 생성자이며, Application을 주입받는다.
     *
     * @param Application  $app  Application
     * @param GateContract $gate GateContract
     */
    public function __construct(Application $app, GateContract $gate)
    {
        $this->app = $app;
        $this->gate = $gate;
        $this->config = app('xe.config');
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $current_route = app('request')->route();

        $site_key = \XeSite::getCurrentSiteKey();
        $permissionHandler = app('xe.permission');

        //null이면 설정이 없는것, true면 허용, false면 거절
        //설정이 없어도 최고관리자면 owner 권한을 준다.
        if($this->isSuper()) $allowOwner = true;
        else $allowOwner = ($permissionHandler->get('settings.multisite.owner') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite.owner'));

        //매니저 권한
        $allowManager = ($permissionHandler->get('settings.multisite.manager') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite.manager'));

        //메뉴설정이 저장된적 있는지 확인
        $setting_menu_config = $this->config->get('setting_menus',false,$site_key);

        //현재 열린 라우팅 액션 미리 확인
        $action = array_get($current_route->action,'setting_menu');

        //소유자 권한이 있으면 임시로 최고관리자 등급을 적용해주고,
        //매니저 권한이 있으면 임시로 관리자 등급을 적용해준다.
        //(어차피 페이지접근시에는 최고등급이 필요해서 최종적으로 최고관리자 등급을 적용받지만, 메뉴권한 체크를위해서 먼저 이걸적용받는다.)
        if($allowOwner === true) $this->setSuperUser();
        if($allowManager === true) $this->setManager();

        $getMenu = \XeRegister::get('settings/menu');
        ksort($getMenu);

        foreach ($getMenu as $id => $item) {
            //소유자는 아이템 리플레이스만 함
            if($allowOwner == true && $setting_menu_config != null){
                $this->replaceSettingMenuItem($id,$site_key);
            }else{
                //해당 메뉴아이템의 권한이 저장된적 있으면
                if($permissionHandler->get('multisite.menus.'.$id,$site_key) != null){
                    //퍼미션이 설정되었는데, 권한이 없는 유저가 접근하면 메뉴에서 삭제
                    if($this->gate->denies('access', new PermissionInstance('multisite.menus.'.$id))) {
                        $item['display'] = false;
                        \XeRegister::push('settings/menu', $id, $item);
                    //권한이 있는유저면 메뉴 아이콘, 정보 등 설정에서 대체
                    }else if($setting_menu_config != null){
                        $this->replaceSettingMenuItem($id,$site_key);
                    }

                    //권한이 없고, 현재 라우트 액션과 setting_menu($id)가 같다면 접근거부
                    if($action == $id && $this->gate->denies('access', new PermissionInstance('multisite.menus.'.$id))){
                        throw new AuthorizationException(xe_trans('xe::accessDenied'));
                    }
                //권한이 저장된 적이 없으면 그냥 리플레이스
                }else if($setting_menu_config != null){
                    $this->replaceSettingMenuItem($id,$site_key);
                }
            }
        }

        //메뉴세팅이 끝나고나면 소유자나 관리자에게 슈퍼권한을 준다
        if($allowManager === true || $allowOwner === true) $this->setSuperUser();

        //기존 순정XE 설정미들웨어를 실행시켜준다.
        $settingsMiddleware = new SettingsMiddleware($this->app, $this->gate);
        return $settingsMiddleware->handle($request,$next);
    }

    private function replaceSettingMenuItem($id,$site_key){
        $item_config = $this->config->getVal('setting_menus.'.$id,null,true,$site_key);
        if($item_config == null || array_get($item_config , 'deleted_plugin', "N") === "Y") return;

        $item_config['display'] = array_get($item_config,'is_off','N') == "Y" ? false : true;
        $item_config['title'] = array_get($item_config,'title_lang',null) == null ? $item_config['title'] : $item_config['title_lang'];

        \XeRegister::push('settings/menu', $id, $item_config);
    }

    private function isManager(){
        return auth()->user()->getRating() == 'manager';
    }

    private function isSuper(){
        return auth()->user()->getRating() == 'super';
    }

    private function setManager(){
        auth()->user()->setAttribute('rating','manager');
    }
    private function setSuperUser(){
        auth()->user()->setAttribute('rating','super');
    }
}
