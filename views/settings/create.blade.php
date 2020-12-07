@section('page_title')
    <h2>{{ $title }}</h2>
@stop
@section('page_description')
    <small>{{xe_trans('multisite::searchSitesCount') }}</small>
@endsection



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
                    <div class="form-group">
                        <label for="site_key">{{xe_trans('multisite::host')}}</label>
                        <div class="input-group">
                            <input type="text" id="site_key" name="site_key" class="form-control" value="{{Request::old('host')}}" aria-describedby="basic-addon1">
                            <span class="input-group-addon" id="basic-addon1">.{{ $defaultSite->FeaturedDomain->first()->domain }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ xe_trans('xe::site')}} {{ xe_trans('xe::name') }}</label> <small>{{ xe_trans('xe::inputSiteNameDescription') }}</small>
                        {!! uio('langText', ['name'=>'site_title']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="pull-right">
            <a href="{{ url()->previous(route('settings.menu.index')) }}" class="btn btn-default">{{xe_trans('xe::cancel')}}</a>
            <button type="submit" class="btn btn-primary">{{xe_trans('xe::submit')}}</button>
        </div>
    </div>

</form>
