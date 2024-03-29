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
            <a data-toggle="tab" data-target="#{{ array_get($menuItem,'menuGroup','undefined_menu_group') }}Section" href="#{{ array_get($menuItem,'menuGroup','undefined_menu_group') }}Section" class="btn-link" style="color:#333;">
                {{ xe_trans($menuItem['title']) }} <small>(순서 : {{ array_get($menuItem,'ordering') }})</small>
                <i class="pull-right {{ array_get($menuItem,'delete_plugin',"N") == "Y" ? 'xi-close-min' : ((array_get($menuItem,'is_off',"N") == "Y") ? 'xi-eye-off' : 'xi-eye-o') }}"></i>
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
