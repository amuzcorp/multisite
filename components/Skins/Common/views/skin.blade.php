@section('page_title')
    <h2>사이트 리스트 스킨설정</h2>
@endsection

@section('page_description')@endsection
<!-- Main content -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{xe_trans('xe::skin')}}</h3>
                    </div>
                </div>
                <div id="collapseTwo" class="panel-collapse collapse in">
                    <div class="panel-body">
                        {!! $skinSection !!}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>