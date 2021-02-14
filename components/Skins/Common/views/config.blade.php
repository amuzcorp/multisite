@section('page_title')
    <h2>상세설정</h2>
@endsection

@section('page_description')@endsection


<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">상세설정</h3>
                    </div>
                </div>
                <form>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <div class="panel">

                                <div class="panel-heading">
                                    <div class="pull-left">
                                        <h4 class="panel-title">{{xe_trans('xe::settings')}}</h4>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('board::perPage')}} <small>{{xe_trans('board::perPageDescription')}} </small></label>
                                                </div>
                                                <input type="text" id="" name="perPage" class="form-control" value="{{Request::old('perPage', $config->get('perPage'))}}" @if($config->getPure('perPage') === null) disabled="disabled" @endif/>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('xe::comment')}} </label>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-9">
                                                        <select id="" name="comment" class="form-control" @if($config->getPure('comment') === null) disabled="disabled" @endif>
                                                            <option value="true" {!! $config->get('comment') == true ? 'selected="selected"' : '' !!} >{{xe_trans('xe::use')}}</option>
                                                            <option value="false" {!! $config->get('comment') == false ? 'selected="selected"' : '' !!} >{{xe_trans('xe::disuse')}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <a href="{{route('manage.comment.setting', ['targetInstanceId' => $config->get('instanceId')])}}" class="btn">{{xe_trans('xe::settings')}}</a>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="panel-footer">
                            <div class="pull-right">
                                <button type="submit" class="btn btn-primary"><i class="xi-download"></i>{{xe_trans('xe::save')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
