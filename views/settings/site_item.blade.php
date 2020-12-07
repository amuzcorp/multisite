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
                                            <span class="icon-wrap" style="background-image:url('{{ $Site->config->get('favicon.path') }}');"></span>
                                            {{ xe_trans($Site->seo->get('mainTitle')) }}
                                        </span>
        <dl>
            <dt class="sr-only">Front Link</dt>
            <dd>{!! $Site->getDomainLink('방문') !!}</dd>

            <dt class="sr-only">Back Link</dt>
            <dd>{!! $Site->getDomainLink('대시보드',"settings") !!}</dd>
            <dt class="sr-only">Domain</dt>
            <dd>대표 도메인 {{ $Site->FeaturedDomain->first()->domain }} (외 {{ $Site->Domains->count() - 1 }} 연결)</dd>
        </dl>
        <p class="ellipsis">{{ xe_trans($Site->seo->get('description')) }}</p>
    </div>

    <div class="btn-right form-inline">
        <a href="{!! $Site->getDomainLink(false,"settings/setting") !!}" target="_blank" class="xe-btn xe-btn-positive-outline" data-site-key="{{ $Site->site_key }}">{{ xe_trans('xe::defaultSettings') }} {{ xe_trans('xe::modify') }}</a>
        <a href="" class="xe-btn xe-btn-positive" data-site-key="{{ $Site->site_key }}">{{ xe_trans('xe::domain') }} {{ xe_trans('xe::settings') }}</a>
    </div>
</li>
