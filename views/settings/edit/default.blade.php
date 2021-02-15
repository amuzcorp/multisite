
<div class="panel-group" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('xe::defaultSettings')}}</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" enctype="multipart/form-data" id="f-editor-setting" action="{{ route('settings.multisite.update', ['site_key' => $site_key]) }}">
                {{ csrf_field() }}
                <div class="panel-body">
                    @foreach($infos as $config_parent => $info)
                        @if($info['display'] !== true) @continue @endif
                        <div class="panel">
                            <div class="panel-heading">
                                <div class="pull-left">
                                    <h4 class="panel-title">{{ $info['title'] }}</h4>
                                    <small>{{ $info['description'] }}</small>
                                </div>
                            </div>

                            <div class="panel-body">
                                <div class="row">
                                    @foreach($info['fields'] as $config_id => $field)
                                        <div class="{{ isset($field["size"]) ? $field["size"] : "col-sm-6" }}">
                                            <div class="form-group">
                                                @php
                                                    if(isset($Site->meta[$config_parent])){
                                                        if($field['_type'] == 'formImage' && $Site->meta[$config_parent]->get($config_id) !== null){
                                                            $field['uio']['value'] = ['path' => \Xpressengine\Media\Models\Image::find($Site->meta[$config_parent]->get($config_id))->url() ];
                                                        }else{
                                                            $field['uio']['value'] = $Site->meta[$config_parent]->get($config_id);
                                                        }
                                                    }
                                                @endphp
                                                {{ uio($field['_type'],$field["uio"]) }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary">{{ xe_trans('xe::save') }}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>
