@section('page_title')
    <h2>사이트 목록</h2>
@stop

{{-- include contents blade file --}}
@section('content')
    <div class="container-fluid container-fluid--part claim">
        {!! isset($content) ? $content : '' !!}
    </div>
@show
