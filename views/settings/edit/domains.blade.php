@if(Request::get('target_domain'))
    @php
        $domain_field = [
                        '_type' => 'text',
                        'label' => '도메인',
                        'readonly' =>  'readonly',
                        'description' => 'http:// 또는 https://를 제외한 도메인을 입력합니다.'
                    ];
        $index_field = [
                        '_type' => 'select',
                        'label' => '연결할 인스턴스',
                        'disabled' =>  'disabled',
                        'description' => 'SSL을 사용하기 위해서는 로드밸런서에 연결하거나 서버에 인증서가 별도로 설치되어야 합니다.',
                        'options' => array_merge([null => '기본 홈 인스턴스'],$output['menu_instances']),
                    ];
    @endphp
    @if(!isset($output['domain']->domain))
        @unset($domain_field['readonly'])
    @endif
    @if(!isset($output['domain']->is_featured) || $output['domain']->is_featured == "N")
        @unset($index_field['disabled'])
    @endif
    {{--추가 또는 편집--}}
    <div class="panel-group">
        <form id="formCreate" method="post" action="{{ route('settings.multisite.create.domain', ['site_key' => $site_key]) }}">
            {{ csrf_field() }}
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">도메인 추가</h3>
                    </div>
                </div>

                <div class="panel-body">
                    {{uio('form', [
                'fields' => [
                    'domain' => $domain_field,
                    'is_redirect_to_featured' => [
                        '_type' => 'radio',
                        'label' => '리다이렉트',
                        'description' => '리다이렉트를 사용하면 접속시 기본도메인으로 리다이렉트 하고, 사용하지 않으면 접속 도메인이 유지됩니다.',
                        'options' => ['Y' => '사용','N'=>'사용안함'],
                    ],
                    'is_ssl' => [
                        '_type' => 'radio',
                        'label' => 'SSL',
                        'description' => 'SSL을 사용하기 위해서는 로드밸런서에 연결하거나 서버에 인증서가 별도로 설치되어야 합니다.',
                        'options' => ['Y' => '사용','N'=>'사용안함'],
                    ],
                    'index_instance' => $index_field
                ],
                'value' => $output['domain'],
                'type' => 'fieldset'
                    ])}}
                </div>

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary"><span>{{ xe_trans('xe::save') }}</span></button>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'domains']) }}" class="btn btn-light"><span>{{ xe_trans('xe::back') }}</span></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@else
    <div class="panel-group">
        <form id="__xe_fList" method="post" action="{{ route('settings.multisite.update.domain.default', ['site_key' => $site_key]) }}">
            {{ csrf_field() }}
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">도메인<small>여러개의 도메인을 연결할 수 있습니다.</small></h3>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'domains', 'target_domain' => true]) }}" class="btn btn-primary"><i class="xi-plus"></i><span>새 도메인 추가</span></a>
                    </div>
                </div>

                <div class="panel-heading">
                    <div class="pull-left">
                        <div class="btn-group" role="group">
                            <button type="button" class="__xe_remove btn btn-default">선택 삭제</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" id="__xe_check-all"></th>
                            <th scope="col">보안</th>
                            <th scope="col">도메인</th>
                            <th scope="col">기본도메인</th>
                            <th scope="col">도메인유지</th>
                            <th scope="col">인덱스 인스턴스</th>
                            <th scope="col">관리</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($output['domains'] as $domain)
                            <tr>
                                <td><input type="checkbox" name="id[]" {!! $domain->is_featured == 'Y' ? 'disabled="disabled"' : '' !!} value="{{ $domain->domain }}" class="__xe_checkbox"></td>
                                <td>
                                    @if($domain->is_ssl == 'Y')
                                        <span class="xe-badge xe-success"><i class="xi-lock"></i> https://</span>
                                    @else
                                        <span class="xe-badge xe-danger"><i class="xi-unlock-o"></i> http://</span>
                                    @endif
                                </td>
                                <td>{{ $domain->domain }}</td>
                                <td><input class="__xe_check_join_group" name="featured_domain" type="radio"
                                           value="{{ $domain->domain }}" {!! $domain->is_featured == 'Y' ? 'checked="checked"' : '' !!}>
                                </td>
                                <td>
                                    @if($domain->is_featured == 'Y')
                                        <span class="xe-badge xe-warning">기본</span>
                                    @elseif($domain->is_redirect_to_featured == "Y")
                                        <span class="xe-badge xe-warning">리디렉션</span>
                                    @else
                                        도메인 유지
                                    @endif
                                </td>
                                <td>
                                    @if($domain->is_featured == 'Y')
                                        <span class="xe-badge xe-warning">기본 인스턴스</span>
                                    @elseif(is_null($domain->index_instance))
                                        기본 홈 인스턴스
                                    @else
                                        {!! $domain->MenuItem->getLinkAttribute() !!}
                                    @endif
                                </td>
                                <td><a href="{{ route($target_route, ['site_key' => $site_key, 'mode'=>'domains', 'target_domain' => $domain->domain]) }}" class="btn btn-default">관리</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="panel-footer">
                    <div class="pull-left">
                        <button type="submit" class="btn btn-warning"><span>
                                <i class="xi-renew"></i> 기본도메인 변경</span></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endisset

<script>
    // @FIXME 파일 분리
    window.jQuery(function ($) {
        $('#__xe_check-all').change(function () {
            if ($(this).is(':checked')) {
                $('input.__xe_checkbox:not(:disabled)').prop('checked', true);
            } else {
                $('input.__xe_checkbox').prop('checked', false);
            }
        });


        $('.__xe_remove').click(function (e) {
            if (!$('input.__xe_checkbox:checked').is('input')) {
                return false;
            }
            var $f = $('#__xe_fList');
            $f.attr('action', "{{ route('settings.multisite.delete.domain',['site_key' => $site_key]) }}");
            $('<input type="hidden" name="_method" value="DELETE">').prependTo($f);
            $f.submit();
        });
    });
</script>
