@section('page_title')
    <h2>{{ xe_trans('multisite::sitesManage') }}</h2>
@stop

@section('page_description')
    <small>{{xe_trans('multisite::searchSitesCount') }} : {{ $Sites->total() }}</small>
@endsection

<div class="container-fluid container-fluid--part">
    <div class="row">
        <form id="search_form" method="get" action="">
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
                            <div class="pull-right" style="padding-left:10px;">
                                <div class="search-btn-group">
                                    <a href="{{ route('settings.multisite.create') }}" class="xe-btn xe-btn-install">
                                        <i class="xi-plus"></i>{{ xe_trans('xe::site') }} {{ xe_trans('xe::create') }}
                                    </a>
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
                            <div class="pull-right">
{{--                            셀렉트 있던 자리--}}
                            </div>
                            <div class="pull-left">
                                <select name="list_count" class="form-control" onchange="jQuery('#search_form').submit();">
                                    @foreach([5,10,15,30,50,100] as $num)
                                        <option value="{{ $num }}" {!! $num == $list_count ? 'selected="selected"' : '' !!}>{{ $num }}개씩 보기</option>
                                    @endforeach
                                </select>
{{--                                <div class="btn-group">--}}
{{--                                    <button class="btn btn-default __xe_check_all">{{ xe_trans('xe::selectAll') }}</button>--}}
{{--                                </div>--}}
{{--                                <div class="btn-group __xe_controll_btn">--}}
{{--                                </div>--}}
                            </div>
                        </div>

                        <ul class="list-group list-plugin">
                            @foreach ($Sites as $Site)
                                <li class="list-group-item {{ ($Site->status == 'deactivated') ? 'off' : '' }}">
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
                                            <dt class="sr-only">생성일</dt>
                                            <dd>{!! $Site->created_at->format('Y-m-d H:i') !!}</dd>
                                            <dt class="sr-only">상태</dt>
                                            <dd class="{{ $Site->status == 'activated' ? 'text-info' : 'text-warning' }}">{!! $Site->getStatus() !!}</dd>
                                        </dl>
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
                                        {!! isset($Site->plugin->get('list')['pos_config']) && $Site->plugin->get('list')['pos_config']['status'] == 'activated' ? '<span class="xe-badge xe-primary">POS</span>' : '' !!}
                                        {!! isset($Site->plugin->get('list')['signage']) && $Site->plugin->get('list')['signage']['status'] == 'activated' ? '<span class="xe-badge xe-warning">사이니지</span>' : '' !!}
                                        {!! isset($Site->plugin->get('list')['cctv_open_platform']) && $Site->plugin->get('list')['cctv_open_platform']['status'] == 'activated' ? '<span class="xe-badge xe-success">CCTV</span>' : '' !!}
                                        <span class="p-2">&nbsp;</span>
{{--                                        <a href="{!! $Site->getDomainLink(false,"settings/setting") !!}" target="_blank" class="xe-btn xe-btn-positive-outline" data-site-key="{{ $Site->site_key }}">{{ xe_trans('xe::defaultSettings') }} {{ xe_trans('xe::modify') }}</a>--}}
                                        <a href="{{ route('settings.multisite.edit',['site_key' => $Site->site_key]) }}" class="xe-btn xe-btn-positive-outline" data-site-key="{{ $Site->site_key }}">{{ xe_trans('xe::settings') }}</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>


                {{ $Sites->appends(['status' => Request::get('status'), 'query' => Request::get('query'), 'list_count' => $list_count])->links() }}
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
