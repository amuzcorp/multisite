<?php
namespace Amuz\XePlugin\Multisite\Middleware;
use App\Http\Middleware\LangPreprocessor;
use Auth;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Foundation\Application;
use Xpressengine\Http\Request;
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
     * @param  Request $request
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

        $permissionHandler = app('xe.permission');

        //null이면 설정이 없는것, true면 허용, false면 거절
        //설정이 없어도 최고관리자면 owner 권한을 준다.
        if($this->isSuper() || $this->isManager()) $allowOwner = true;
        else $allowOwner = ($permissionHandler->get('settings.multisite.owner') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite.owner'));

        //매니저 권한
        $allowManager = ($permissionHandler->get('settings.multisite.manager') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite.manager'));

        //오너거나 매니저면 엑세스는 그냥 받는다.
        $allowAccess = ($permissionHandler->get('settings.multisite') == null) ? null : $this->gate->allows('access', new PermissionInstance('settings.multisite'));
        if($allowAccess != null && ($allowOwner || $allowManager)) $allowAccess = true;

        //액세스설정이 저장된적이 있으면
        if($allowAccess != null){
            if($allowAccess == false) throw new AuthorizationException(xe_trans('xe::accessDenied'));
            //사용자단에서 관리권한을 쓸 일이 있을수있어서 권한 같이 설정함
            if($allowManager) $this->setManager();
            if($allowOwner) $this->setSuperUser();
        }

        //Lang Preprocessor를 실행시켜준다.
        $langPreProcessor = new LangPreprocessor($this->app);
        return $langPreProcessor->handle($request,$next);
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
