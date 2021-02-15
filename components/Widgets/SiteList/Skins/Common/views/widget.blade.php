{{ XeFrontend::css('plugins/board/assets/css/widget.list.css')->load() }}
<div class="list-widget">
    <h3 class="article-table-title">
        {{$title}}
    </h3>
    <a href="#"></a>
    <div class="table-wrap">
        <table class="article-table type2">
            <caption class="xe-sr-only">{{$title}}</caption>
            <tbody>
            @foreach ($Sites as $Site)
                <tr>
                    <td><strong><a href="{!! $Site->getDomainLink(false) !!}">{{ xe_trans($Site->config->get('site_title')) }}</a></strong>
                            <small>
                                {{ $Site->FeaturedDomain->first()->domain }}
                            </small>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
