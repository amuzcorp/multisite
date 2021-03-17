<ul class="nav nav-tabs">
    <li @if($_active == null) class="active" @endif><a href="{{ route('settings.multisite.edit', ['site_key' => $site_key]) }}">{{xe_trans('xe::defaultSettings')}}</a></li>
    <li @if($_active == 'meta') class="active" @endif><a href="{{ route('settings.multisite.edit', ['site_key' => $site_key, 'mode' => 'meta']) }}">메타정보</a></li>
    <li @if($_active == 'domains') class="active" @endif><a href="{{ route('settings.multisite.edit', ['site_key' => $site_key, 'mode'=>'domains']) }}">{{xe_trans('xe::domain')}}</a></li>
    <li @if($_active == 'managers') class="active" @endif><a href="{{ route('settings.multisite.edit', ['site_key' => $site_key, 'mode'=>'managers']) }}">{{xe_trans('xe::owner')}}</a></li>
    <li @if($_active == 'delete') class="active" @endif><a class="text-danger" href="{{ route('settings.multisite.edit', ['site_key' => $site_key, 'mode'=>'delete']) }}">{{xe_trans('xe::site')}} {{xe_trans('xe::delete')}}</a></li>
</ul>
