# AS 센터 관리 시스템 - Claude Code 프로젝트 지침

## 🚨 긴급 작업 (2025-11-12 진행 중)

### 호스팅 서버 포팅 작업

**SSH 접속 정보**:
- 도메인: dcom.co.kr
- 포트: 22
- 사용자명: dcom2000
- 비밀번호: Noblein12!!

**작업 목표**: 로컬 개발 프로젝트를 호스팅 서버로 포팅

**작업 순서**:
1. SSH 서버 접속 및 기본 정보 확인 (OS, 디렉토리 구조)
2. 서버 환경 파악 (PHP 버전, MySQL 버전, 웹서버 종류)
3. 프로젝트 디렉토리 구조 확인 및 백업
4. 데이터베이스 설정 확인 (DB명, 사용자, 권한)
5. 프로젝트 파일 업로드 및 설정 (DB 연결, 경로 등)
6. 테스트 및 검증

**MCP 도구 사용**:
- SSH MCP 서버가 설치됨 (@fangjunjie/ssh-mcp-server by classfang)
- 비밀번호 인증 지원 (name=dcom 연결로 설정됨)
- 새 세션에서 SSH MCP 도구가 자동 로드됨
- 사용 가능한 도구:
  - `execute-command`: SSH 명령 실행 (cmdString, connectionName="dcom")
  - `upload`: 로컬 파일을 원격 서버로 업로드
  - `download`: 원격 서버 파일을 로컬로 다운로드
  - `list-servers`: 사용 가능한 SSH 서버 목록 조회

---

## 📌 세션 시작 시 필독 사항

**이 파일은 세션이 열릴 때마다 자동으로 로드됩니다.**

### 주요 프로젝트 정보

- **프로젝트명**: 디지탈컴 AS (After-Sales) 센터 관리 시스템
- **기술 스택**: PHP 5.x, MySQL, jQuery, 레거시 코드베이스
- **문자 인코딩**: UTF-8
- **저장소**: https://github.com/HANSOLJJ/AS_center_page.git (main branch)

---

## 📅 최근 작업 현황

### 2025-11-17 작업 (오늘 완료)

#### 1. VSCode 원격 편집 환경 구축 (✅ 완료)

**목표**: dcom.co.kr 서버의 AS 파일들을 로컬 VSCode에서 편집

**시도 및 결과**:

1. **VSCode Remote-SSH 시도** (❌ 실패)
   - SSH config 파일 생성: `C:\Users\noble\.ssh\config`
   - Host 설정: dcom (dcom.co.kr:22, user: dcom2000)
   - 실패 원인: 서버의 glibc/libstdc++ 버전이 VSCode Server 요구사항 미충족
   - 서버 환경: Cafe24 공유 호스팅, PHP 7.0.0 (2016년 빌드), 오래된 Linux

2. **SFTP 설정으로 전환** (✅ 성공)
   - `.vscode/sftp.json` 생성
   - 설정:
     - uploadOnSave: true (저장 시 자동 업로드)
     - downloadOnOpen: false (수동 다운로드만)
     - syncMode: "update" (기존 파일만 업데이트)
   - ignore 설정:
     - .git, DOCS, .claude, *.md (문서 파일)
     - as/db_config.local.php (로컬 전용 DB 설정)
     - .vscode (설정 파일)
   - 원격 경로: `/home/hosting_users/dcom2000/www`

3. **DB 설정 파일 분리**
   - `as/db_config.php` → `as/db_config.local.php` (로컬 전용)
   - 서버의 `db_config.php` 다운로드 (프로덕션 설정: localhost, dcom2000, Basserd2@@)
   - SFTP에서 `db_config.local.php` ignore 처리

**사용 방법**:
- VSCode SFTP 확장 설치 (SFTP by Natizyskunk)
- 파일 편집 후 Ctrl+S → 자동으로 서버에 업로드
- 새 파일: 우클릭 → "Upload" (수동 업로드)

**커밋**: 예정
**상태**: ✅ 완료

---

### 2025-11-13 작업 (이전 완료)

#### 1. 데이터베이스 설정 중앙화 (✅ 완료)

**목표**: 31개 PHP 파일의 하드코딩된 DB 연결 정보를 단일 db_config.php로 통합

**작업 내용**:

1. **db_config.php 생성**
   - MySQL 호환성 레이어 로드 (mysql_compat.php)
   - 데이터베이스 연결 정보 정의 (DB_HOST, DB_USER, DB_PASS, DB_NAME)
   - 자동 연결 및 DB 선택
   - 전역 변수로 연결 저장 ($GLOBALS['db_connect'])

2. **31개 PHP 파일 수정**
   - 기존 개별 DB 연결 코드 제거
   - `require_once '../db_config.php';` 또는 `require_once 'db_config.php';`로 변경
   - 수정된 파일:
     - dashboard.php, login_process.php
     - orders/*.php (orders.php, order_handler.php, order_payment.php, order_receipt.php)
     - parts/*.php (parts.php, parts_add.php, parts_edit.php, category_add.php, category_edit.php)
     - products/*.php (products.php, model_add.php, model_edit.php, poor_add.php, poor_edit.php, result_add.php, result_edit.php)
     - customers/*.php (members.php, member_add.php, member_edit.php)
     - stat/*.php (statistics.php, export_statistics.php)
     - as_task/*.php (as_requests.php, as_request_handler.php, as_request_view.php, as_receipt.php, as_repair.php, as_repair_handler.php, close_as.php, cancel_as.php)

3. **설정 분리**
   - **로컬 (테스트 서버)**: mysql, mic4u_user, change_me, mic4u
   - **프로덕션 (dcom.co.kr)**: localhost, dcom2000, Basserd2@@, dcom2000

**커밋**: 예정
**상태**: ✅ 완료

#### 2. 서버 배포 (dcom.co.kr) (✅ 완료)

**목표**: 로컬 프로젝트를 Cafe24 호스팅 서버로 포팅

**작업 내용**:

1. **파일 업로드**
   - as/ 디렉토리 압축 (as.tar.gz, 125KB) 및 업로드
   - vendor/ 디렉토리 압축 (vendor.tar.gz, 1.3MB) 및 업로드
   - 서버에서 압축 해제 및 권한 설정 (755)

2. **데이터베이스 마이그레이션**
   - 로컬 MySQL에서 AS 시스템 관련 13개 테이블 덤프
   - schema_as_only.sql (구조), data_as_only.sql (데이터, 38MB → 4.7MB 압축)
   - 서버 MariaDB에 임포트 완료
   - 데이터: 874명 고객, 32,936건 AS 요청, 405개 자재

3. **누락 테이블 추가**
   - step2_center 테이블 발견 및 추가
   - 4개 센터: 을지로, 영등포, 본사, 용산
   - as_receipt.php, order_receipt.php에서 센터명 조회 시 필요

4. **수정 파일 서버 업로드**
   - statistics.php (날짜 범위 버튼 수정본)
   - 기타 db_config.php 사용 파일들 (초기 배포에 포함됨)

**서버 환경**:
- 호스트: dcom.co.kr
- Web PHP: 7.4.5p1
- CLI PHP: 7.0.0
- DB: MariaDB 10.1.13
- 접속 URL: https://dcom.co.kr/as/

**커밋**: 예정
**상태**: ✅ 완료

---

### 2025-11-08 작업 (이전 완료)

#### 1. as_requests.php, orders.php, as_statistics.php "전체 기간" 버튼 구현 (✅ 완료)

**목표**: 날짜 검색 필터에 "전체 기간" 버튼을 추가하여 기간 제한 없이 전체 데이터 조회 가능

**작업 내용**:

1. **as_requests.php**
   - 기본값 변경: `$range = ''` (전체 기간)
   - "전체 기간" 버튼을 첫 번째 옵션으로 추가
   - setSearchDateRange() 함수에 'all' 케이스 추가 (startDate, endDate = '')
   - 페이지네이션에 range 파라미터 추가

2. **orders.php**
   - 기본값 변경: `$range = ''` (전체 기간)
   - 요청 탭(Tab1), 완료 탭(Tab2) 모두에 "전체 기간" 버튼 추가
   - setOrderDateRange() 함수에 'all' 케이스 추가 (startDate, endDate = '')
   - 두 탭의 페이지네이션 모두에 range 파라미터 추가

3. **as_statistics.php**
   - 기본값 변경: `$range = 'month'` (금월 - 사용자 요구)
   - "전체 기간" 버튼을 첫 번째 옵션으로 추가
   - setDateRange() 함수에 'all' 케이스 추가 (startDate, endDate = '')
   - **SQL 쿼리 4개 함수 업데이트** (빈 날짜 값 처리):
     - `getStatistics()`: WHERE 절 조건부 처리
     - `getTopRepairProducts()`: WHERE 절 조건부 처리
     - `getTopRepairParts()`: WHERE 절 조건부 처리
     - `getTopSaleParts()`: WHERE 절 조건부 처리

**버튼 순서**: 전체 기간 → 오늘 → 금주 → 금월 → 금년

**기본값 정책**:
- `as_requests.php`: 전체 기간 (사용성 향상 - 모든 데이터 조회 기본)
- `orders.php`: 전체 기간 (사용성 향상 - 모든 데이터 조회 기본)
- `as_statistics.php`: 금월 (통계 조회 시 일반적인 습관)

**수정 파일**:
- `as/as_requests.php` (라인 31, 860-864, 884-918, 1189-1191)
- `as/orders.php` (라인 36, 760-764, 1009-1013, 955-956, 1164-1165, setOrderDateRange 함수)
- `as/as_statistics.php` (라인 25-26, 44-47, 57-89, 137-150, 163-178, 191-205, 561-562, 627-634)

**커밋**: 예정 (아직 커밋하지 않음)

**상태**: ✅ 완료

---

### 2025-11-07 작업 (이전 완료)

#### 1. as_repair.php 제품명 선택 기능 구현 (✅ 완료)

**목표**: AS 수리 등록 시 step15_as_model에서 제품을 선택하여 s14_model과 cost_name을 함께 업데이트

**작업 내용**:
- step15_as_model 제품 목록 조회 및 select dropdown 추가
- 기존 제품 자동 선택 기능
- 선택된 제품 ID로부터 모델명 조회 후 업데이트

**수정 파일**:
- `as_repair.php` (라인 61-68, 478-486): 제품 목록 조회 및 dropdown UI 추가
- `as_repair_handler.php` (라인 99-113): 제품 ID로부터 모델명 조회 및 s14_model, cost_name 업데이트

**상태**: ✅ 완료

#### 2. as_repair_handler.php s18_accid 기존값 유지 (✅ 완료)

**문제**: 자재 수정 시 DELETE 후 INSERT하면 s18_accid가 새로 생성되어 기존 ID를 잃음

**해결책**:
- 기존 자재는 UPDATE로 처리 (s18_accid 유지)
- 새로운 자재만 INSERT
- 삭제된 자재만 DELETE
- part_id별로 매핑하여 중복 없이 처리

**수정 파일**:
- `as_repair_handler.php` (라인 56-125): DELETE/INSERT 전략 → UPDATE/INSERT 조합 전략으로 변경

**상태**: ✅ 완료

#### 3. as_repair_handler.php s14_cart 자재 개수 업데이트 (✅ 완료)

**목표**: 수리 자재 등록 시 자재 종류의 개수를 s14_cart에 저장

**작업 내용**:
- 모든 자재 저장 완료 후 step18_as_cure_cart의 자재 개수를 COUNT
- s14_cart 필드 업데이트
- 제품 ID 미선택 시에도 s14_cart 업데이트

**수정 파일**:
- `as_repair_handler.php` (라인 144-151, 155, 158): s14_cart 계산 및 UPDATE에 추가

**상태**: ✅ 완료

#### 4. step14_as_item s14_cart 일괄 업데이트 (✅ 완료)

**목표**: 기존 데이터의 s14_cart 필드에 자재 개수 채우기

**작업 내용**:
- s14_aiid >= 84767부터 step18_as_cure_cart 조인으로 자재 개수 계산
- 총 9개 레코드 중 3개가 자재 보유

**실행 명령**:
```sql
UPDATE step14_as_item a
SET a.s14_cart = (SELECT COUNT(*) FROM step18_as_cure_cart WHERE s18_aiid = a.s14_aiid)
WHERE a.s14_aiid >= 84767;
```

**상태**: ✅ 완료 (9개 레코드 업데이트, 3개가 자재 보유)

#### 5. receipt.php 날짜 처리 개선 (✅ 완료)

**문제**: 1970년 1월 1일로 표시되는 날짜들이 많음 (옛날 데이터일수록 심함)

**원인**:
- `strtotime()` 실패 시 false 반환 → `date(false)` = 1970-01-01
- `is_numeric("0")` = true → `date(0)` = 1970-01-01
- 레거시 데이터의 datetime 형식 불일치

**해결책**:
- 유닉스 타임스탐프 유효성 검사 (86400 이상, 1970-02-01 이후)
- `strtotime()` 실패 시 원본값 표시
- 필드 매핑 수정:
  - "일자" = s20_sell_in_date (접수일)
  - "입금일자" = s20_bank_check (입금일)
  - "A/S 처리완료일" = s20_as_out_date (처리완료일)

**수정 파일**:
- `receipt.php` (라인 41-76, 470): 날짜 포맷팅 함수 추가 및 필드 매핑 수정

**상태**: ✅ 완료

#### 6. as_request_view.php 날짜 처리 개선 (✅ 완료)

**문제**: receipt.php와 동일한 1970년 문제

**해결책**:
- `format_date()` 함수 추가 (라인 68-87)
- receipt.php와 동일한 날짜 validation 로직 적용
- s13_as_in_date, s13_bank_check, s13_as_out_date 세 필드 모두 처리

**수정 파일**:
- `as_request_view.php` (라인 68-91): format_date() 함수 추가 및 적용

**상태**: ✅ 완료

---

### 2025-11-05 작업 (이전 완료)

#### 1. as_request_handler.php Step 3 구현 (✅ 완료)

**목표**: Step 3에서 제품 및 불량증상을 선택하고 여러 제품을 카트에 추가

**작업 내용**:

1. **Step 3 UI 추가** (v0.5.0)
   - 제품 선택 드롭다운 (step15_as_model에서 데이터 로드)
   - 불량증상 선택 드롭다운 (step16_as_poor에서 데이터 로드)
   - 백엔드: `load_step3_data` AJAX 액션 추가
   - 동작: 업체명 선택 시 Step 3 자동 표시 및 데이터 로드

2. **카트 기능 추가** (v0.5.1)
   - `selectedProducts[]` 배열로 제품 관리
   - **제품 추가** 버튼 구현
   - 추가된 제품 목록 테이블 표시 (제품명, 불량증상, 삭제 버튼)
   - 유효성 검사: 제품 및 불량증상 미선택 시 에러 메시지
   - 함수:
     - `addProductToCart()` - 제품 추가
     - `updateSelectedProductsList()` - 목록 업데이트
     - `removeFromSelectedProducts(index)` - 제품 삭제

3. **제품 정렬 순서 변경**
   - `step15_as_model` 조회 시 `ORDER BY s15_amid DESC` 적용
   - 최신 제품이 먼저 표시됨

**커밋**:
- `4a52fcc` - "feat: Add Step 3 (product and defect symptom selection)" (v0.5.0)
- `2a657a5` - "feat: Add shopping cart functionality to Step 3 and sort products by s15_amid DESC" (v0.5.1)

**상태**: ✅ 완료 (여러 제품 추가 가능, 제품/불량증상 선택 및 삭제 기능 구현)

**주요 변경사항**:
- `as/as_request_handler.php`: 121줄 + 95줄 추가 (총 216줄 추가)
- 업체명 선택 → Step 2 표시 → Step 3 표시 (데이터 로드) → 제품 추가 가능한 흐름 완성

---

### 2025-11-04 작업 (이전 완료)

#### 1. parts_edit.php 수정 (✅ 완료)

**이슈**: parts.php Tab1에서 수정 버튼 클릭 후 parts_edit.php 로드 시 가격 입력 폼과 수정/취소 버튼이 보이지 않음

- **원인**: CSS 레이아웃 문제로 폼 요소들이 숨겨짐
- **해결방법**: CSS 전체 재작성
  - 모든 요소에 명시적 `width: 100%` 설정
  - Container 너비 명확화
  - 반응형 디자인 개선
- **커밋**: `9463bb1` - "fix: Ensure all price fields and buttons visible in parts_edit.php"
- **상태**: ✅ 완료 (모든 8개 가격 필드 + 버튼 표시됨)

#### 2. poor_edit.php, poor_add.php 개선 (✅ 완료)

**이슈**: nav-bar와 header에서 사용자명 표시 안 됨

- **해결방법**: orders.php 스타일과 동일하게 개선
  - 완전한 header 구조 추가
  - Navigation bar (nav-bar) 추가
  - 사용자명 표시 (님 형식)
  - 일관된 CSS 스타일 적용
- **커밋**: `f20dea1` - "fix: Add nav-bar to poor_edit, poor_add, result_edit, result_add"
- **상태**: ✅ 완료

#### 3. result_edit.php, result_add.php 개선 (✅ 완료)

**이슈**: nav-bar와 header에서 사용자명 표시 안 됨

- **해결방법**: orders.php 스타일과 동일하게 개선
- **커밋**: `f20dea1` (위와 동일 커밋)
- **상태**: ✅ 완료

---

### 2025-11-03 작업 (이전 완료)

**데이터베이스 마이그레이션 & orders.php 최적화:**

- ✅ UTF-8 (utf8mb4) 인코딩 마이그레이션
- ✅ orders.php 성능 최적화 및 UI 개선
- ✅ 마이그레이션 문서화
- ✅ Git 브랜치 정규화 (master → main)

---

## 🔧 현재 Git 상태

```
원격 저장소: https://github.com/HANSOLJJ/AS_center_page.git
Branch: main (master는 삭제됨)
최신 태그: v0.5.1-20251105 (곧 v0.6.0-20251113 예정)
최신 커밋: 2a657a5 - feat: Add shopping cart functionality to Step 3 and sort products by s15_amid DESC
커밋 예정: db_config.php 중앙화 및 서버 배포 완료
```

### 최근 작업 파일 (커밋 예정)

```
- as/db_config.php (새로 생성)
- as/statistics.php (날짜 버튼 수정)
- 31개 PHP 파일 (db_config.php 사용)
```

### 최근 커밋 히스토리

```
2a657a5 - feat: Add shopping cart functionality to Step 3 and sort products by s15_amid DESC (v0.5.1)
4a52fcc - feat: Add Step 3 (product and defect symptom selection) to as_request_handler.php (v0.5.0)
5f3e5a2 - fix: Keep cancel button always visible in order_handler.php
89956c6 - feat: Hide submit button until company and parts are selected in order_handler.php
f46b25c - refactor: Apply conditional step display to order_handler.php
5f47b9e - refactor: Update order_handler.php to use step-by-step progressive disclosure pattern
26c956b - feat: Add Step 2 (delivery method selection) to as_request_handler.php
```

---

## 📋 다음 작업 예정 (2025-11-14)

### 1️⃣ 최신 데이터베이스 마이그레이션 (우선순위: 긴급)

**목표**: 옛날 호스팅 서버에 있는 최신 DB를 로컬 및 dcom.co.kr로 이전

**작업 순서**:

1. **옛날 호스팅 서버에서 최신 DB 덤프**
   - AS 시스템 관련 13개 테이블 전체 덤프
   - 고객 데이터, AS 요청 데이터, 자재 데이터 등 포함

2. **로컬 테스트 서버로 임포트**
   - 현재 로컬 DB의 AS 관련 데이터 삭제 (TRUNCATE 또는 DROP)
   - 최신 DB 데이터 임포트
   - 동작 테스트 (로그인, AS 조회, 통계 등)

3. **이상 없을 시 dcom.co.kr로 이전**
   - 현재 dcom.co.kr DB의 AS 관련 데이터 삭제
   - 최신 DB 데이터 임포트
   - 브라우저에서 동작 확인

**주의사항**:
- 현재 로컬 및 dcom.co.kr의 프로젝트 관련 DB 데이터는 삭제 필요
- 백업 필수 (만약을 대비)
- 13개 테이블 전체 확인:
  - 2010_admin_member
  - step1_parts
  - step2_center
  - step5_category
  - step11_member
  - step13_as
  - step14_as_item
  - step15_as_model
  - step16_as_poor
  - step18_as_cure_cart
  - step19_as_result
  - step20_sell
  - step21_sell_cart

**예상 파일**:
- old_server_dump.sql (옛날 서버 덤프)
- 임포트 스크립트

---

### 2️⃣ as_statistics.php 그래프 기능 추가 (우선순위: 높음)

**목표**: AS 분석 및 판매 분석 탭에서 고객별, 제품별, 자재별 통계를 그래프로 시각화

**작업 내용**:

1. **AS 분석 탭 (as_analysis)**
   - 제품별 AS 건수 (막대 그래프)
   - 불량증상별 AS 건수 (파이 차트)
   - 고객별 AS 건수 TOP 10 (막대 그래프)
   - 기간별 AS 추이 (선 그래프)

2. **판매 분석 탭 (sales_analysis)**
   - 제품별 판매액 (막대 그래프)
   - 자재별 판매 수량 (파이 차트)
   - 고객별 판매액 TOP 10 (막대 그래프)
   - 기간별 판매 추이 (선 그래프)

3. **기술 스택**:
   - Chart.js 또는 Google Charts 라이브러리 사용
   - 기간 필터 (전체 기간/오늘/금주/금월/금년)와 연동
   - AJAX로 동적 데이터 로드
   - 반응형 디자인

**예상 파일**:
- `as/as_statistics.php` (그래프 추가)
- `as/get_graph_data.php` (AJAX 데이터 제공)

---

### 2️⃣ as_statistics.php 월간 리포트 XLSX 내보내기 (우선순위: 높음)

**목표**: 현재 선택된 기간의 통계 데이터를 Excel 파일로 내보내기

**작업 내용**:

1. **내보내기 버튼**
   - as_statistics.php에 "Excel 다운로드" 버튼 추가
   - 현재 선택된 기간의 모든 통계 데이터 포함

2. **Excel 파일 구성** (Sheet 별):
   - Sheet1: 요약 통계 (AS 완료, 판매 완료, 매출 등)
   - Sheet2: 월별 AS 통계 (월, 완료건수, 매출)
   - Sheet3: 월별 판매 통계 (월, 완료건수, 매출)
   - Sheet4: TOP3 수리 제품 (제품명, 건수)
   - Sheet5: TOP3 수리 자재 (자재명, 수량)
   - Sheet6: TOP3 판매 자재 (자재명, 수량)
   - Sheet7: 상세 AS 목록 (고객명, 제품, 불량증상, 상태, 금액)
   - Sheet8: 상세 판매 목록 (고객명, 자재, 수량, 금액)

3. **기술 스택**:
   - PHPExcel 또는 OpenSpout 라이브러리 사용
   - 한글 인코딩 UTF-8 유지
   - 통화 형식 (￥) 적용
   - 셀 병합, 스타일 지정

4. **파일명**: `AS_통계_YYYY-MM-DD_HH-MM-SS.xlsx`

**예상 파일**:
- `as/as_statistics.php` (내보내기 버튼 추가)
- `as/export_statistics.php` (Excel 생성 및 다운로드)

**예상 라이브러리**:
- PHPExcel (또는 더 가벼운 OpenSpout)
- composer로 설치 필요

---

## 📝 작업 시 체크리스트

1. **파일 수정 전**

   - 현재 파일 상태 확인 (git status)
   - 기존 스타일/구조 검토
   - 다른 페이지와의 일관성 확인

2. **파일 수정 후**

   - 모든 변경사항 git add
   - 명확한 커밋 메시지 작성 (한글 O, 상세 설명 포함)
   - 필요시 v0.X.X-YYYYMMDD 형식 태그 생성
   - 원격 저장소에 push

3. **HTML/CSS/JS 작업**

   - parts.php, orders.php, members.php 등의 스타일 참고
   - nav-bar와 header는 orders.php 형식 통일
   - 반응형 디자인 고려
   - 버튼 가시성 확인 (width, display, z-index 등)

4. **PHP 작업**
   - SQL은 `mysql_real_escape_string()` 사용
   - UTF-8 헤더 명시 (`header('Content-Type: text/html; charset=utf-8');`)
   - 세션 확인 (`$_SESSION['member_id']` 등)
   - 에러 처리 및 메시지 표시

---

## 🎯 알려진 이슈 및 해결 내역

### ✅ 해결됨

- [2025-11-13] 데이터베이스 설정 중앙화 완료 (db_config.php로 31개 파일 통합)
- [2025-11-13] dcom.co.kr 서버 배포 완료 (as/, vendor/ 디렉토리 + DB 마이그레이션)
- [2025-11-13] step2_center 테이블 누락 발견 및 추가 (센터명 조회 기능)
- [2025-11-08] as_requests.php, orders.php, as_statistics.php "전체 기간" 버튼 구현 (기간 제한 없이 전체 데이터 조회 가능)
- [2025-11-08] as_statistics.php SQL 쿼리 4개 함수 업데이트 (빈 날짜 값 처리 추가)
- [2025-11-07] receipt.php & as_request_view.php 1970년 날짜 문제 해결 (datetime validation 추가)
- [2025-11-07] as_repair.php 제품 선택 기능 구현 (step15_as_model 통합)
- [2025-11-07] as_repair_handler.php s18_accid 기존값 유지 (UPDATE/INSERT 전략)
- [2025-11-07] s14_cart 자재 개수 자동 업데이트 (저장 시 자동 계산)
- [2025-11-05] as_request_handler.php Step 3 구현 완료 (제품/불량증상 선택 + 카트 기능)
- [2025-11-05] order_handler.php 조건부 Step 표시 및 제출 버튼 가시성 완료
- [2025-11-05] 제품 목록 정렬 DESC 순서 적용 (최신 제품 우선)
- [2025-11-04] parts_edit.php 가격 폼/버튼 미표시 → CSS 재작성으로 해결
- [2025-11-04] poor/result edit/add 페이지 nav-bar 미표시 → orders.php 스타일 적용으로 해결
- [2025-11-03] 데이터베이스 EUC-KR → UTF-8 마이그레이션 완료

### ⚠️ 진행 중

없음

### 📌 향후 예정

- 옛날 호스팅 서버 최신 DB 마이그레이션 (내일 우선순위 1 - 긴급)
- as_statistics.php 그래프 기능 (우선순위 2)
- as_statistics.php 월간 리포트 XLSX 내보내기 (우선순위 3)
- as_repair.php 추가 기능 개선 (필요시)
- as_center/ 페이지들과의 연동 (추후)
- parts.php Tab3-5 기능 구현 (추후)
- dashboard.php 고도화 (추후)
- 전체 페이지 일관된 UI/UX 적용 (추후)

---

## 📚 참고 파일

| 파일명                        | 용도                           | 최근 수정일 |
| ----------------------------- | ------------------------------ | ----------- |
| `.claude/instructions.md`     | 프로젝트 지침 (현재 파일)      | 2025-11-13  |
| `CLAUDE.md`                   | 전체 프로젝트 문서             | 2025-11-04  |
| `as/db_config.php`            | DB 설정 중앙화 (신규 생성)     | 2025-11-13  |
| `as/as_statistics.php`        | 통계/분석 (기간 필터 완료)     | 2025-11-13  |
| `as/as_requests.php`          | AS 요청 관리 (기간 필터 완료)  | 2025-11-08  |
| `as/orders.php`               | 자재 판매 관리 (기간 필터 완료)| 2025-11-08  |
| `as/as_request_handler.php`   | AS 요청 신청 (Step 3 완료)     | 2025-11-05  |
| `as/order_handler.php`        | 자재 판매 신청 (참고용)        | 2025-11-05  |
| `as/as_request_view.php`      | AS 상세 조회 (날짜 처리 개선)  | 2025-11-07  |
| `as/as_repair.php`            | AS 수리 처리 (제품 선택 완료)  | 2025-11-07  |
| `as/as_repair_handler.php`    | AS 수리 저장 (자재 관리 완료)  | 2025-11-07  |
| `as/parts.php`                | 자재 관리 (참고용)             | 2025-11-04  |
| `as/members.php`              | 고객 관리 (참고용)             | 2025-11-04  |
| `as/products.php`             | 제품 관리 (참고용)             | 2025-11-04  |

---

## 💾 데이터베이스 정보

**마이그레이션 상태**: ✅ UTF-8 (utf8mb4) 완료

- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci
- MySQL 연결: mysql_compat.php에서 설정

**로컬 환경 (테스트 서버)**:
- Host: mysql (Docker 컨테이너)
- User: mic4u_user
- Password: change_me
- Database: mic4u

**프로덕션 환경 (dcom.co.kr)**:
- Host: localhost
- User: dcom2000
- Password: Basserd2@@
- Database: dcom2000
- DB Server: MariaDB 10.1.13

**AS 시스템 테이블 (13개)**:

- 2010_admin_member (관리자)
- step1_parts (자재)
- step2_center (센터)
- step5_category (카테고리)
- step11_member (회원)
- step13_as (AS 요청)
- step14_as_item (AS 항목)
- step15_as_model (모델)
- step16_as_poor (불량증상)
- step18_as_cure_cart (수리 자재)
- step19_as_result (AS 결과)
- step20_sell (자재 판매)
- step21_sell_cart (판매 항목)

---

---

## 🔒 주의 사항

### 1. 데이터베이스 설정 관리 (2025-11-13)
- **db_config.php**: 모든 DB 연결은 이 파일을 통해 관리
  - 로컬과 프로덕션 설정이 다름 (주석 참고)
  - 새 PHP 파일 생성 시 `require_once 'db_config.php';` 또는 `require_once '../db_config.php';` 필수
  - 직접 DB 연결 코드 작성 금지
- **환경 분리**:
  - 로컬: mysql, mic4u_user, change_me, mic4u
  - 프로덕션: localhost, dcom2000, Basserd2@@, dcom2000
- **서버 배포 시**: db_config.php만 프로덕션 설정으로 변경하면 됨

### 2. 날짜 필드 처리 (2025-11-07)
- **receipt.php**: 모든 날짜 필드에서 `format_date()` 사용하지 않음 (인라인 로직)
  - 향후 리팩토링 시 함수로 통일할 것
- **as_request_view.php**: `format_date()` 함수로 통일
  - 다른 페이지에도 동일한 패턴 권장
- **datetime validation 규칙**:
  - 유닉스 타임스탐프: `intval($value) > 86400` (1970-02-01 이후)
  - 문자열 datetime: `strtotime()` 실패 시 원본값 표시
  - 빈값(NULL/0): 빈 문자열 반환

### 3. s18_accid 관리 (2025-11-07)
- DELETE 후 INSERT하면 s18_accid가 새로 생성됨
- 기존 자재 수정 시 UPDATE/INSERT 조합 전략 필수
  - `part_id => s18_accid` 매핑으로 관리
  - 들어온 데이터와 기존 데이터 비교하여 UPDATE/INSERT/DELETE 구분

### 4. s14_cart 업데이트 (2025-11-07)
- 자재 저장 완료 후 COUNT로 자재 종류의 개수 계산
- 모든 경우에 s14_cart 업데이트 (제품 ID 미선택 시에도)
- 레거시 데이터: 일괄 업데이트 필요 (이미 완료: s14_aiid >= 84767)

### 5. Step 3 select 필드 처리 (2025-11-05)
- 제품 선택(product_name) = s15_amid (제품 ID, 숫자)
- 수리 방법 선택(as_end_result) = s19_result (문자열)
- JavaScript에서 querySelector 선택자 변경 필수 (input vs select)

---

**마지막 업데이트**: 2025-11-13 (오늘)
**최신 작업**: 데이터베이스 설정 중앙화 및 dcom.co.kr 서버 배포 완료
