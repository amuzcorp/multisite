
<div class="panel-group" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('xe::site')}} {{xe_trans('xe::delete')}}</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" id="f-editor-setting" onsubmit="return confirm('이 작업은 되돌릴 수 없습니다.')" action="{{ route('settings.multisite.destroy', ['site_key' => $site_key]) }}">
                {{ csrf_field() }}
                <div class="panel-body">

                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h4 class="panel-title">컨텐츠 이동</h4>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                이동할 컨텐츠
                                                <small>다른 사이트로 이동할 컨텐츠를 선택합니다.</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" data-target='site_contents' class="cart_all __xe_inherit" {{ false ? 'checked' : '' }}>
                                                    전체
                                                </label>
                                            </div>
                                        </div>
                                        @php
                                            $module_list = DB::table('instance_route')->where('site_key', '=', $site_key)->groupBy('module')->get();
                                        @endphp

                                        <h4>문서</h4>
                                        @foreach($module_list as $module)
                                        <div class="input-group">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="contents[{{ $module->module }}]" class="site_contents __xe_inherit" {{ false ? 'checked' : '' }}>
                                                    {{ $module->module }}
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                        <h4>기타</h4>
                                        <div class="input-group">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="contents[files]" class="site_contents __xe_inherit" {{ false ? 'checked' : '' }}>
                                                    첨부파일
                                                </label>
                                            </div>
                                        </div>

                                        <div class="input-group">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="contents[comments]" class="site_contents __xe_inherit" {{ false ? 'checked' : '' }}>
                                                    댓글
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                이동할 대상
                                                <small>선택된 컨텐츠가 이동 될 사이트를 선택합니다.</small>
                                            </label>
                                        </div>
                                        <select name="target_site_key" class="form-control">
                                            @php
                                              use Amuz\XePlugin\Multisite\Models\Site;
                                              $site_list = Site::Where('site_key' ,'<>', $site_key)->get();
                                            @endphp
                                            @foreach($site_list as $site)
                                            <option value="{{ $site->site_key }}" {{ false ? 'selected' : '' }}>
                                                {{ xe_trans($site->config->get('site_title')) }} ({{ $site->FeaturedDomain->first()->domain }})
                                            </option>
                                            @endforeach
                                            <option value="0" {{ false ? '' : 'selected' }}>{{ xe_trans('xe::disuse') }}</option>
                                        </select>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-danger">{{ xe_trans('xe::delete') }}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>
