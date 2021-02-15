{{-- implement it!! --}}

<div class="container-fluid container-fluid--part">
    <div class="row">
        <form method="get" action="">
            <div class="col-sm-12">
                <div class="panel-group">
                    <div class="panel">
                        <div class="panel-heading">
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
                                            <a href="{{ instance_route('show',['site_key' => $Site->site_key]) }}">{{ xe_trans($Site->config->get('site_title')) }}</a>
                                        </span>
                                        <dl>
                                            <dt class="sr-only">Domain</dt>
                                            <dd>대표 도메인 {{ $Site->FeaturedDomain->first()->domain }}</dd>
                                        </dl>
                                        <p class="ellipsis"></p>
                                    </div>

                                    <div class="btn-right form-inline">
                                        <a href="{!! $Site->getDomainLink(false,"settings/setting") !!}" target="_blank" class="xe-btn xe-btn-positive-outline" data-site-key="{{ $Site->site_key }}">사이트 바로가기</a>
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
