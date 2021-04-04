@section('page_title')
    <h2>{{ $title }}</h2>
@stop

@section('page_description')
    <small>{{ sprintf(xe_trans('multisite::siteUpdate'), xe_trans($Site->config->get('site_title')))}}</small>
@endsection

@include('multisite::views.settings.edit._tab', ['_active' => $mode, 'site_key' => $site_key])

@switch($mode)
    @case('meta')
        @include('multisite::views.settings.edit.meta', compact('site_key', 'Site', 'defaultSite'))
    @break
    @case('domains')
        @include('multisite::views.settings.edit.domains', compact('site_key', 'Site', 'defaultSite'))
    @break
    @case('users')
        @include('multisite::views.settings.edit.users', compact('site_key', 'Site', 'defaultSite'))
    @break
    @case('managers')
        @include('multisite::views.settings.edit.managers', compact('site_key', 'Site', 'defaultSite'))
    @break
    @case('menu')
        @include('multisite::views.settings.edit.menu', compact('site_key', 'Site', 'defaultSite'))
    @break
    @case('delete')
        @include('multisite::views.settings.edit.delete', compact('site_key', 'Site', 'defaultSite'))
    @break
    @default
        @include('multisite::views.settings.edit.default', compact('site_key', 'Site', 'defaultSite'))
@endswitch
