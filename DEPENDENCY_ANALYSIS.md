# AS 시스템 의존성 분석 보고서

**작성일**: 2025-11-10  
**프로젝트**: mic4u AS (Application/Submission) System  
**분석 범위**: as/ 폴더의 주요 PHP 파일들  
**인코딩**: EUC-KR (UTF-8로 마이그레이션 완료)

---

## 목차
1. [주요 의존성 그룹](#주요-의존성-그룹)
2. [공통 의존성](#공통-의존성)
3. [데이터베이스 테이블 맵핑](#데이터베이스-테이블-맵핑)
4. [JavaScript 라이브러리 맵핑](#javascript-라이브러리-맵핑)
5. [파일 간 호출 관계](#파일-간-호출-관계)
6. [AJAX 및 핸들러](#ajax-및-핸들러)
7. [보안 고려사항](#보안-고려사항)

---

## 주요 의존성 그룹

### 1. 자재 관리 (Parts Management)

#### 포함 파일 구조
```
parts.php (메인 페이지)
├── mysql_compat.php (MySQL 호환성 레이어)
├── @config.php (데이터베이스 설정)
│   └── @session_inc.php (세션 초기화)
├── parts_add.php (새 자재 등록)
└── parts_edit.php (자재 정보 수정)
```

#### 각 파일의 역할

**parts.php** (E:\web_shadow\mic4u\www\as\parts.php)
- **용도**: AS 자재 관리 메인 페이지 (5개 탭)
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `step1_parts`: AS 자재 (s1_uid, s1_name, s1_caid, s1_cost_c_1, s1_cost_a_1, s1_cost_a_2, s1_cost_n_1, s1_cost_n_2, s1_cost_s_1)
  - `step5_category`: 자재 카테고리 (s5_caid, s5_category)
- **주요 기능**:
  - Tab1: AS 자재 관리 (목록, 검색, 페이징, AJAX 삭제)
  - Tab2: 자재 카테고리 관리 (DESC 정렬, AJAX 삭제)
  - Tab3-5: 스켈레톤 구조 (미구현)
- **JavaScript**: jQuery (자동완성 없음, blockUI 로딩 가능)
- **세션 변수**: `$_SESSION['member_id']`, `$_SESSION['member_sid']`
- **삭제 메커니즘**: GET 기반 (?action=delete_part&id=xx)

**parts_add.php** (E:\web_shadow\mic4u\www\as\parts_add.php)
- **용도**: 새 자재 등록 폼
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **INSERT 대상 테이블**: `step1_parts`
- **필드**:
  - s1_name: 자재명 (필수)
  - s1_caid: 카테고리 ID (선택)
  - s1_cost_c_1: AS center 공급가
  - s1_cost_a_1: 대리점 개별 판매가
  - s1_cost_a_2: 대리점 수리용 판매가
  - s1_cost_n_1: 일반 개별 판매가
  - s1_cost_n_2: 일반 수리용 판매가
  - s1_cost_s_1: 특별공급가
- **성공 시 리다이렉트**: `parts.php?inserted=1`

**parts_edit.php** (E:\web_shadow\mic4u\www\as\parts_edit.php)
- **용도**: 자재 정보 수정 폼
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step1_parts`
- **선택 로직**: GET 파라미터 `?id=xx`로 기존 데이터 미리 로드
- **카테고리**: 동적 드롭다운 생성 (step5_category 조회)

**category_add.php** (E:\web_shadow\mic4u\www\as\category_add.php)
- **용도**: 새 카테고리 등록
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **INSERT 대상 테이블**: `step5_category`
- **필드**:
  - s5_caid: 자동 생성 (4자리, zero-padded, 예: 0001, 0002)
  - s5_category: 카테고리명 (필수)

**category_edit.php** (E:\web_shadow\mic4u\www\as\category_edit.php)
- **용도**: 카테고리 정보 수정
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step5_category`
- **읽기 전용**: s5_caid (수정 불가)
- **편집 가능**: s5_category

---

### 2. 제품 관리 (Products)

#### 포함 파일 구조
```
products.php (메인 페이지)
├── mysql_compat.php (MySQL 호환성 레이어)
├── @config.php (데이터베이스 설정)
├── product_add.php (새 제품 등록)
├── product_edit.php (제품 정보 수정)
├── poor_add.php (불량증상 타입 등록)
├── poor_edit.php (불량증상 타입 수정)
├── result_add.php (AS 결과 타입 등록)
└── result_edit.php (AS 결과 타입 수정)
```

#### 각 파일의 역할

**products.php** (E:\web_shadow\mic4u\www\as\products.php)
- **용도**: 제품 관리 메인 페이지 (3개 탭)
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `step15_as_model`: 제품 모델 (s15_amid, s15_model_name, s15_model_sn)
  - `step16_as_poor`: 불량증상 타입 (s16_apid, s16_poor)
  - `step19_as_result`: AS 결과 타입 (s19_asrid, s19_result)
- **주요 기능**:
  - Tab: model - 모델 관리 (목록, 검색, 페이징, AJAX 삭제)
  - Tab: poor - 불량증상 타입 관리 (목록, 검색, AJAX 삭제)
  - Tab: result - AS 결과 타입 관리 (목록, 검색, AJAX 삭제)
- **UI 특성**: Column 경계선 추가 (border-right), 마지막 column 경계선 제거
- **삭제 메커니즘**: GET 기반 (?action=delete_model&id=xx)

**product_add.php** (E:\web_shadow\mic4u\www\as\product_add.php)
- **용도**: 새 제품 모델 등록 폼
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **INSERT 대상 테이블**: `step15_as_model`
- **필드**:
  - s15_model_name: 모델명 (필수)
  - s15_model_sn: 시리얼 및 버전 (필수)
- **성공 시 리다이렉트**: `products.php?tab=model&inserted=1`

**product_edit.php** (E:\web_shadow\mic4u\www\as\product_edit.php)
- **용도**: 제품 모델 정보 수정 폼
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step15_as_model`
- **선택 로직**: GET 파라미터 `?id=xx`로 기존 데이터 미리 로드

**poor_add.php** (E:\web_shadow\mic4u\www\as\poor_add.php)
- **용도**: 새 불량증상 타입 등록 폼
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **INSERT 대상 테이블**: `step16_as_poor`
- **필드**:
  - s16_poor: 불량증상명 (필수)

**poor_edit.php** (E:\web_shadow\mic4u\www\as\poor_edit.php)
- **용도**: 불량증상 타입 정보 수정 폼
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step16_as_poor`

**result_add.php** (E:\web_shadow\mic4u\www\as\result_add.php)
- **용도**: 새 AS 결과 타입 등록 폼
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **INSERT 대상 테이블**: `step19_as_result`
- **필드**:
  - s19_result: AS 결과명 (필수)

**result_edit.php** (E:\web_shadow\mic4u\www\as\result_edit.php)
- **용도**: AS 결과 타입 정보 수정 폼
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step19_as_result`

---

### 3. 고객 관리 (Members)

#### 포함 파일 구조
```
members.php (메인 페이지)
├── mysql_compat.php (MySQL 호환성 레이어)
├── @config.php (데이터베이스 설정)
├── member_add.php (새 고객 등록)
└── member_edit.php (고객 정보 수정)
```

#### 각 파일의 역할

**members.php** (E:\web_shadow\mic4u\www\as\members.php)
- **용도**: 고객 정보 관리 메인 페이지
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `step11_member`: 고객 정보 (s11_meid, s11_sec, s11_com_name, s11_phone1, s11_phone2, s11_phone3 등)
- **주요 기능**:
  - 고객 목록 조회 (페이징: 10개씩)
  - 검색 (업체명 또는 전화번호)
  - AJAX 삭제 (POST 기반)
- **UI 특성**: Column 경계선 추가, 모든 셀 center 정렬
- **AJAX 삭제**: POST로 action=delete, id=xx 전송

**member_add.php** (E:\web_shadow\mic4u\www\as\member_add.php)
- **용도**: 새 고객 등록 폼
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **INSERT 대상 테이블**: `step11_member`
- **필드**:
  - s11_sec: 분류 (일반, 대리점, 딜러 등)
  - s11_com_name: 업체명 (필수)
  - s11_phone1, s11_phone2, s11_phone3: 전화번호 (필수)
  - 기타 필드는 기본값으로 자동 입력
- **성공 시 리다이렉트**: `members.php?inserted=1`

**member_edit.php** (E:\web_shadow\mic4u\www\as\member_edit.php)
- **용도**: 고객 정보 수정 폼
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step11_member`
- **선택 로직**: GET 파라미터 `?id=xx`로 기존 데이터 미리 로드

---

### 4. 주문/판매 관리 (Orders/Sales)

#### 포함 파일 구조
```
orders.php (메인 페이지 - 2탭)
├── mysql_compat.php (MySQL 호환성 레이어)
├── @config.php (데이터베이스 설정)
├── order_handler.php (AJAX 핸들러)
├── order_edit.php (주문 정보 수정)
├── order_payment.php (결제 상태 업데이트)
└── receipt.php (영수증 출력)
```

#### 각 파일의 역할

**orders.php** (E:\web_shadow\mic4u\www\as\orders.php)
- **용도**: 주문/판매 관리 메인 페이지
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `step20_sell`: 판매 주문 (s20_sellid, s20_as_level, s20_sell_in_date, s20_as_out_date 등)
  - `step21_sell_cart`: 판매 장바구니 상세 (s21_sellid 참조)
  - `step11_member`: 고객 정보 (조인)
- **탭 구조**:
  - Tab1 (request): 판매요청 (s20_as_level != '2')
  - Tab2 (completed): 판매완료 (s20_as_level = '2')
- **주요 기능**:
  - 탭별 주문 목록 조회 (페이징: 10개씩)
  - 검색 (고객명, 전화번호, 기간)
  - 상태 필터
  - 삭제 (외래키 제약 고려)
- **날짜 필드**: 
  - request: s20_sell_in_date (접수일자)
  - completed: s20_as_out_date (완료일자)

**order_handler.php** (E:\web_shadow\mic4u\www\as\order_handler.php)
- **용도**: 주문 관련 AJAX 핸들러
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **주요 액션**:
  - `search_member`: 고객 검색 (step11_member)
  - `add_member`: 새 고객 추가 (step11_member)
  - `delete_order`: 주문 삭제 (step20_sell, step21_sell_cart 함께 처리)
- **응답 형식**: JSON

**order_edit.php** (E:\web_shadow\mic4u\www\as\order_edit.php)
- **용도**: 주문 정보 수정 폼
- **포함 파일**:
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step20_sell`
- **선택 로직**: GET 파라미터 `?id=xx`로 기존 데이터 미리 로드

**order_payment.php** (E:\web_shadow\mic4u\www\as\order_payment.php)
- **용도**: 결제 상태 업데이트
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **UPDATE 대상 테이블**: `step20_sell`
- **주요 필드**: s20_bank_check (입금 확인 날짜)

**receipt.php** (E:\web_shadow\mic4u\www\as\receipt.php)
- **용도**: 영수증 출력/조회
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **SELECT 대상 테이블**: 
  - `step20_sell` (주문 정보)
  - `step21_sell_cart` (상세 항목)
- **URL 파라미터**: `?id=xx` (sellid)
- **날짜 처리**: 유닉스 타임스탐프 및 datetime 형식 모두 지원

---

### 5. AS 요청 관리 (AS Requests)

#### 포함 파일 구조
```
as_requests.php (메인 페이지 - 4탭)
├── mysql_compat.php (MySQL 호환성 레이어)
├── @config.php (데이터베이스 설정)
├── as_request_handler.php (AJAX 핸들러)
└── as_request_view.php (AS 요청 상세 조회)
```

#### 각 파일의 역할

**as_requests.php** (E:\web_shadow\mic4u\www\as\as_requests.php)
- **용도**: AS 요청 관리 메인 페이지
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `step13_as`: AS 요청 (s13_asid, s13_meid, s13_as_level, s13_as_in_date, s13_as_out_date)
  - `step14_as_item`: AS 요청 아이템 (s14_aiid, s14_asid, s14_model, s14_poor)
  - `step11_member`: 고객 정보 (조인)
  - `step15_as_model`: 제품 모델 (조인)
  - `step16_as_poor`: 불량증상 (조인)
- **탭 구조**:
  - request: AS 요청 (s13_as_level NOT IN '2','3','4','5')
  - working: AS 진행 (s13_as_level IN '2','3','4')
  - completed: AS 완료 (s13_as_level = '5')
  - spare: Spare (별도 처리)
- **주요 기능**:
  - 탭별 요청 목록 조회 (페이징: 10개씩)
  - 검색 (고객명, 전화번호, 기간)
  - 삭제 (아이템 단위 또는 AS 요청 전체)
- **날짜 필드**: 
  - request/working: s13_as_in_date (입고일)
  - completed: s13_as_out_date (출고일)

**as_request_handler.php** (E:\web_shadow\mic4u\www\as\as_request_handler.php)
- **용도**: AS 요청 관련 AJAX 핸들러
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **주요 액션**:
  - `get_as_request_data`: 기존 AS 요청 정보 로드 (수정용)
  - `load_step3_data`: Step 3 데이터 로드 (모델, 불량증상)
  - 추가 액션은 파일 전체 검토 필요
- **응답 형식**: JSON

**as_request_view.php** (E:\web_shadow\mic4u\www\as\as_request_view.php)
- **용도**: AS 요청 상세 조회/인쇄
- **포함 파일**: 
  - `@config.php` (require_once)
  - `@error_function.php` (require_once)
  - `@access.php` (require_once)
- **SELECT 대상 테이블**: 
  - `step13_as` (AS 요청 정보)
  - `step14_as_item` (아이템 목록)
  - `step18_as_cure_cart` (사용된 자재)
  - `step11_member` (고객 정보)
  - `step15_as_model` (제품)
  - `step16_as_poor` (불량증상)
  - `step1_parts` (자재)
- **URL 파라미터**: `?id=xx` (asid)
- **출력 기능**: 인쇄 CSS 지원

---

### 6. AS 수리 관리 (AS Repair)

#### 포함 파일 구조
```
as_repair.php (메인 페이지)
├── mysql_compat.php (MySQL 호환성 레이어)
└── as_repair_handler.php (AJAX 핸들러)
```

#### 각 파일의 역할

**as_repair.php** (E:\web_shadow\mic4u\www\as\as_repair.php)
- **용도**: AS 수리 처리 페이지
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `step14_as_item`: AS 아이템 (s14_aiid, s14_asid, s14_model, s14_poor)
  - `step18_as_cure_cart`: 수리용 자재 (s18_accid, s18_aiid, s18_uid 등)
  - `step1_parts`: 자재 정보 (조인)
- **주요 기능**:
  - Step-by-step 수리 처리 UI
  - 수리 방법 선택
  - 자재 정보 입력 및 비용 계산
  - AJAX 기반 부품 로딩 및 페이징
- **JavaScript**: Fetch API 기반 (jQuery 미사용)
- **출력 기능**: 인쇄 지원

**as_repair_handler.php** (E:\web_shadow\mic4u\www\as\as_repair_handler.php)
- **용도**: AS 수리 관련 AJAX 핸들러
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **주요 액션**:
  - `save_repair_step`: 수리 방법 선택 및 자재 정보 저장
  - `load_parts`: 부품 목록 로드 (페이징 지원)
  - 추가 액션은 파일 전체 검토 필요
- **응답 형식**: JSON
- **대상 테이블**: 
  - `step14_as_item` (UPDATE)
  - `step18_as_cure_cart` (INSERT/UPDATE/DELETE)
  - `step1_parts` (SELECT)

---

### 7. 대시보드 (Dashboard)

#### 포함 파일 구조
```
dashboard.php (메인 페이지)
└── mysql_compat.php (MySQL 호환성 레이어)
```

#### 각 파일의 역할

**dashboard.php** (E:\web_shadow\mic4u\www\as\dashboard.php)
- **용도**: AS 시스템 대시보드 및 메뉴 네비게이션
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **데이터베이스 테이블**:
  - `2010_admin_member`: 관리자 계정 (사용자 정보 조회용)
  - 기타 통계 데이터 (필요에 따라)
- **주요 기능**:
  - 사용자 정보 표시
  - 메뉴 네비게이션 (parts, products, members, orders, as_requests, as_repair)
  - 로그아웃 기능
- **세션**: `$_SESSION['member_id']`, `$_SESSION['user_name']`, `$_SESSION['member_level']`

---

## 공통 의존성

### 파일 포함 계층 구조

```
모든 as/ PHP 파일
    ↓
mysql_compat.php (MySQL 호환성 레이어)
    ↓
    ├── mysqli_connect (Docker 'mysql' 서비스)
    ├── mysqli_select_db ('mic4u' DB)
    └── charset/collation 설정 (utf8mb4_unicode_ci)

plus (선택적):

as/ 관리자 페이지
    ↓
@config.php (설정 및 DB 초기화)
    ├── @session_inc.php (세션 시작)
    ├── @access.php (권한 확인)
    └── @error_function.php (에러 핸들링)
```

### 공통 설정 파일

#### mysql_compat.php (E:\web_shadow\mic4u\www\as\mysql_compat.php)
- **용도**: PHP 7.4+ 환경에서 deprecated mysql_* 함수 호환성 제공
- **주요 함수**:
  - `mysql_connect()`: 데이터베이스 연결
  - `mysql_select_db()`: DB 선택
  - `mysql_query()`: 쿼리 실행
  - `mysql_fetch_assoc()`, `mysql_fetch_array()`, `mysql_fetch_row()`: 결과 조회
  - `mysql_num_rows()`: 행 수
  - `mysql_affected_rows()`: 영향받은 행 수
  - `mysql_insert_id()`: 마지막 INSERT ID
  - `mysql_real_escape_string()`: SQL 이스케이프
  - `mysql_error()`, `mysql_errno()`: 에러 정보
- **내부 구현**: `mysqli_*` 함수 래핑
- **문자셋 설정**:
  ```
  mysqli_set_charset($link, 'utf8mb4');
  SET collation_connection = 'utf8mb4_unicode_ci'
  ```

#### @config.php (E:\web_shadow\mic4u\www\as\@config.php)
- **용도**: AS 시스템 전역 설정
- **포함 파일**: 
  - `@session_inc.php`
- **DB 연결 정보**:
  - Host: 'mysql' (Docker)
  - User: 'mic4u_user'
  - Password: 'change_me'
  - Database: 'mic4u'
- **전역 변수** (테이블 이름):
  ```php
  $db1 = 'step1_parts'
  $db2 = 'step2_center'
  $db3 = 'step3_member'
  $db4 = 'step4_cart'
  $db5 = 'step5_category'
  $db6 = 'step6_order'
  $db7 = 'step7_center_parts'
  $db8 = 'step8_sendbox'
  $db9 = 'step9_out'
  $db10 = 'step10_tax'
  $db11 = 'step11_member'
  $db12 = 'step12_sms_sample'
  ```

#### @session_inc.php (E:\web_shadow\mic4u\www\as\@session_inc.php)
- **용도**: 세션 초기화
- **포함 파일**: 
  - `mysql_compat.php` (require_once)
- **세션 변수**:
  - `$_SESSION['member_id']`: 로그인 ID
  - `$_SESSION['member_sid']`: 세션 ID (유효성 검증용)

#### @access.php (E:\web_shadow\mic4u\www\as\@access.php)
- **용도**: 권한 확인 (로그인 체크)
- **동작**: 로그인되지 않았으면 로그인 페이지로 리다이렉트

#### @error_function.php (E:\web_shadow\mic4u\www\as\@error_function.php)
- **용도**: 에러 메시지 표시
- **주요 기능**: JavaScript alert로 에러 메시지 표시

---

## 데이터베이스 테이블 맵핑

### 테이블 전체 목록 및 사용 현황

| No. | 테이블명 | 주요 필드 | 사용 페이지 | 용도 |
|-----|---------|---------|-----------|------|
| 1 | step1_parts | s1_uid, s1_name, s1_caid, s1_cost_c_1, s1_cost_a_1, s1_cost_a_2, s1_cost_n_1, s1_cost_n_2, s1_cost_s_1 | parts.php, parts_add.php, parts_edit.php, as_repair.php | AS 자재 관리 |
| 2 | step2_center | s2_center_id, s2_center, s2_center_tel | as_center/* | AS 센터 정보 |
| 3 | step3_member | - | (레거시) | 멤버 정보 (미사용) |
| 4 | step4_cart | - | (레거시) | 장바구니 (미사용) |
| 5 | step5_category | s5_caid, s5_category | parts.php, parts_add.php, parts_edit.php, category_add.php, category_edit.php | 자재 카테고리 |
| 6 | step6_order | - | (레거시) | 주문 (미사용) |
| 7 | step7_center_parts | - | (레거시) | 센터 자재 (미사용) |
| 8 | step8_sendbox | - | (레거시) | 발송박스 (미사용) |
| 9 | step9_out | - | (레거시) | 출고 (미사용) |
| 10 | step10_tax | - | (레거시) | 세금 (미사용) |
| 11 | step11_member | s11_meid, s11_sec, s11_com_name, s11_phone1, s11_phone2, s11_phone3 등 | members.php, member_add.php, member_edit.php, orders.php, as_requests.php, as_request_handler.php | 고객 정보 |
| 12 | step12_sms_sample | - | (레거시) | SMS 샘플 (미사용) |
| 13 | step13_as | s13_asid, s13_meid, s13_as_level, s13_as_in_date, s13_as_out_date, s13_as_in_how | as_requests.php, as_request_view.php, as_request_handler.php | AS 요청 |
| 14 | step14_as_item | s14_aiid, s14_asid, s14_model, s14_poor, s14_as_start_view | as_requests.php, as_request_view.php, as_request_handler.php, as_repair.php, as_repair_handler.php | AS 요청 아이템 |
| 15 | step15_as_model | s15_amid, s15_model_name, s15_model_sn | products.php, product_add.php, product_edit.php, as_requests.php, as_request_view.php, as_request_handler.php | 제품 모델 |
| 16 | step16_as_poor | s16_apid, s16_poor | products.php, poor_add.php, poor_edit.php, as_requests.php, as_request_view.php, as_request_handler.php | 불량증상 타입 |
| 18 | step18_as_cure_cart | s18_accid, s18_aiid, s18_uid, s18_cost, s18_quantity, s18_cost_sec | as_repair.php, as_repair_handler.php, as_request_view.php | 수리용 자재 |
| 19 | step19_as_result | s19_asrid, s19_result | products.php, result_add.php, result_edit.php | AS 결과 타입 |
| 20 | step20_sell | s20_sellid, s20_as_level, s20_sell_in_date, s20_as_out_date, s20_bank_check, ex_company, ex_tel, ex_man, ex_address | orders.php, order_edit.php, order_handler.php, order_payment.php, receipt.php | 판매 주문 |
| 21 | step21_sell_cart | s21_sellid, s21_uid, s21_quantity, s21_cost | orders.php, order_handler.php, receipt.php | 판매 장바구니 |
| - | 2010_admin_member | id, passwd, userlevel | dashboard.php, admin_login_process.php | 관리자 계정 |

### 테이블 간 외래키 관계

```
step13_as (AS 요청)
├── step13_as.s13_meid → step11_member.s11_meid
└── step13_as.s13_asid ← step14_as_item.s14_asid

step14_as_item (AS 아이템)
├── step14_as_item.s14_asid → step13_as.s13_asid
├── step14_as_item.s14_model → step15_as_model.s15_amid
├── step14_as_item.s14_poor → step16_as_poor.s16_apid
└── step14_as_item.s14_aiid ← step18_as_cure_cart.s18_aiid

step18_as_cure_cart (수리용 자재)
├── step18_as_cure_cart.s18_aiid → step14_as_item.s14_aiid
└── step18_as_cure_cart.s18_uid → step1_parts.s1_uid

step1_parts (자재)
└── step1_parts.s1_caid → step5_category.s5_caid

step15_as_model (제품)
└── (독립적)

step16_as_poor (불량증상)
└── (독립적)

step20_sell (판매 주문)
├── step20_sell.s20_meid → step11_member.s11_meid
└── step20_sell.s20_sellid ← step21_sell_cart.s21_sellid

step21_sell_cart (판매 아이템)
└── step21_sell_cart.s21_sellid → step20_sell.s20_sellid
```

---

## JavaScript 라이브러리 맵핑

### 라이브러리 사용 현황

#### 1. jQuery 기반 시스템 (레거시)

**as_center1/index.php** (구형 jQuery 기반)
```html
<SCRIPT type="text/javascript" src="jquery-1.4.2.min.js"></SCRIPT>
<script type="text/javascript" src="jquery.blockUI.js"></script>
<script type="text/javascript" src="jquery.validate.js"></script>
<script type="text/javascript" src="jquery.mousewheel-3.0.2.js"></script>
<link rel="stylesheet" type="text/css" href="jquery.autocomplete.css"/>
<script type="text/javascript" src="jquery.autocomplete.js"></script>
<!-- Gallery -->
<script type="text/javascript" src="galleryview/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="galleryview/jquery.galleryview-2.1.1.js"></script>
<script type="text/javascript" src="galleryview/jquery.timers-1.2.js"></script>
```

**사용 현황**:
- `parts.php`: 기본 jQuery (자동완성 없음)
- `products.php`: 기본 jQuery
- `members.php`: 기본 jQuery
- `orders.php`: 기본 jQuery
- `as_requests.php`: 기본 jQuery

#### 2. Fetch API 기반 시스템 (신규)

**as_repair.php**
- Vanilla JavaScript (Fetch API 사용)
- jQuery 미포함
- 자료 라이브러리 불필요

**as_repair_handler.php**
- JSON 응답 포맷

**as_request_handler.php**
- JSON 응답 포맷

**order_handler.php**
- JSON 응답 포맷

### 주요 JavaScript 사용 패턴

#### AJAX 삭제 (jQuery 기반)
```javascript
// parts.php, products.php, members.php에서 사용
$.ajax({
    type: 'POST',
    url: 'delete_handler.php',
    data: { action: 'delete', id: xxx },
    success: function(response) {
        // 성공 처리
    }
});
```

#### AJAX 삭제 (Fetch API 기반)
```javascript
// as_repair.php에서 사용
fetch('as_repair_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'save_repair_step', ... })
})
.then(r => r.json())
.then(data => { ... });
```

#### 인라인 JavaScript
```javascript
// 폼 제출
<A href='javascript:sendit()'>

// 팝업 창
<A href=\"javascript://\" onClick=\"profileWindow('print.php?number=" . $number . "')\">

// 페이지 이동
<A href='javascript:loadParts(1)'>
```

---

## 파일 간 호출 관계

### 직접 호출 관계 (include/require)

```
dashboard.php
    ├── mysql_compat.php (require_once)
    └── (메뉴로 다른 페이지 링크)

parts.php
    ├── mysql_compat.php (require_once)
    └── @config.php (암묵적, login 체크)

parts_add.php
    ├── mysql_compat.php (require_once)
    └── (성공 시) → parts.php?inserted=1

parts_edit.php
    ├── mysql_compat.php (require_once)
    └── (성공 시) → parts.php?edited=1

category_add.php / category_edit.php
    ├── mysql_compat.php (require_once)
    └── (성공 시) → parts.php?tab=tab2&...

products.php
    ├── mysql_compat.php (require_once)
    └── @config.php (암묵적, login 체크)

product_add.php / poor_add.php / result_add.php
    ├── mysql_compat.php (require_once)
    └── (성공 시) → products.php?tab=...&inserted=1

members.php
    ├── mysql_compat.php (require_once)
    └── @config.php (암묵적, login 체크)

member_add.php / member_edit.php
    ├── mysql_compat.php (require_once)
    └── (성공 시) → members.php?...

orders.php
    ├── mysql_compat.php (require_once)
    └── AJAX로 order_handler.php 호출

order_handler.php
    ├── mysql_compat.php (require_once)
    └── JSON 응답 반환

as_requests.php
    ├── mysql_compat.php (require_once)
    └── AJAX로 as_request_handler.php 호출

as_request_handler.php
    ├── mysql_compat.php (require_once)
    └── JSON 응답 반환

as_request_view.php
    ├── @config.php (require_once)
    ├── @error_function.php (require_once)
    ├── @access.php (require_once)
    └── SELECT from step13_as, step14_as_item, step18_as_cure_cart

as_repair.php
    ├── mysql_compat.php (require_once)
    └── AJAX로 as_repair_handler.php 호출

as_repair_handler.php
    ├── mysql_compat.php (require_once)
    └── JSON 응답 반환

receipt.php
    ├── mysql_compat.php (require_once)
    └── SELECT from step20_sell, step21_sell_cart
```

### 간접 호출 관계 (URL 파라미터로 제어)

```
dashboard.php → (메뉴 클릭)
├── parts.php?tab=tab1 (자재 관리)
├── products.php?tab=model (제품 관리)
├── members.php (고객 관리)
├── orders.php?tab=request (주문 관리)
├── as_requests.php?tab=request (AS 요청)
└── as_repair.php (AS 수리)

parts.php?tab=tab1 → (추가 버튼 클릭)
├── parts_add.php (새 자재 등록)
├── parts_edit.php?id=xx (자재 수정)
└── (DELETE 요청) → parts.php?deleted=1

parts.php?tab=tab2 → (추가 버튼 클릭)
├── category_add.php (새 카테고리)
├── category_edit.php?id=xx (카테고리 수정)
└── (DELETE 요청) → parts.php?deleted=1

products.php?tab=model → (추가 버튼 클릭)
├── product_add.php
├── product_edit.php?id=xx
└── (DELETE 요청) → products.php?deleted=1

as_requests.php → (요청 클릭)
└── as_request_view.php?id=xx (상세 조회)
    └── (AJAX로) as_request_handler.php (데이터 로드)

as_requests.php → (수리 시작)
└── as_repair.php?id=xx (AS 수리 처리)
    └── (AJAX로) as_repair_handler.php (데이터 저장)

orders.php → (주문 클릭)
├── order_edit.php?id=xx (주문 수정)
└── receipt.php?id=xx (영수증)
```

---

## AJAX 및 핸들러

### AJAX 통신 흐름

#### 1. 자재 삭제 (parts.php)

**요청**:
```
GET /as/parts.php?action=delete_part&id=123&tab=tab1
```

**응답**:
```
Redirect → /as/parts.php?tab=tab1&deleted=1
```

#### 2. 카테고리 삭제 (parts.php)

**요청**:
```
GET /as/parts.php?action=delete_category&id=0001&tab=tab2
```

**응답**:
```
Redirect → /as/parts.php?tab=tab2&deleted=1
```

#### 3. 고객 삭제 (members.php)

**요청**:
```javascript
$.ajax({
    type: 'POST',
    url: 'members.php',
    data: { action: 'delete', id: 123 },
    dataType: 'json'
});
```

**응답**:
```json
{
    "success": true,
    "message": "고객 정보가 정상적으로 삭제되었습니다."
}
```

#### 4. 고객 검색 (order_handler.php)

**요청**:
```javascript
$.ajax({
    type: 'POST',
    url: 'order_handler.php?action=search_member',
    data: { search_name: '업체명' },
    dataType: 'json'
});
```

**응답**:
```json
{
    "success": true,
    "members": [
        {
            "s11_meid": 1,
            "s11_com_name": "업체명",
            "s11_phone1": "032",
            "s11_phone2": "624",
            "s11_phone3": "1980"
        }
    ]
}
```

#### 5. AS 요청 데이터 로드 (as_request_handler.php)

**요청**:
```javascript
$.ajax({
    type: 'POST',
    url: 'as_request_handler.php?action=get_as_request_data',
    data: { as_id: 123 },
    dataType: 'json'
});
```

**응답**:
```json
{
    "success": true,
    "as_info": {
        "s13_asid": 123,
        "s13_meid": 456,
        "s13_as_in_how": "배송"
    },
    "member_info": {
        "s11_meid": 456,
        "s11_com_name": "업체명",
        "s11_phone1": "032"
    },
    "products": [
        {
            "model_id": 1,
            "poor_id": 2,
            "model_name": "Model A",
            "poor_name": "전원 안됨"
        }
    ]
}
```

#### 6. 부품 로드 및 수리 저장 (as_repair_handler.php)

**요청**:
```javascript
fetch('as_repair_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'save_repair_step',
        itemid: 123,
        as_end_result: '교체',
        parts_data: [
            { part_id: 1, part_name: '부품1', cost: 50000, quantity: 1 }
        ]
    })
});
```

**응답**:
```json
{
    "success": true,
    "message": "저장 완료"
}
```

---

## 보안 고려사항

### 현재 보안 이슈

#### 1. SQL Injection 취약점

**위험 패턴**:
```php
// parts.php, products.php 등에서 발견
$where .= " AND s11_com_name LIKE '%" . mysql_real_escape_string($search) . "%'";
// mysql_real_escape_string 사용하므로 부분적으로 완화
```

**개선 필요**:
- 매개변수화된 쿼리 (Prepared Statements) 사용으로 전환 필요
- mysqli 또는 PDO로 마이그레이션 필요

#### 2. XSS (Cross-Site Scripting) 취약점

**위험 패턴**:
```php
echo $row['s1_name']; // HTML 이스케이프 미사용
```

**개선 필요**:
- `htmlspecialchars()` 사용: `echo htmlspecialchars($row['s1_name']);`
- 또는 템플릿 엔진 도입

#### 3. 인증 및 권한

**현재 상태**:
- 세션 기반 인증 (session_start() + $_SESSION 확인)
- @access.php로 로그인 확인

**개선 필요**:
- CSRF 토큰 추가
- 권한 확인 강화 (member_level 기반)

#### 4. 파일 업로드

**파일 확인 필요**: `/_filez/` 업로드 디렉토리의 보안 설정

#### 5. 에러 처리

**현재 패턴**:
```php
$result = @mysql_query($query); // @ 연산자로 에러 억제
```

**개선 필요**:
- 프로덕션 환경에서 에러 로깅으로 전환
- 사용자에게는 일반 메시지만 표시

---

## 마이그레이션 체크리스트

### 단계 1: 기본 구조 파악 (완료)
- [x] 의존성 맵핑 완료
- [x] 테이블 관계 파악 완료
- [x] JavaScript 라이브러리 식별 완료

### 단계 2: 문자 인코딩 (완료)
- [x] UTF-8 마이그레이션 완료 (utf8mb4_unicode_ci)
- [x] mysql_compat.php 업데이트
- [x] 모든 PHP 파일에 header() UTF-8 설정

### 단계 3: 데이터베이스 마이그레이션 (계획 중)
- [ ] 매개변수화된 쿼리로 전환 (mysqli 또는 PDO)
- [ ] 모든 mysql_* 함수 교체
- [ ] SQL 인젝션 취약점 제거

### 단계 4: XSS 방지 (계획 중)
- [ ] 모든 echo/출력 htmlspecialchars() 적용
- [ ] 템플릿 엔진 도입 검토
- [ ] Content-Security-Policy 헤더 추가

### 단계 5: CSRF 방지 (계획 중)
- [ ] CSRF 토큰 생성 및 검증
- [ ] 모든 POST 요청에 토큰 추가

### 단계 6: 테스트 (계획 중)
- [ ] 단위 테스트 작성
- [ ] 통합 테스트
- [ ] 보안 테스트

---

## 개발 가이드

### 새로운 기능 추가 시 의존성 체크리스트

1. **포함 파일 확인**
   - [ ] `mysql_compat.php` require_once 추가?
   - [ ] `@config.php` 필요?
   - [ ] 권한 확인 필요?

2. **데이터베이스**
   - [ ] 어떤 테이블 사용?
   - [ ] 외래키 관계 확인?
   - [ ] 트랜잭션 필요?

3. **JavaScript**
   - [ ] jQuery vs Fetch API 선택?
   - [ ] AJAX 필요?
   - [ ] 라이브러리 버전 호환성?

4. **보안**
   - [ ] 입력 검증?
   - [ ] SQL 인젝션 방지?
   - [ ] XSS 방지?
   - [ ] CSRF 방지?

5. **세션**
   - [ ] 로그인 확인?
   - [ ] 권한 확인?
   - [ ] 세션 정보 저장?

---

## 추가 참고사항

### 환경 설정

**Docker 기반**:
- MySQL 서비스명: `mysql`
- Database: `mic4u`
- User: `mic4u_user`
- Password: `change_me` (프로덕션에서 변경 필수)

**문자셋**:
- 데이터베이스: utf8mb4
- Collation: utf8mb4_unicode_ci
- PHP 파일: UTF-8 (EUC-KR에서 마이그레이션 완료)

### 성능 고려사항

1. **페이징**: 기본 10개씩 (대부분의 페이지)
2. **캐싱**: 현재 미사용 (대규모 데이터에서 필요 시 추가)
3. **인덱스**: 데이터베이스 스키마 확인 필요
4. **쿼리 최적화**: N+1 문제 확인 필요

### 향후 개선 방향

1. **모던 PHP 프레임워크로 마이그레이션**
   - Laravel, Symfony 등 고려
   - 현재 레거시 코드 유지보수 비용 증가

2. **API 분리**
   - RESTful API 개발
   - 프론트엔드와 백엔드 분리

3. **테스트 자동화**
   - PHPUnit, Codeception 도입
   - CI/CD 파이프라인 구축

4. **문서화**
   - 현재 분석 보고서 유지보수
   - API 문서 작성
   - 데이터베이스 다이어그램 생성

---

**분석 완료 일시**: 2025-11-10  
**분석자**: Claude Code (AI)  
**최종 검토**: 필수 (human review required)

