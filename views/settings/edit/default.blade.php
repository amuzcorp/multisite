
<div class="panel-group" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('xe::defaultSettings')}}</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" id="f-editor-setting" action="{{ route('settings.multisite.update', $site_key) }}">
                {{ csrf_field() }}
                <div class="panel-body">

                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h4 class="panel-title">{{ xe_trans('xe::defaultSettings') }}</h4>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::editorHeight') }}
                                                <small> {{ xe_trans('xe::unit') }}: px</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="height" value="{{ false }}">
                                            <span class="input-group-addon">px</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::fontSize') }}
                                                <small>{{ xe_trans('xe::explainFontSize') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" name="fontSize" value="{{ false }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::fontFamily') }}
                                                <small>{{ xe_trans('xe::explainFontFamily') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" name="fontFamily" value="{{ false }}" placeholder="Ex) Tahoma, Geneva, sans-serif">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                CSS
                                                <small>{{ xe_trans('xe::explainStylesheet') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" name="stylesheet" value="{{ false }}" placeholder="Ex) plugin/myplugin/assets/some.css">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h4 class="panel-title">{{ xe_trans('xe::file') }}</h4>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::enableUpload') }}
                                                <small>{{ xe_trans('xe::explainEnableUpload') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="uploadActive" class="form-control">
                                            <option value="1" {{ false ? 'selected' : '' }}>{{ xe_trans('xe::use') }}</option>
                                            <option value="0" {{ false ? '' : 'selected' }}>{{ xe_trans('xe::disuse') }}</option>
                                        </select>
                                        {{--<label>--}}
                                        {{--<input type="checkbox" name="uploadActive" value="1" {{ $config->get('uploadActive') ? 'checked' : '' }}>--}}
                                        {{--{{ xe_trans('xe::explainEnableUpload') }}--}}
                                        {{--</label>--}}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::availableExtension') }}
                                                <small>{{ xe_trans('xe::explainAvailableExtension') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" name="extensions" value="{{ false }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::maxFileSize') }}
                                                <small>{{ xe_trans('xe::descMaxFileSize') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="fileMaxSize" value="{{ false }}">
                                            <span class="input-group-addon">MB</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('xe::attachMaxSize') }}
                                                <small>{{ xe_trans('xe::descAttachMaxSize') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ false === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="attachMaxSize" value="{{ false }}">
                                            <span class="input-group-addon">MB</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="clearfix">
                                        <label>php.ini 설정 값</label>
                                    </div>

                                    <div class="clearfix">
                                        <label>upload_max_filesize</label>
                                        <small>{{ xe_trans('xe::descUploadMaxFilesize', ['fileMaxSize' => xe_trans('xe::maxFileSize'), 'attachMaxSize' => xe_trans('xe::attachMaxSize')]) }}</small>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ false }}" disabled>
                                            <span class="input-group-addon">MB</span>
                                        </div>
                                    </div>

                                    <div class="clearfix">
                                        <label>post_max_size</label>
                                        <small>{{ xe_trans('xe::descPostMaxSize') }}</small>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ false }}" disabled>
                                            <span class="input-group-addon">MB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary">{{ xe_trans('xe::save') }}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>
