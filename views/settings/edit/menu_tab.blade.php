<div class="__xe_section_box tab-pane fade {{ $is_first ? 'active in':'' }}" id="{{ $menuItem['menuGroup'] }}Section">
    @php ($is_first = false)
    <div class="panel-heading">
        <div class="pull-left">
            <h3 class="panel-title">{{xe_trans($menuItem['title']) }} 메뉴설정</h3>
        </div>
    </div>
    <div class="panel-body">
        <div class="panel">
            <form  method="post" action="{{ route('settings.multisite.update.setting_menus', [
                                            'site_key' => $site_key,
                                            'config_id' => $menuItem['config_key']
                                            ]) }}" onsubmit="return updateSiteSetting(this)">

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
                                'label' => '메뉴이름'
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
                                'label' => '정렬 순서',
                            ],
                            'description' => [
                                '_type' => 'textarea',
                                'label' => '설명',
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
            <form  method="post" action="{{ route('settings.multisite.update.permissions', [
                                            'site_key' => $site_key,
                                            'permission_id' => $menuItem['permission_key']
                                            ]) }}" onsubmit="return updateSiteSetting(this)">

                <input type="hidden" name="_token" value="{{{ Session::token() }}}">
                <input type="hidden" name="site_key" value="{{ $site_key }}" />
                <div class="panel-heading">
                    <div class="pull-left">
                        <h4 class="panel-title">{{ xe_trans($menuItem['title']) }} 엑세스권한</h4>
                    </div>
                </div>
                <div class="panel-body">
                    {!! uio('xpressengine@registeredPermission',['permission'=>$menuItem,'site_key' => $site_key]) !!}
                </div>

                <div class="panel-heading">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary">{{xe_trans('xe::save')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@php ($is_first = false)
@if(array_get($menuItem,'child'))
    @foreach($menuItem['child'] as $menuGroup => $menuItem)
        @include('multisite::views.settings.edit.menu_tab',$menuItem)
    @endforeach
@endif
