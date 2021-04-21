<li class="{{ $is_first ? 'active':'' }}">
    <div class="item_wrap">
        <div class="sort-list__handler">
            <button type="button" class="xu-button xu-button--subtle-link xu-button--icon">
                <span class="xu-button__icon">
                    <i class="{{ array_get($menuItem,'icon','xi-bars') }}"></i>
                </span>
            </button>
        </div>
        <p class="sort-list__text">
            <a data-toggle="tab" data-target="#{{ array_get($menuItem,'menuGroup','undefined_menu_group') }}Section" href="#{{ $menuItem['menuGroup'] }}Section" class="btn-link" style="color:#333;">
                {{ xe_trans($menuItem['title']) }} <small>(순서 : {{ $menuItem['ordering'] }})</small>
                <i class="pull-right {{ $menuItem['is_off'] == "Y" ? 'xi-eye-off' : (($menuItem['display'] == false) ? 'xi-close-min' : 'xi-eye-o') }}"></i>
            </a>
        </p>
    </div>
    @php ($is_first = false)
    @if(array_get($menuItem,'child'))
        <ul class="sort-list sort-list--custom-item __ui-sortable sub_items">
            @foreach($menuItem['child'] as $menuGroup => $menuItem)
                @include('multisite::views.settings.edit.menu_list',$menuItem)
            @endforeach
        </ul>
    @endif
</li>
