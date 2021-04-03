<?php
use Xpressengine\User\Models\User;
?>
{{ app('xe.frontend')->js('assets/core/xe-ui-component/js/xe-page.js')->load() }}
{{ app('xe.frontend')->js('assets/vendor/jqueryui/jquery-ui.min.js')->load() }}
{{ app('xe.frontend')->css('assets/vendor/jqueryui/jquery-ui.min.css')->load() }}
<div class="panel-group">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">사이트 회원</h3>
                ( {{xe_trans('xe::searchUserCount')}} : {{  $output['users']->total() }} / {{xe_trans('xe::allUserCount')}} : {{ $output['allUserCount'] }} )
            </div>
            <div class="pull-right">
            </div>
        </div>

        <div class="panel-heading">

            <div class="pull-left">
                <div class="input-group search-group">
                    <form method="GET" action="{{ route('settings.user.index') }}" accept-charset="UTF-8" role="form" id="_search-form" class="form-inline">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                            <span class="__xe_selectedKeyfield">
                                            @if (Request::get('keyfield') === 'display_name')
                                                    {{xe_trans($config->get('display_name_caption'))}}
                                                @elseif (Request::get('keyfield') === 'login_id')
                                                    {{xe_trans('xe::id')}}
                                                @elseif (Request::get('keyfield') === 'email')
                                                    {{xe_trans('xe::email')}}
                                                @else
                                                    {{xe_trans('xe::select')}}
                                                @endif
                                            </span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="#" class="__xe_selectKeyfield" data-value="display_name">{{xe_trans($output['user_config']->get('display_name_caption'))}}</a></li>
                                    <li><a href="#" class="__xe_selectKeyfield" data-value="login_id">{{xe_trans('xe::id')}}</a></li>
                                    <li><a href="#" class="__xe_selectKeyfield" data-value="email">{{xe_trans('xe::email')}}</a></li>
                                </ul>
                            </div>
                            <div class="search-input-group">
                                <input type="text" name="keyword" class="form-control" aria-label="Text input with dropdown button" placeholder="{{xe_trans('xe::enterKeyword')}}" value="{{ Request::get('keyword') }}">
                                <button type="submit" class="btn-link">
                                    <i class="xi-search"></i><span class="sr-only">{{xe_trans('xe::search')}}</span>
                                </button>
                            </div>
                        </div>
                        @foreach(Request::except(['keyfield','keyword','page','startDate', 'endDate']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" class="__xe_keyfield" name="keyfield" value="{{ Request::get('keyfield') }}">
                    </form>
                </div>
            </div>
            <div class="pull-right">
                <div class="input-group search-group">
                <form method="post" action="{{ route('settings.multisite.add.user',['site_key'=>$site_key]) }}" accept-charset="UTF-8" role="form" id="_search-form" class="form-inline">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <div class="input-group-btn">
                           <select class="form-control" name="group_id">
                               <option value="">그룹 선택</option>
                               @foreach($output['groups'] as $key => $group)
                                   <option value="{{$group->id}}">{{ $group->name }}</option>
                               @endforeach
                           </select>
                        </div>
                        <div class="search-input-group">
                            <input type="text" name="user_id" class="form-control" aria-label="Text input with dropdown button" placeholder="아이디 입력" value="">
                        </div>
                        <div class="input-group-btn">
                            <button type="submit" class="btn btn-success">
                                <i class="xi-plus"></i><span>사이트회원 추가</span>
                            </button>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
        <div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th scope="col"><input type="checkbox" class="__xe_check-all"></th>
                    <th scope="col">
                        @if ($output['user_config']->get('use_display_name') === true)
                            {{xe_trans($output['user_config']->get('display_name_caption'))}}
                        @else
                            {{xe_trans('xe::id')}}
                        @endif
                    </th>
                    <th scope="col" class="text-center">{{xe_trans('xe::account')}}</th>
                    <th scope="col">{{xe_trans('xe::email')}}</th>
                    <th scope="col">{{xe_trans('xe::signUpDate')}}</th>
                    <th scope="col">{{xe_trans('xe::latestLogin')}}</th>
                    <th scope="col">{{xe_trans('xe::userGroup')}}</th>
                    <th scope="col">{{xe_trans('xe::status')}}</th>
                    <th scope="col">{{xe_trans('xe::management')}}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($output['users'] as $user)
                    @php
                    $user->site_key = $site_key;
                    @endphp
                    <tr>
                        <td><input name="userId[]" class="__xe_checkbox" type="checkbox" value="{{ $user->getId() }}" @if($user->rating === \Xpressengine\User\Rating::SUPER) disabled @endif></td>
                        <td>
                            <img data-toggle="xe-page-toggle-menu"
                                 data-url="{{ route('toggleMenuPage') }}"
                                 data-data='{!! json_encode(['id'=>$user->getId(), 'type'=>'user']) !!}' src="{{ $user->getProfileImage() }}" width="30" height="30" alt="{{xe_trans('xe::profileImage')}}" class="user-profile">
                            <span>
                                    <a href="#"
                                       data-toggle="xe-page-toggle-menu"
                                       data-url="{{ route('toggleMenuPage') }}"
                                       data-data='{!! json_encode(['id'=>$user->getId(), 'type'=>'user']) !!}' data-text="{{ $user->getDisplayName() }}">{{ $user->getDisplayName() }}</a>
                               </span>
                        </td>
                        <td class="text-center">
                            @if(count($user->accounts))
                                @foreach($user->accounts as $account)
                                    <span data-toggle="tooltip" class="badge black" title="{{ $account->provider }}">{{ $account->provider }}</span>
                                @endforeach
                            @else
                                <span data-toggle="tooltip" class="badge black" title="기본">xe</span>
                            @endif
                        </td>
                        <td>{{ data_get($user, 'email', xe_trans('xe::empty')) }}</td>
                        <td>{!! $user->created_at->format('y-m-d') !!}</td>
                        <td>
                            @if($user->login_at !== null)
                                {!! $user->login_at->format('y-m-d') !!}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($user->groups !== null)
                                {{ implode(', ', array_pluck($user->groups, 'name')) }}
                            @endif
                        </td>
                        <td>
                            @if ($user->status === User::STATUS_ACTIVATED)
                                <label class="label label-green">{{xe_trans('xe::permitted')}}</label>
                            @elseif ($user->status === User::STATUS_PENDING_ADMIN || $user->status === User::STATUS_PENDING_EMAIL)
                                <label class="label label-blue">{{ xe_trans('xe::pending') }}</label>
                            @else
                                <label class="label label-danger">{{xe_trans('xe::rejected')}}</label>
                            @endif
                        </td>
                        <td>
                            {!! $Site->getDomainLink(xe_trans('xe::management'),'settings/user/'.$user->getId().'/edit','class="btn btn-default"') !!}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        </div>
        @if($pagination = $output['users']->render())
            <div class="panel-footer">
                <div class="pull-left">
                    <nav>
                        {!! $pagination !!}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>
