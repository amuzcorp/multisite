
<!--[D] accordion 효과 제거 시 panel-group에 id="accordion" 추가 -->
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    @foreach ($output['permissionGroups'] as $groupName => $group)
        <div class="panel __xe_section_box">
            <div class="panel-heading">
                <div class="pull-left">
                    <h3 class="panel-title">{{ $groupName }}</h3>
                </div>
                <div class="pull-right">
                    <a data-toggle="collapse" data-parent="#accordion" data-target="#{{ $groupName }}Section" href="#collapseTwo" class="btn-link panel-toggle"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">{{ xe_trans('xe::closeMenu') }}</span></a>
                </div>
            </div>
            <div id="{{ $groupName }}Section" class="panel-collapse panel-body collapse in" role="tabpanel">
                @foreach ($group as $key => $item)
                    <div class="panel">

                        <form  method="post" action="{{ route('settings.multisite.update.permissions', [
                                            'site_key' => $site_key,
                                            'permission_id' => $item['id']
                                            ]) }}" onsubmit="return updateSiteSetting(this)">
                        <input type="hidden" name="_token" value="{{{ Session::token() }}}">
                        <input type="hidden" name="site_key" value="{{ $site_key }}" />
                        <div class="panel-heading">
                            <h4>{{ $item['title'] }}</h4>
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
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
