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
            <dt class="sr-only">Domain</dt>
            <dd>대표 도메인 {{ $Site->FeaturedDomain->first()->domain }} (외 {{ $Site->Domains->count() - 1 }} 연결)</dd>
            <dt class="sr-only">{{ xe_trans('xe::author') }}</dt>
        </dl>
        <p class="ellipsis">{{ xe_trans($Site->seo->get('description')) }}</p>
    </div>

    <div class="btn-right form-inline">
        <a href="" class="xe-btn xe-btn-positive-outline" data-plugin-id="{{ $Site->site_key }}">{{ xe_trans('xe::modify') }}</a>
    </div>
</li>
