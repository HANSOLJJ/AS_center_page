# AS Center Management System

디지탈컴 AS 센터 관리 시스템

## 프로젝트 정보

- **Repository**: https://github.com/HANSOLJJ/AS_center_page.git
- **Branch**: main
- **최신 버전**: v0.6.5-20251117

## 서버 정보

| 구분 | 정보 |
|------|------|
| **운영 서버** | dcom.co.kr |
| **DB Host** | localhost |
| **DB Name** | dcom2000 |
| **Character Set** | UTF-8 (utf8mb4_unicode_ci) |
| **PHP Version** | 5.x (mysql_* 호환 레이어 사용) |

## 기술 스택

- **Backend**: PHP 5.x + MySQL
- **Frontend**: HTML5, CSS3, Vanilla JavaScript, jQuery
- **Library**: PhpSpreadsheet (엑셀 export)
- **MySQL 호환성**: `mysql_compat.php` (deprecated mysql_* 함수 지원)

## 디렉토리 구조

```
as/
├── index.php              # 메인 진입점 (로그인 페이지로 리다이렉트)
├── login.php              # 로그인 페이지
├── login_process.php      # 로그인 처리
├── logout.php             # 로그아웃
├── dashboard.php          # 대시보드 (메인 화면)
├── db_config.php          # DB 연결 설정 (운영)
├── db_config.local.php    # DB 연결 설정 (로컬)
├── mysql_compat.php       # MySQL 호환성 레이어
│
├── as_task/               # AS 작업 관리
│   ├── as_requests.php    # AS 접수 목록
│   ├── as_request_handler.php
│   ├── as_repair.php      # AS 수리 상세
│   ├── as_repair_handler.php
│   ├── as_repair_get_parts.php
│   └── as_receipt.php     # AS 영수증 출력
│
├── orders/                # 자재 판매 관리
│   ├── orders.php         # 주문 목록
│   ├── order_handler.php
│   ├── order_edit.php     # 주문 수정
│   ├── order_payment.php  # 입금 확인
│   └── order_receipt.php  # 판매 영수증 출력
│
├── parts/                 # 자재 관리
│   ├── parts.php          # 자재 목록
│   ├── parts_add.php
│   ├── parts_edit.php
│   ├── category_add.php   # 카테고리 관리
│   └── category_edit.php
│
├── products/              # 제품 관리
│   ├── products.php       # 제품 목록
│   ├── product_add.php
│   ├── product_edit.php
│   ├── poor_add.php       # 불량증상 관리
│   ├── poor_edit.php
│   ├── result_add.php     # AS결과 관리
│   └── result_edit.php
│
├── customers/             # 고객 관리
│   ├── members.php        # 고객 목록
│   ├── member_add.php
│   ├── member_edit.php
│   └── member_delete.php
│
└── stat/                  # 통계/분석
    ├── statistics.php     # 통계 대시보드
    ├── export_as_report.php      # AS 리포트 엑셀
    ├── export_sales_report.php   # 판매 리포트 엑셀
    └── export_monthly_report.php # 월간 종합 리포트 엑셀
```

## 주요 기능

### 1. 대시보드 (`dashboard.php`)
- 이번 달 AS 완료 건수
- 이번 달 자재 판매 건수
- 최근 12개월 매출 그래프 (Chart.js)
- 회계 마감일 기준 (전월 26일 ~ 당월 25일)

### 2. AS 작업 (`as_task/`)
- AS 접수/수리/완료 관리
- 제품별 수리 이력
- 교체 자재 등록
- 영수증 출력

### 3. 자재 판매 (`orders/`)
- 소모품 판매 관리
- 구매신청 목록 (입금 확인 필요)
- 판매 완료 목록
- 영수증 출력

### 4. 통계/분석 (`stat/`)
- **개요 탭**: 종합 통계, TOP3 수리 제품/자재, TOP3 판매 자재
- **월간 리포트 탭**: 월별 종합 매출 결과
- **AS 분석 탭**: TOP10 수리 모델, TOP10 교체 자재, 연도별/월별 그래프
- **판매 분석 탭**: TOP10 판매 자재, 연도별/월별 그래프
- 엑셀 리포트 다운로드

## 데이터베이스 테이블

| 테이블명 | 설명 |
|----------|------|
| `2010_admin_member` | 관리자 계정 |
| `step13_as` | AS 접수 마스터 |
| `step14_as_item` | AS 제품 아이템 |
| `step15_as_model` | 제품 모델 |
| `step16_as_poor` | 불량증상 타입 |
| `step17_as_result` | AS결과 타입 |
| `step18_as_cure_cart` | AS 교체 자재 |
| `step20_sell` | 자재 판매 마스터 |
| `step21_sell_cart` | 판매 자재 아이템 |
| `step05_parts` | 자재 마스터 |
| `step05_parts_category` | 자재 카테고리 |

## 개발 환경 설정

### 로컬 개발
1. `db_config.local.php` 파일 수정
2. Docker 또는 로컬 MySQL 서버 필요

```php
// db_config.local.php 예시
define('DB_HOST', 'localhost');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
```

### 운영 배포
- SFTP로 dcom.co.kr 서버에 직접 업로드
- `db_config.php` 사용 (운영 DB 설정)

## Git 커밋 규칙

```
feat: 새로운 기능 추가
fix: 버그 수정
refactor: 코드 리팩토링
docs: 문서 수정
style: 코드 스타일 변경
```

## 최근 변경 이력

- `v0.6.5` - 월별 매출 그래프 최근 12개월 방식으로 변경
- `v0.6.4` - 버튼 상태 수정
- `v0.6.3` - 사용자 지정 날짜 검색 기능
- `v0.6.2` - 기본 월 설정 수정
- `v0.6.1` - 월간 리포트 스타일 개선

## 주의사항

1. **회계 마감일**: 매월 26일~25일 기준으로 집계
2. **인코딩**: UTF-8 (utf8mb4) 사용
3. **PHP 버전**: mysql_* 함수 호환 레이어 필수 (PHP 7.0+)
4. **세션**: 로그인 시 `member_id`, `member_sid` 세션 필수
