
<div class="panel-group" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">메타정보</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" enctype="multipart/form-data" id="f-editor-setting" action="{{ route('settings.multisite.update', ['site_key' => $site_key]) }}">
                {{ csrf_field() }}
                @include("multisite::views.settings.edit.default_infos")
                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary">{{ xe_trans('xe::save') }}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>
