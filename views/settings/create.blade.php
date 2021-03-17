@section('page_title')
    <h2>{{ $title }}</h2>
@stop


<form action="{{ route('settings.multisite.store') }}" method="post" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="{{ Session::token() }}"/>
    <div class="col-md-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{xe_trans('multisite::addNewSite')}}</h3>
                    </div>
                    <div class="pull-right">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">{{xe_trans('xe::fold')}}</span></a>
                    </div>
                </div>

                <div class="panel-body" id="collapseOne">
                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h3 class="panel-title">사이트 기본설정</h3>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label for="site_key">{{xe_trans('multisite::host')}}</label> <small>호스트는 향후 변경이 불가능합니다.</small>
                                <div class="input-group">
                                    <input type="text" id="site_key" name="host" class="form-control" value="{{Request::old('host')}}" aria-describedby="basic-addon1" maxlength="50">
                                    <span class="input-group-addon" id="basic-addon1">.{{ $defaultSite->FeaturedDomain->first()->domain }}</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ xe_trans('xe::site')}} {{ xe_trans('xe::name') }}</label> <small>{{ xe_trans('xe::inputSiteNameDescription') }}</small>
                                {!! uio('langText', ['name'=>'site_title']) !!}
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-heading">
                                <div class="pull-left">
                                    <h3 class="panel-title">메타정보 설정</h3>
                                </div>
                            </div>
                        @include("multisite::views.settings.edit.default_infos")
                    </div>

                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h3 class="panel-title">{{ xe_trans('xe::extension') }}</h3>
                            </div>
                        </div>
                        <div class="panel-body">

                            <div class="form-group">
                            <label>설치된 익스텐션</label> <small>이 사이트에서 활성화 할 익스텐션을 선택합니다.</small>
                            <div class="row">
                                @php
                                    $need_plugins = [
                                        'board','comment','widget_page','page','ckeditor'
                                        ,'dynamic_factory','dynamic_field_extend','multisite','news_client'
                                        ];
                                @endphp
                                @foreach(app('xe.plugin')->getPlugins() as $item)
                                    <div class="col-sm-6">
                                        <div class="input-group">
                                            <div class="checkbox card-wrap">
                                                <label>
                                                    <input type="checkbox"
                                                           @if(in_array($item->getId(),$need_plugins)) checked="checked" onclick="return false" @endif
                                                           name="extensions[{{ $item->getId() }}]"
                                                           value="Y"
                                                           class="__xe_checkbox">
                                                    <span class="plugin-title card-title">{{ $item->getTitle() }}</span>
                                                    @if($authors = $item->getAuthors())
                                                        <small>By {{ reset($authors)['name'] }}</small>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                        @if(0)
                                        <p class="ellipsis">{{ $item->getDescription() }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            </div>

                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h3 class="panel-title">{{ xe_trans('xe::theme') }}</h3>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label>{{ xe_trans('xe::site')}} {{ xe_trans('xe::theme')}}</label> <small class="text-danger">선택된 테마의 익스텐션은 반드시 활성화 처리됩니다.</small>
                                {!! uio('themeSelect') !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pull-right">
            <a href="{{ url()->previous(route('settings.multisite.index')) }}" class="btn btn-default">{{xe_trans('xe::cancel')}}</a>
            <button type="submit" class="btn btn-primary">{{xe_trans('xe::submit')}}</button>
        </div>
    </div>

</form>
