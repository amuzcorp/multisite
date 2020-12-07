@section('page_title')
    <h2>{{ xe_trans('multisite::sitesManage') }}</h2>
@stop

@section('page_description')
    <small>{{xe_trans('multisite::searchSitesCount') }}</small>
@endsection

<div class="container-fluid container-fluid--part">
    <div class="row">
        <form method="get" action="">
            <input type="hidden" name="status" value="{{\Request::get('status')}}">

            <div class="col-sm-12">
                <div class="admin-tab-info">
                    <ul class="admin-tab-info-list __status_list">
                        <li @if (Request::get('status', null) === null) class="on" @endif>
                            <a href="#" class="admin-tab-info-list__link" data-type="sale_type">{{xe_trans('xe::all')}} </a>
                        </li>
                        <li @if (Request::get('status') === 'activated') class="on" @endif>
                            <a href="#" class="admin-tab-info-list__link" data-type="sale_type" data-value="activated">{{ xe_trans('xe::active') }} </a>
                        </li>
                        <li @if (Request::get('status') === 'deactivated') class="on" @endif>
                            <a href="#" class="admin-tab-info-list__link" data-type="sale_type" data-value="deactivated">{{xe_trans('xe::deactive')}} </a>
                        </li>
                    </ul>
                </div>

                <div class="panel-group">
                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-right">
                                <div class="search-btn-group">
                                    <a href="{{ route('settings.multisite.create') }}" class="xe-btn xe-btn-install">
                                        <i class="xi-plus"></i>{{ xe_trans('xe::site') }} {{ xe_trans('xe::create') }}
                                    </a>
{{--                                    <button class="xe-btn">업데이트 목록</button>--}}
                                </div>
                            </div>
                            <div class="pull-left">
                                <div class="btn-group">
                                    <button class="btn btn-default __xe_check_all">{{ xe_trans('xe::selectAll') }}</button>
                                </div>
                                <div class="btn-group __xe_controll_btn">
                                </div>
                            </div>

                            <div class="pull-right text-align--right">
                                <div class="input-group search-group">
                                    <div class="search-input-group">
                                        <input type="text" class="form-control" placeholder="{{xe_trans('xe::enterKeyword')}}" name="query" value="{{ $keyword }}">
                                        <button class="btn-link">
                                            <span class="sr-only">{{xe_trans('xe::search')}}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul class="list-group list-plugin">
                            @foreach ($Sites as $Site)
                                <li class="list-group-item">
                                    <div class="list-group-item-checkbox">
                                        <label class="xe-label">
                                            <input type="checkbox" value="{{ $Site->site_key }}" class="__xe_checkbox">
                                            <span class="xe-input-helper"></span>
                                            <span class="xe-label-text xe-sr-only">체크박스</span>
                                        </label>
                                    </div>

                                    <div class="left-group">
                                        <span class="plugin-title">
                                            <span class="icon-wrap" style="background-image:url('{{ $Site->config->get('favicon.path') ? $Site->config->get('favicon.path') : '/assets/core/settings/img/logo.png' }}');"></span>
                                            {{ xe_trans($Site->config->get('site_title')) }}
                                        </span>
                                        <dl>
                                            <dt class="sr-only">Front Link</dt>
                                            <dd>{!! $Site->getDomainLink('방문') !!}</dd>

                                            <dt class="sr-only">Back Link</dt>
                                            <dd>{!! $Site->getDomainLink('대시보드',"settings") !!}</dd>
                                            <dt class="sr-only">Domain</dt>
                                            <dd>대표 도메인 {{ $Site->FeaturedDomain->first()->domain }} (외 {{ $Site->Domains->count() - 1 }} 연결)</dd>
                                        </dl>
                                        <p class="ellipsis"></p>
                                    </div>

                                    <div class="btn-right form-inline">
                                        <a href="{!! $Site->getDomainLink(false,"settings/setting") !!}" target="_blank" class="xe-btn xe-btn-positive-outline" data-site-key="{{ $Site->site_key }}">{{ xe_trans('xe::defaultSettings') }} {{ xe_trans('xe::modify') }}</a>
                                        <a href="" class="xe-btn xe-btn-positive" data-site-key="{{ $Site->site_key }}">{{ xe_trans('xe::settings') }}</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<form action="" method="POST" id="amuz-site">
    {{ csrf_field() }}
    <input type="hidden" name="site_keys[]">
</form>

<script>
    $(function(){
        $(document).on('click', '.plugin-install', function() {
            $("#xe-install-plugin").find('[name="pluginId[]"]').val($(this).data('target'));
            $("#xe-install-plugin").submit();
        })

        $(document).on('click', '.__status_list li a', function() {
            $('input[name="status"]').val($(this).data('value'))
            $(this).closest('form').submit()
        })
    });
</script>
