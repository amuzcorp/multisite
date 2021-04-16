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

        if($this->gate->allows('access', new PermissionInstance('settings.multisite.manager')) || $this->gate->allows('access', new PermissionInstance('settings.multisite.owner'))){
            $this->setSuperUser();
            return $next($request);
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
