<?php
namespace Amuz\XePlugin\Multisite\Middleware;
use Auth;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Foundation\Application;
use Xpressengine\Permission\Instance as PermissionInstance;

class SetSiteGrantMiddlewareForAPI
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
        $permissionHandler = app('xe.permission');

        //null이면 설정이 없는것, true면 허용, false면 거절
        //설정이 없어도 최고관리자면 owner 권한을 준다.
        if($this->isSuper()) $allowOwner = true;
        else $allowOwner = ($permissionHandler->get('settings.multisite.owner') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite.owner'));

        //매니저 권한
        $allowManager = ($permissionHandler->get('settings.multisite.manager') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite.manager'));

        //API접근시 소유자나 관리자에게 슈퍼권한을 준다
        if($allowManager === true || $allowOwner === true) $this->setSuperUser();

        return $next($request);
    }

    private function isSuper(){
        return auth()->user()->getRating() == 'super';
    }

    private function setSuperUser(){
        auth()->user()->setAttribute('rating','super');
    }
}
