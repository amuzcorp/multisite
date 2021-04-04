<!--[D] accordion 효과 제거 시 panel-group에 id="accordion" 추가 -->
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

    <div class="panel __xe_section_box">
        <div class="panel-body">
        <div class="row">
            <div class="col-sm-4">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h3 class="panel-title">설정 메뉴</h3>
                        </div>
                        <div class="pull-right" style="display:none">
                            <a href="#" class="btn btn-danger">전체 초기화</a>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div id="menuListSection" class="panel-collapse collapse in" role="tabpanel">
                        <ul class="sort-list sort-list--custom-item __ui-sortable">
                            @php ($is_first = true)
                            @foreach ($output['menus'] as $menuGroup => $menuItem)
                                @include('multisite::views.settings.edit.menu_list',$menuItem)
                                @php ($is_first = false)
                            @endforeach
                        </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="tab-content panel">
                @php ($is_first = true)
                @foreach ($output['menus'] as $menuGroup => $menuItem)
                    @include('multisite::views.settings.edit.menu_tab', $menuItem)
                    @php ($is_first = false)
                @endforeach
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
