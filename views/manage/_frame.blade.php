@section('page_title')
    <h2>{{xe_trans('page::pageDetailConfigures')}}</h2>
@endsection

@section('page_description')
@endsection

{{-- $_active 는 SettingsSkin 에서 처리됨 --}}
<ul class="nav nav-tabs">
    <li @if($_active == 'edit') class="active" @endif><a href="{{ route('manage.plugin.page.edit', ['pageId' => $pageId]) }}">{{xe_trans('xe::config')}}</a></li>
    <li @if($_active == 'editor') class="active" @endif><a href="{{ route('manage.plugin.page.editor', ['pageId' => $pageId]) }}">{{xe_trans('xe::editor')}}</a></li>
    <li @if($_active == 'skin') class="active" @endif><a href="{{ route('manage.plugin.page.skin', ['pageId' => $pageId]) }}">{{xe_trans('xe::skin')}}</a></li>
</ul>

{{-- include contents blade file --}}
@section('content')
    {!! isset($content) ? $content : '' !!}
@show
