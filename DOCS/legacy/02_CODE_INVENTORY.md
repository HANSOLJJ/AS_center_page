# Phase 1: 코드 인벤토리 & 의존성 분석

**작성일**: 2025-10-23  
**분석 대상**: E:\web_shadow\mic4u\www\@@as\as  
**총 파일 수**: 469개 PHP 파일  
**총 디렉토리 수**: 38개

---

## 📁 1. 디렉토리 구조 요약

```
@@as/as/
├── [핵심 설정 파일]
│   ├── @config.php          (전역 설정: DB 연결, 테이블 정의, 스타일 변수)
│   ├── @session_inc.php     (세션 초기화)
│   ├── @access.php          (접근 제어: 로그인 확인)
│   ├── @error_function.php  (에러 처리 함수)
│
├── [인증 파일]
│   ├── index.php            (로그인 페이지 메인)
│   ├── admin_login_process.php   (관리자 로그인 처리)
│   ├── as_login_process.php      (AS 직원 로그인 처리)
│   ├── logout.php           (로그아웃)
│
├── [대시보드/프레임]
│   ├── admin_frame.php      (관리자 대시보드 메인 프레임 - iframe 구조)
│   ├── admin_menu.php       (관리자 메뉴 - 레벨 2)
│   ├── admin_menu_center.php (센터관리자 메뉴 - 레벨 0)
│   ├── admin_menu_raintrace.php (추적자 메뉴 - 레벨 3)
│   ├── welcome.php          (환영 페이지)
│
├── as_center/               (센터 1 - AS 신청/처리/결과) - 38개 파일
│   ├── list.php             (페이지 라우팅)
│   ├── write*.php, write*_process.php (4단계 폼)
│   ├── modify.php, modify_process.php
│   ├── del.php, del_process.php
│   ├── cure_*.php           (수리 항목 관리)
│   ├── include.step*.php    (단계별 include 파일)
│   └── ...
│
├── as_center1/              (센터 2) - 45개 파일
├── as_center2/              (센터 3) - 24개 파일
├── as_center3/              (센터 4) - 29개 파일
├── as_center4/              (센터 5) - 25개 파일
├── as_center5/              (센터 6) - 19개 파일
│
├── parts/                   (부품 관리) - 9개 파일
│   ├── write.php, write_process.php
│   ├── list.php, list_view.php
│   ├── modify.php, modify_process.php
│   └── del.php, del_process.php
│
├── parts_category/          (부품 카테고리) - 8개 파일
├── parts_count/             (부품 수량) - 2개 파일
│
├── center/                  (센터 기본) - 8개 파일
├── center_parts/            (센터 부품) - 1개 파일
├── center_parts_order/      (부품 주문) - 13개 파일
├── center_parts_order_list/ (주문 현황) - 2개 파일
├── center_out_list/         (출고 현황) - 3개 파일
│
├── order/                   (AS 주문) - 5개 파일
├── out/                     (출고) - 13개 파일
├── out_list/                (출고 현황) - 3개 파일
│
├── member/                  (직원 관리) - 8개 파일
├── client/                  (클라이언트) - 3개 파일
│
├── result/                  (AS 결과) - 15개 파일
├── result_sell/             (판매 결과) - 7개 파일
│
├── as_item/                 (AS 제품) - 8개 파일
├── as_poor/                 (부실 사유) - 4개 파일
├── as_result/               (AS 결과 추가) - 4개 파일
│
├── tax/                     (세금/청구) - 12개 파일
│
├── bank/                    (뱅크 거래) - 26개 파일
├── bank_sell/               (판매 뱅크) - 8개 파일
│
├── sell/                    (판매) - 37개 파일
│
├── sms_db/                  (SMS DB) - 8개 파일
├── sms/                     (SMS 발송) - 1개 파일
│
├── pw/                      (비밀번호) - 3개 파일
├── exl/                     (엑셀) - 12개 파일
├── as_center_export/        (데이터 내보내기) - 2개 파일
├── print/                   (인쇄) - 1개 파일
├── sample/                  (샘플) - 1개 파일
│
├── jqu/                     (jQuery 라이브러리)
│
└── MIGRATION_DOCS/          (마이그레이션 문서) ← 현재 위치
```

---

## 🔗 2. 핵심 Include 파일 분석

### 2.1 @config.php (가장 중요)

**역할**: 전역 설정 파일 - 모든 페이지에서 include됨

**주요 내용**:
```php
// 세션 시작
session_start();

// 관리자 정보
$admin_email = "digitalcoms@digitalcoms.net";
$admin_name  = "스마트과자 AS 관리 시스템";

// DB 연결 (deprecated mysql_* 함수 사용)
$mysql_host = 'localhost';
$mysql_user = 'mic4u41';
$mysql_pwd  = 'digidigi';
$mysql_db   = 'mic4u41';
$connect = @mysql_connect($mysql_host, $mysql_user, $mysql_pwd);
@mysql_select_db($mysql_db, $connect);

// DB 테이블명 상수 (21개)
$db1 = 'step1_parts';
$db2 = 'step2_center';
// ... $db21
```

**마이그레이션 작업**:
- ❌ `mysql_connect()` → ✅ `PDO` 또는 `MySQLi`로 변경
- ❌ `mysql_select_db()` → ✅ DSN에 포함
- 환경 변수로 설정 외부화 (.env 파일)
- 에러 처리 개선

**영향받는 파일**: 469개 (모든 파일)

---

### 2.2 @session_inc.php

**역할**: 세션 초기화

**현재 코드**:
```php
<?php
session_start();
?>
```

**문제점**:
- @config.php에서도 session_start() 호출됨 → 중복
- 보안 옵션 미설정 (HttpOnly, Secure, SameSite)

**마이그레이션 작업**:
```php
<?php
// 세션 보안 설정 (PHP 7.3+)
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => 'mic4u.co.kr',
    'secure' => true,           // HTTPS only
    'httponly' => true,         // JavaScript 접근 차단
    'samesite' => 'Strict'      // CSRF 방지
]);

session_start();
?>
```

---

### 2.3 @access.php

**역할**: 접근 제어 - 로그인 확인

**현재 코드**:
```php
<?php
if($HTTP_SESSION_VARS["member_id"] =="" AND $HTTP_SESSION_VARS["member_sid"] =="")  {
   echo ("<meta http-equiv='Refresh' content='0; URL=index.php'>");
   exit;
}
?>
```

**문제점**:
- `$HTTP_SESSION_VARS` deprecated (PHP 5.4+) → `$_SESSION` 사용
- Meta refresh 대신 `header()` 사용
- 권한 검증 없음 (모든 페이지에서 동일하게 확인)

**마이그레이션 작업**:
```php
<?php
// 로그인 확인
if (!isset($_SESSION['member_id']) || !isset($_SESSION['member_sid'])) {
    header("Location: index.php", true, 302);
    exit;
}

// 권한 검증 추가 (선택사항)
// if ($_SESSION['userlevel'] < 1) {
//     die("권한이 없습니다.");
// }
?>
```

---

### 2.4 @error_function.php

**역할**: 에러 처리 함수

**현재 예상 구조**:
```php
<?php
function error($msg) {
   echo("ERROR: $msg");
   exit;
}
?>
```

**마이그레이션 작업**:
- 구조화된 에러 로깅
- 사용자 친화적 메시지
- 관리자 이메일 알림

---

## 📋 3. 파일별 분류

### 3.1 핵심 모듈 (Most Critical)

| 디렉토리 | 파일 수 | 우선순위 | 복잡도 | 설명 |
|---------|--------|---------|--------|------|
| **as_center/** | 38 | 🔴 높음 | ⭐⭐⭐ | AS 신청 핵심 - 4단계 폼 처리 |
| **result/** | 15 | 🔴 높음 | ⭐⭐ | AS 결과/보고서 |
| **bank/** | 26 | 🟠 중간 | ⭐⭐⭐ | 뱅크 거래 관리 |

### 3.2 중요 모듈 (Important)

| 디렉토리 | 파일 수 | 우선순위 | 복잡도 |
|---------|--------|---------|--------|
| **parts/** | 9 | 🟠 중간 | ⭐⭐ |
| **center/** | 8 | 🟠 중간 | ⭐ |
| **member/** | 8 | 🟠 중간 | ⭐ |
| **tax/** | 12 | 🟠 중간 | ⭐⭐ |
| **sell/** | 37 | 🟠 중간 | ⭐⭐⭐ |

### 3.3 보조 모듈 (Supporting)

| 디렉토리 | 파일 수 | 복잡도 |
|---------|--------|--------|
| center_parts_order/ | 13 | ⭐⭐ |
| order/ | 5 | ⭐⭐ |
| out/ | 13 | ⭐⭐ |
| sms_db/ | 8 | ⭐ |
| exl/ | 12 | ⭐ |

---

## 🔄 4. 페이지 라우팅 패턴

### 4.1 Standard CRUD Pattern

```
list.php → list_view.php (조회)
    ↓ (다른 페이지에서 링크)
write.php → write_process.php (생성) → 결과 페이지로 리다이렉트
modify.php → modify_process.php (수정) → 결과 페이지로 리다이렉트
del.php → del_process.php (삭제) → 결과 페이지로 리다이렉트
```

**문제점**:
- URL에 데이터가 노출됨 (GET 파라미터)
- 리다이렉트 후 POST-Redirect-GET 패턴 미적용
- 폼 CSRF 토큰 없음

**예시 (as_center/write_process.php)**:
```php
<?php
include "../@config.php";
include "../@access.php";

$s13_as_center = $_POST['s13_as_center'];
// ... 폼 데이터 처리 ...

// 결과 저장
$query = "INSERT INTO $db13 (...) VALUES (...)";
$result = mysql_query($query);

// 리다이렉트 (GET 파라미터 포함)
echo ("<meta http-equiv='Refresh' content='0; URL=list.php?in_code=write2&s13_as_center=$s13_as_center'>");
?>
```

**마이그레이션 패턴**:
```php
<?php
// 1. POST 데이터 수신 및 검증
$validator = new FormValidator();
$data = [
    's13_as_center' => $_POST['s13_as_center'] ?? null,
    // ... 다른 필드
];

if (!$validator->validate($data, $rules)) {
    $_SESSION['errors'] = $validator->getErrors();
    header("Location: write.php", true, 302);
    exit;
}

// 2. DB 저장
try {
    $stmt = $pdo->prepare("INSERT INTO step13_as (...) VALUES (...)");
    $stmt->execute(array_values($data));
    $newId = $pdo->lastInsertId();
    
    // 3. 성공 메시지 저장 (세션)
    $_SESSION['success'] = "AS 신청이 저장되었습니다.";
    
    // 4. 리다이렉트 (POST-Redirect-GET)
    header("Location: list.php?id=$newId", true, 303);
    exit;
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    $_SESSION['error'] = "저장 중 오류가 발생했습니다.";
    header("Location: write.php", true, 302);
    exit;
}
?>
```

### 4.2 Multi-step Form Pattern (as_center)

```
Step 1: list.php (write 링크 클릭)
   ↓
Step 2: write.php (센터 선택)
   ↓ (submit)
Step 3: write1_process.php (검증 & 저장)
   ↓
Step 4: write2-1.php 또는 write2-2.php (조건에 따라 분기)
   ↓ (submit)
Step 5: write2_process.php
   ↓
Step 6: write3.php
   ↓ (submit)
Step 7: write3_process.php
   ↓
Step 8: write4.php (최종 확인)
   ↓ (submit)
Step 9: 결과 저장 & 리다이렉트
```

**현재 문제점**:
- URL에 중간 데이터 노출 (`?s13_as_center=$value`)
- 뒤로가기로 데이터 손실 가능
- 타임아웃 시 입력 데이터 손실

**마이그레이션 전략**:
- 세션에 중간 데이터 저장
- AJAX 기반 Single Page Application으로 전환
- 또는 현재 구조 유지하되 URL 인코딩 개선

---

## 🔐 5. 보안 취약점 분석

### 5.1 SQL Injection (매우 위험)

**발생 위치**: 거의 모든 PHP 파일

**예시**:
```php
// parts/write_process.php
$s1_name = $_POST['s1_name'];
$query = "INSERT INTO $db1 (...) VALUES ('$s1_name', ...)";
$result = mysql_query($query);
```

**위험도**: 🔴 매우 높음  
**영향**: 데이터 유출, 조작, 삭제, 악의적 쿼리 실행

**마이그레이션 작업**: 모든 쿼리를 Prepared Statements로 변환
```php
$stmt = $pdo->prepare("INSERT INTO step1_parts (...) VALUES (?, ?)");
$stmt->execute([$s1_name, ...]);
```

---

### 5.2 XSS (Cross-Site Scripting)

**발생 위치**: 목록 페이지 (list_view.php), 상세 페이지 (view.php)

**예시**:
```php
// result/view.php
echo "<td>" . $row->s19_result_detail . "</td>";  // HTML 이스케이핑 없음
```

**위험도**: 🔴 높음  
**영향**: 쿠키 탈취, 세션 하이재킹, 악성 스크립트 실행

**마이그레이션 작업**: 모든 출력에 htmlspecialchars() 적용
```php
echo "<td>" . htmlspecialchars($row->s19_result_detail, ENT_QUOTES, 'UTF-8') . "</td>";
```

---

### 5.3 CSRF (Cross-Site Request Forgery)

**발생 위치**: 모든 form submit

**현재**: 토큰 없음

**위험도**: 🟠 중간  
**영향**: 권한 없는 사용자가 계정으로 행동 가능

**마이그레이션 작업**:
```php
// 폼 생성 시
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- 폼 필드 -->
</form>

// 처리 시
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF 토큰이 유효하지 않습니다.");
}
```

---

### 5.4 Weak Password Hashing

**현재**: MySQL `PASSWORD()` 함수
```php
$pass = $_POST['password'];
$query = "INSERT INTO $db3 (..., s3_passwd) VALUES (..., PASSWORD('$pass'))";
```

**문제점**:
- PASSWORD() 함수는 보안이 약함
- 단방향 변환이지만 해킹 취약
- 소금(salt) 없음

**마이그레이션 작업**:
```php
$password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 10]);
$stmt = $pdo->prepare("INSERT INTO step3_member (..., s3_passwd) VALUES (..., ?)");
$stmt->execute([..., $password_hash]);

// 검증
if (password_verify($_POST['password'], $row->s3_passwd)) {
    // 로그인 성공
}
```

---

### 5.5 기타 보안 문제

| 문제 | 현재 | 목표 | 우선순위 |
|------|------|------|---------|
| Error Display | 화면에 표시 | 로그 파일에만 | 🔴 높음 |
| Session Timeout | 없음 | 30분 | 🟠 중간 |
| HTTPS | 미적용 | 필수 | 🔴 높음 |
| Input Validation | 최소한 | 엄격한 검증 | 🔴 높음 |
| Rate Limiting | 없음 | 로그인 시도 제한 | 🟠 중간 |
| Logging | 없음 | 구조화된 로깅 | 🟠 중간 |

---

## 📦 6. 외부 라이브러리 & 의존성

### 6.1 jQuery (jqu/ 디렉토리)

**파일 구조**:
```
jqu/
├── jquery-1.4.2.min.js           (2010년 버전 - 극히 낡음!)
├── jquery.autocomplete.js        (자동완성)
├── jquery.validate.js            (폼 검증)
├── jquery.blockUI.js             (UI 차단)
├── jquery.galleryview-2.1.1.js   (이미지 갤러리)
└── demo/                         (데모 페이지)
```

**문제점**:
- jQuery 1.4.2는 2010년 버전
- 현재 최신 버전은 3.6.0 (2021년)
- 보안 업데이트 없음

**마이그레이션 작업**:
- jQuery 3.6+ 또는 Vanilla JavaScript로 변경
- 공식 CDN 사용 또는 npm으로 관리
- 자동완성, 폼 검증 기능 현대화

---

### 6.2 Flash Assets

**참고**: style.css, flash.js, flashObject.js 포함

**문제점**:
- Flash는 2020년에 공식 지원 종료
- 보안 취약점 심각
- 모던 브라우저에서 차단

**마이그레이션 작업**:
- Flash 애니메이션 → CSS/JavaScript 애니메이션으로 변경
- 또는 제거

---

## 🗂️ 7. 파일 간 의존성 그래프

### 7.1 의존성 깊이

```
@config.php (깊이 0 - 모든 파일이 include)
   ↓ include
@session_inc.php (깊이 1)
   ↓ include
@access.php (깊이 1)
   ↓ include
@error_function.php (깊이 1)
   ↓ include

모든 페이지 파일 (깊이 2+)
   ├─ list.php
   ├─ write.php
   ├─ write_process.php
   ├─ modify.php
   ├─ modify_process.php
   └─ del_process.php
```

### 7.2 주요 include 호출

```php
// 거의 모든 페이지의 시작
<?php
include "../@config.php";      // ← 환경 변수, DB 연결 설정
include "../@access.php";      // ← 로그인 확인
// 또는
include "@access.php";
?>
```

---

## 📊 8. 마이그레이션 파일 분류

### Phase 2-1: 즉시 변환 필요 (High Priority)

| 파일 | 변환 | 이유 |
|------|------|------|
| @config.php | 🔴 필수 | mysql_* 제거, PDO 사용 |
| @access.php | 🔴 필수 | $_SESSION 사용, header() 사용 |
| 모든 *_process.php | 🔴 필수 | SQL Injection 방지, Prepared Statements |
| 모든 list_view.php | 🔴 필수 | XSS 방지, htmlspecialchars() |

### Phase 2-2: 점진적 변환 (Medium Priority)

| 파일 | 변환 | 이유 |
|------|------|------|
| write.php | 🟡 선택 | 현재 구조 유지 가능 |
| view.php | 🟡 선택 | 데이터 표시만 개선하면 됨 |

### Phase 3: 리팩토링 (Low Priority)

| 파일 | 변환 | 이유 |
|------|------|------|
| jqu/* | 🟢 나중 | jQuery 업그레이드 |
| flash.js | 🟢 나중 | Flash 제거 |

---

## ✅ 9. 마이그레이션 체크리스트

### Phase 1 (기초 준비) - 현재 단계
- [x] 파일 구조 분석
- [x] 의존성 맵핑
- [x] 보안 취약점 식별
- [ ] 현재 데이터베이스 백업
- [ ] 신규 호스팅 환경 준비
- [ ] 로컬 개발 환경 구성

### Phase 2-1 (PHP 코드 현대화)
- [ ] @config.php PDO 변환
- [ ] 모든 mysql_query() 제거
- [ ] Prepared Statements 적용
- [ ] 입력 검증 추가
- [ ] XSS 방지 (htmlspecialchars)

### Phase 2-2 (보안 강화)
- [ ] CSRF 토큰 구현
- [ ] 비밀번호 해싱 (password_hash)
- [ ] 세션 보안 옵션
- [ ] 에러 로깅 구축

### Phase 3 (UI 현대화)
- [ ] jQuery 업그레이드
- [ ] Flash 제거
- [ ] 반응형 디자인 (선택사항)

---

## 📝 10. 다음 단계

1. **현재 데이터베이스 백업** (`01_DATABASE_SCHEMA.md` 참고)
2. **신규 호스팅 환경 확인** (`03_HOSTING_SETUP.md` 예정)
3. **로컬 개발 환경 구성** (PHP 7.4+, MariaDB 10.x)
4. **@config.php 리팩토링** (Phase 2-1 시작)

이 문서는 마이그레이션 진행 중 지속적으로 업데이트됩니다.
