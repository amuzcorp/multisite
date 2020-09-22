## Multisite Reposity

XE3를 하나의 구동환경에서 무제한 웹사이트로 확장할 수 있도록 합니다.

<hr />

#### 사전작업

> XE3는 현재 라라벨 5.5기반으로 개발되어있으며 2021년까지 버전없의 계획이 없음. (7.0으로 업데이트 예정)
> 이에따라, 컴포저와 일부 파일 수정을 통한 라라벨 5.6으로의 버전업데이트가 필요합니다.
   
composer.json을 수정합니다.   
   
```
  "require": {   
        "php": ">=7.0.0",   
        "fideloper/proxy": "~3.3",   
        "laravel/framework": "5.5.*",   
        "laravel/tinker": "~1.0"   
    },   
```
   
에서, 아래와 같이 수정합니다.   
   
```
 "require": {   
        "php": ">=7.1.3",   
        "fideloper/proxy": "~4.0",   
        "laravel/framework": "5.6.*",   
        "laravel/tinker": "~1.0"   
    },   
```

``
app/Http/Middleware/TrustProxies.php
``
파일을 수정합니다.

```
    protected $headers = [
        Request::HEADER_FORWARDED => 'FORWARDED',
        Request::HEADER_X_FORWARDED_FOR => 'X_FORWARDED_FOR',
        Request::HEADER_X_FORWARDED_HOST => 'X_FORWARDED_HOST',
        Request::HEADER_X_FORWARDED_PORT => 'X_FORWARDED_PORT',
        Request::HEADER_X_FORWARDED_PROTO => 'X_FORWARDED_PROTO',
    ];
```
에서, 아래와같이 수정합니다.
```
 protected $headers = Request::HEADER_X_FORWARDED_ALL;
```
