<!--[D] accordion 효과 제거 시 panel-group에 id="accordion" 추가 -->
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    <div class="row">
        <div class="col-sm-4">
            <div class="panel __xe_section_box">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">설정 메뉴</h3>
                    </div>
                </div>
                <div id="menuListSection" class="panel-collapse collapse in" role="tabpanel">
                <ul class="sort-list sort-list--custom-item __ui-sortable">
                    @php ($is_first = true)
                    @foreach ($output['menus'] as $menuGroup => $menuItem)
                        <li class="{{ $is_first ? 'active':'' }}">
                            @php ($is_first = false)
                            <div class="sort-list__handler">
                                <button type="button" class="xu-button xu-button--subtle-link xu-button--icon">
                                    <span class="xu-button__icon">
                                        <i class="{{$menuItem['icon']}}"></i>
                                    </span>
                                </button>
                            </div>
                            <p class="sort-list__text">
                                <a data-toggle="tab" data-target="#{{ $menuGroup }}Section" href="#{{ $menuGroup }}Section" class="btn-link" style="color:#333;">
                                    {{ xe_trans($menuItem['title']) }} <small>(순서 : {{ $menuItem['ordering'] }})</small>
                                    <i class="pull-right xi-angle-right"></i>
                                </a>
                            </p>
                        </li>
                    @endforeach
                </ul>
                </div>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="tab-content panel">
            @php ($is_first = true)
            @foreach ($output['menus'] as $menuGroup => $menuItem)
                <div class="__xe_section_box tab-pane fade {{ $is_first ? 'active in':'' }}" id="{{ $menuGroup }}Section">
                    @php ($is_first = false)
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h3 class="panel-title">{{ $menuItem['title'] }} 메뉴설정</h3>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="panel">
                            <form method="post" action="{{ route('settings.setting.update.permission', $item['id']) }}">
                                <input type="hidden" name="_token" value="{{{ Session::token() }}}">
                                <input type="hidden" name="site_key" value="{{ $site_key }}" />
                            <div class="panel-heading">
                                <div class="pull-left">
                                    <h4 class="panel-title">메뉴속성</h4>
                                </div>
                            </div>
                            <div class="panel-body">
                                {{uio('form', [
                                    'fields' => [
                                        'title' => [
                                            '_type' => 'LangText',
                                            'label' => '메뉴이름',
                                            'description' => '플러그인에서 추가된 메뉴는 메뉴이름이 비어있을 수 있습니다.'
                                        ],
                                        'icon' => [
                                            '_type' => 'text',
                                            'label' => '아이콘',
                                            'description' => '<a href="https://xpressengine.github.io/XEIcon/library-2.3.3.html" target="_blank">XE아이콘 라이브러리</a>에 선언된 아이콘코드로 메뉴아이콘을 변경할 수 있습니다.'
                                        ],
                                        'is_off' => [
                                            '_type' => 'select',
                                            'label' => '메뉴 숨기기',
                                            'description' => '권한과 관계없이 메뉴를 숨길 수 있습니다.',
                                            'options' => ['Y' => '숨김', 'N' => '숨기지 않음']
                                        ],
                                        'ordering' => [
                                            '_type' => 'text',
                                            'label' => '순서',
                                            'description' => '숫자가 낮은 순서대로 정렬됩니다.'
                                        ]
                                    ],
                                    'value' => $menuItem,
                                    'type' => 'fieldset'
                                ])}}
                            </div>
                            <div class="panel-heading">
                                <div class="pull-right">
                                    <button type="submit" class="btn btn-primary">{{xe_trans('xe::save')}}</button>
                                </div>
                            </div>
                            </form>
                        </div>
                        <div class="panel">
                            <div class="panel-heading">
                                <div class="pull-left">
                                    <h4 class="panel-title">엑세스권한</h4>
                                </div>
                            </div>
                            <div class="panel-body">
                            @foreach ($menuItem['child'] as $menuId => $item)
                                <form method="post" action="{{ route('settings.setting.update.permission', $item['id']) }}">
                                    <input type="hidden" name="_token" value="{{{ Session::token() }}}">
                                    <input type="hidden" name="site_key" value="{{ $site_key }}" />
                                    <div class="panel-heading">
                                        <p>{{ xe_trans($item['title']) }}</p>
                                    </div>
                                    <div class="panel-body">
                                        {!! uio('xpressengine@registeredPermission',['permission'=>$item,'site_key' => $site_key]) !!}
                                    </div>
                                    <div class="panel-heading">
                                        <div class="pull-right">
                                            <button type="submit" class="btn btn-primary">{{xe_trans('xe::save')}}</button>
                                        </div>
                                    </div>
                                </form>
                            @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>
</div>
