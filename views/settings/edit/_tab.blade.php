<?php
use Xpressengine\Permission\Instance as PermissionInstance;
?>
<ul class="nav nav-tabs">
{{--    <li @if($_active == null) class="active" @endif><a href="{{ route('settings.multisite.edit', ['site_key' => $site_key]) }}">{{xe_trans('xe::defaultSettings')}}</a></li>--}}
    <li @if($_active == null || $_active == 'default') class="active" @endif><a href="{{ route($target_route, ['site_key' => $site_key]) }}">기본정보</a></li>
    <li @if($_active == 'meta') class="active" @endif><a href="{{ route($target_route, ['site_key' => $site_key, 'mode' => 'meta']) }}">메타정보</a></li>
    <li @if($_active == 'domains') class="active" @endif><a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'domains']) }}">{{xe_trans('xe::domain')}}</a></li>
    <li @if($_active == 'users') class="active" @endif><a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'users']) }}">{{xe_trans('xe::user')}}</a></li>


        <li @if($_active == 'managers') class="active" @endif><a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'managers']) }}">{{xe_trans('xe::permission')}}</a></li>
    {{--    권한, 관리자메뉴, 삭제 등은 소유자만 처리할 수 있도록 함--}}
    @if(Gate::allows('access', new PermissionInstance('settings.multisite.owner')))
        <li @if($_active == 'menu') class="active" @endif><a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'menu']) }}">{{xe_trans('xe::settings')}} {{xe_trans('xe::menu')}}</a></li>
    @endif
        @if($site_key != 'default')
        <li @if($_active == 'delete') class="active" @endif><a class="text-danger" href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'delete']) }}">{{xe_trans('xe::site')}} {{xe_trans('xe::delete')}}</a></li>
        @endif
</ul>
