# Multisite Reposity

### Description
XE3를 하나의 구동환경에서 무제한 웹사이트로 확장할 수 있도록 합니다.

### Enviroment
- [XpressEngine3](https://github.com/xpressengine/xpressengine "XE3 Git") 코어 3.0.13 이상이 필요합니다.

# Features

### Multisite
- [XpressEngine3](https://github.com/xpressengine/xpressengine "XE3 Git")  에 멀티사이트 기능을 추가합니다.
- 새로운 사이트를 등록하고, 사이트목록 모듈을 통해 게시할 수 있습니다.
- 사이트 등록시 호스트와 사이트이름을 결정하고, 활성화 할 익스텐션을 선택할 수 있습니다.
- 사이트 등록시 사용할 테마를 선택할 수 있습니다. (해당 테마를 포함한 플러그인은 강제로 활성화됩니다)

### Permisson
- 폐쇄형 웹사이트를 운영할 수 있습니다. (사이트별 설정 가능)
- 사이트별 운영 그룹을 지정하고, 그룹별 접근메뉴 권한을 제어할 수 있습니다. (setting Menus 참조)
- 소유자와 관리자가 구분됩니다.

### Domain
- 사이트별 도메인, Alias 등록을 관리할 수 있습니다.
- 기본도메인을 선택할 수 있고 기본도메인을 제외한 Alias도메인의경우 기본도메인으로 리다이렉트 할지, 기본도메인을 유지할지 결정할 수 있습니다.
- 사이트별로 SSL을 제어할 수 있습니다.
- 도메인 Alias의 시작 인스턴스를 변경할 수 있습니다. 활용에 따라 한 사이트에서 여러 사이트처럼 보여질 수 있습니다.

### Setting Menus
- 관리자 메뉴를 제어할 수 있습니다.
- 메뉴별 접근권한, 그룹 등을 설정할 수 있고 관리자메뉴에 접근할 수 있는 권한이 하나라도 있으면 회원이든 비회원이든 관리자페이지에 접근할 수 있습니다.
- 메뉴별 순서, 위치, 아이콘 등을 변경하고 제어할 수 있습니다.


# Components

- 사이트 목록 모듈
- 사이트 목록 모듈 기본스킨
- 사이트 목록 모듈 설정스킨
- 사이트 목록 출력 위젯
- 사이트 목록 출력 위젯 기본스킨


# Settings

- 사이트맵 메뉴가 '사이트' 로 이름이 대체됩니다.
- 코어의 기본 설정 이 사이트설정 으로 대체되고 탭이 추가됩니다.
- 메타정보와 도메인설정, 회원, 권한, 메뉴 아이콘 및 위치/이름 표시변경 등에 대한 권한설정이 추가됩니다.


# 3rd-Party

- 써드파티 플러그인에서 사이트 메타를 등록할 수 있습니다.
```php
\XeRegister::push('multisite/site_info', 'setting.store_info', [
    'title' => '상점정보',
    'description' => '상점정보를 입력합니다.',
    'display' => true,
    'ordering' => 100
]);
\XeRegister::push('multisite/site_info', 'setting.store_info.type', [
    "_type" => "formSelect",
    "size" => "col-sm-12",
    "uio" => ['name'=>'type', 'label'=>'상점구분', 'options' => self::$store_types]
]);
```

***

# 향후 업데이트

* 이 플러그인의 업데이트는 필수적으로 코어빌드가 동반되어야 하는 경우가 많습니다. 발견되지 않은 많은 문제가 있을 수 있으며, 내부 프로젝트 등에 활용하기에 적합합니다.
* 사이트별로 로그인 시 약관동의 후 해당 사이트의 그룹에 추가되는 기능
* 하위 사이트에서 회원삭제시 그룹에서만 삭제되도록 수정
* 사이트 상태 활성화/비활성화 여부 결정
* 웹사이트별 상태설정 (운영/파킹/중단)
* 사이트별 구독설정 (유료화된 분양형 웹사이트 제공)

# Patch Note

### 1.0.3 - 21.08.04
* LangPreProcessor와 SetSiteGrant Middleware 간의 충돌 해결
* 하위사이트 권한자가 메뉴 아이템 중복생성하는 문제 해결

### 1.0.2 - 21.08.03
* 하위사이트 권한자가 Lang 관련 설정 저장 불가능한 문제 미들웨어 Web 그룹의 LangPreProcessor 순서변경해서 해결
* 하위사이트 권한자가 프론트화면에서 관리권한 얻지 못하는 문제 해결

### 1.0.1 - 21.08.03
* XE3 코어설정 xe.php 내의 SSL => [ 'Always' => true ] 설정에 대응


***

## 발견된 문제
#### 다음 파일과 충돌 가능성이 있습니다. 무한 리다이렉트가 발생한다면 다음 옵션을 제거하세요.
*./config/production/xe.php*

```php
// 사이트별 SSL 제어를 위해 이 구문은 가능한 삭제하는 것을 권장합니다.
'ssl' => [
    'always' => true
]
```

#### 사이트 생성시 디버그가 활성화 되어야 하는 문제가 있습니다.
*./config/production/app.php*
```php
// debug 가 false 되어있으면 새 사이트가 생성되지 않을 수 있습니다.
return [
    'debug' => true,
...
```

