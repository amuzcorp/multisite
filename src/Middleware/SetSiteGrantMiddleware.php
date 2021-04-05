<?php
namespace Amuz\XePlugin\Multisite\Middleware;
use Auth;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Foundation\Application;
use Xpressengine\Permission\Instance as PermissionInstance;

class SetSiteGrantMiddleware
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
     * 생성자이며, Application을 주입받는다.
     *
     * @param Application  $app  Application
     * @param GateContract $gate GateContract
     */
    public function __construct(Application $app, GateContract $gate)
    {
        $this->app = $app;
        $this->gate = $gate;
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
        $current_route = app('request')->route();
        $route_name = explode('.',$current_route->getName());

        //패스 할 라우팅들 (확인되는데로 계속 추가할 예정)
        $pass_routes = ['login','auth','lang'];
        if(in_array($route_name[0],$pass_routes)) return $next($request);

        $site_key = \XeSite::getCurrentSiteKey();
        $permissionHandler = app('xe.permission');
        $config = app('xe.config');

        $siteAccessPermission = $permissionHandler->get('settings.multisite');
        $siteManagerPermission = $permissionHandler->get('settings.multisite.manager');
        $siteOwnerPermission = $permissionHandler->get('settings.multisite.owner');

        //관리자 라우팅인경우 메뉴를 저장된 설정에 따라 바꿔서 덮어주고 각 메뉴마다의 권한을 설정 해 준다.
        if($route_name[0] == 'settings'){
            $allow = false;
            //소유자이거나 관리자인지 확인해야한다.
            if($allow == false && $siteManagerPermission != null){
                $allow = $this->gate->allows('access', new PermissionInstance('settings.multisite.manager'));
            }
            if($allow == false && $siteOwnerPermission != null){
                $allow = $this->gate->allows('access', new PermissionInstance('settings.multisite.owner'));
            }

            $action = array_get($current_route->action,'setting_menu');

            //관리자접근권한이 없으면 패스
            if($allow == false && !$this->isSuper()) return $next($request);
            else $this->setSuperUser();

            //메뉴설정이 저장된적 있는지 확인
            $setting_menu_config = $config->get('setting_menus',false,$site_key);

            $getMenu = \XeRegister::get('settings/menu');
            ksort($getMenu);
            foreach ($getMenu as $id => $item) {
                //if has permission, set grant
                $itemPermission = $permissionHandler->get('multisite.menus'.$id,$site_key);
                if($itemPermission != null){
                    //퍼미션이 설정되었는데, 권한이 없는 유저가 접근하면 메뉴에서 삭제
                    if($this->gate->denies('access', new PermissionInstance('multisite.menus.'.$id))) {
                        $item['display'] = false;
                        \XeRegister::push('settings/menu', $id, $item);
                    }

                    //권한이 설정되었는데 권한이 있고, 현재 라우트 액션과 setting_menu($id)가 같다면 임시로 슈퍼권한을 덮어줌.
                    if($action == $id && $this->gate->allows('access', new PermissionInstance('multisite.menus.'.$id))){
                        $this->setSuperUser();
                    }
                }

                //if has config, replace $item
                if($setting_menu_config != null){
                    $item_config = $config->get('setting_menus.'.$id,false,$site_key);
                    if($item_config == null) continue;

                    if(isset($item_config['is_off']) && $item_config['is_off'] == "Y") $item['display'] = false;
                    if(isset($item_config['title_lang'])) $item['title'] = $item_config['title_lang'];
                    if(isset($item_config['icon'])) $item['icon'] = $item_config['icon'];
                    if(isset($item_config['ordering'])) $item['ordering'] = $item_config['ordering'];
                    if(isset($item_config['description'])) $item['description'] = $item_config['description'];

                    \XeRegister::push('settings/menu', $id, $item);
                }
            }
        }else if(!$this->isSuper()){
            //관리자메뉴가 아니면 접근권한이 있는지 확인한다.
            $allow = false;
            if($siteAccessPermission != null) {
                $allow = $this->gate->allows('access', new PermissionInstance('settings.multisite'));
            }
            if($allow == false && $siteManagerPermission != null){
                $allow = $this->gate->allows('access', new PermissionInstance('settings.multisite.manager'));
            }
            if($allow == false && $siteOwnerPermission != null){
                $allow = $this->gate->allows('access', new PermissionInstance('settings.multisite.owner'));
            }
            if($allow == false) throw new AuthorizationException(xe_trans('xe::accessDenied'));
        }

        return $next($request);
    }

    private function isSuper(){
        return auth()->user()->getRating() == 'super';
    }

    private function setSuperUser(){
        auth()->user()->setAttribute('rating','super');
    }
}
