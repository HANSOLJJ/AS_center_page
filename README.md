# AS System - 서비스 관리 시스템

> WordPress 사이트 하위 디렉토리(`/as`)로 통합된 AS(After-Sales) 서비스 관리 애플리케이션

## 프로젝트 개요

AS System은 PHP 기반 AS 및 서비스 관리 플랫폼입니다. 현재 dcom.co.kr 호스팅 서버에 배포되어 운영 중입니다.

**운영 URL**: `https://dcom.co.kr/as/`

## 디렉토리 구조

```
.
├── as/                         # AS System (메인 애플리케이션)
│   ├── index.php               # 진입점 (login.php로 리다이렉트)
│   ├── login.php               # 로그인 페이지
│   ├── login_process.php       # 로그인 처리 (인증 로직)
│   ├── logout.php              # 로그아웃 처리
│   ├── dashboard.php           # 대시보드 (메인 화면)
│   ├── db_config.php           # DB 설정 (운영용)
│   ├── db_config.local.php     # DB 설정 (로컬 개발용)
│   ├── mysql_compat.php        # MySQL 호환성 레이어
│   │
│   ├── as_task/                # AS 접수/수리 모듈
│   │   ├── as_requests.php     # AS 접수 목록 (검색, 필터링)
│   │   ├── as_request_handler.php  # AS 접수 AJAX 처리
│   │   ├── as_receipt.php      # AS 접수 등록/수정 폼
│   │   ├── as_repair.php       # AS 수리 처리 화면
│   │   ├── as_repair_handler.php   # AS 수리 AJAX 처리
│   │   └── as_repair_get_parts.php # 자재 검색 API
│   │
│   ├── orders/                 # 주문/판매 모듈
│   │   ├── orders.php          # 주문 목록 (검색, 필터링)
│   │   ├── order_handler.php   # 주문 AJAX 처리
│   │   ├── order_receipt.php   # 주문 등록/수정 폼
│   │   ├── order_edit.php      # 주문 수정
│   │   └── order_payment.php   # 결제 처리
│   │
│   ├── customers/              # 고객/회원 모듈
│   │   ├── members.php         # 회원 목록
│   │   ├── member_add.php      # 회원 등록
│   │   ├── member_edit.php     # 회원 수정
│   │   └── member_delete.php   # 회원 삭제 (AJAX)
│   │
│   ├── products/               # 제품 관리 모듈
│   │   ├── products.php        # 제품 목록 (3탭: 모델/불량증상/AS결과)
│   │   ├── product_add.php     # 모델 등록
│   │   ├── product_edit.php    # 모델 수정
│   │   ├── poor_add.php        # 불량증상 유형 등록
│   │   ├── poor_edit.php       # 불량증상 유형 수정
│   │   ├── result_add.php      # AS결과 유형 등록
│   │   └── result_edit.php     # AS결과 유형 수정
│   │
│   ├── parts/                  # 자재 관리 모듈
│   │   ├── parts.php           # 자재 목록 (5탭: 자재/카테고리/...)
│   │   ├── parts_add.php       # 자재 등록
│   │   ├── parts_edit.php      # 자재 수정
│   │   ├── category_add.php    # 카테고리 등록
│   │   └── category_edit.php   # 카테고리 수정
│   │
│   └── stat/                   # 통계/리포트 모듈
│       ├── statistics.php      # 통계 대시보드
│       ├── export_as_report.php      # AS 리포트 엑셀 내보내기
│       ├── export_monthly_report.php # 월간 리포트 내보내기
│       └── export_sales_report.php   # 판매 리포트 내보내기
│
├── vendor/                 # Composer 패키지
├── logs/                   # 로그 파일
├── .claude/                # Claude Code 설정
├── .vscode/                # VSCode 설정 (sftp.json 포함)
├── CLAUDE.md               # 개발 가이드라인
└── README.md               # 이 파일
```

## 서버 환경 및 접속 정보

| 항목 | 정보 |
|------|------|
| 호스팅 | dcom.co.kr |
| 서버 경로 | `/home/hosting_users/dcom2000/www/as/` |
| PHP 버전 | 7.x |
| 문자 인코딩 | UTF-8 |

### SSH 접속 정보

| 항목 | 값 |
|------|-----|
| Host | dcom.co.kr |
| Port | 22 |
| Username | dcom2000 |
| Password | `Noblein12!!` |

### 데이터베이스 접속 정보

| 항목 | 값 |
|------|-----|
| Host | localhost |
| Database | dcom2000 |
| Username | dcom2000 |
| Password | `Basserd2@@` |

## 데이터베이스

### 테이블 구조

| 테이블명 | 설명 |
|----------|------|
| `step13_as` | AS 접수 내역 |
| `step14_as_item` | AS 품목 |
| `step15_as_model` | 제품 모델 |
| `step16_as_poor` | 불량 증상 유형 |
| `step18_as_cure_cart` | AS 처리 자재 |
| `step19_as_result` | AS 결과 유형 |
| `step1_parts` | 자재 목록 |
| `step2_center` | AS 센터 정보 |
| `step5_category` | 자재 카테고리 |
| `step11_member` | 회원 정보 |
| `step20_sell` | 판매 내역 |
| `step21_sell_cart` | 판매 품목 |
| `2010_admin_member` | 관리자 계정 |

## 배포 및 동기화

### VSCode SFTP Extension 사용

로컬에서 파일 수정 시 원격 서버로 자동 업로드됩니다.

**설정 파일**: `.vscode/sftp.json`

```json
{
    "name": "dcom.co.kr AS System",
    "host": "dcom.co.kr",
    "protocol": "sftp",
    "port": 22,
    "username": "dcom2000",
    "remotePath": "/home/hosting_users/dcom2000/www",
    "uploadOnSave": true,
    "syncMode": "update"
}
```

**주요 기능**:
- `uploadOnSave: true` - 파일 저장 시 자동 업로드
- 새 파일 추가 - 저장하면 원격에도 자동 생성
- 파일 삭제 - 수동으로 `SFTP: Delete Remote` 명령 필요 (자동 삭제 안됨)
- `SFTP: Sync Local -> Remote` - 전체 동기화

**제외 파일** (원격에 업로드 안됨):
- `.git`, `.gitignore`
- `.claude/**`, `.vscode/**`
- `*.md` 파일들
- `as/db_config.local.php` (로컬 전용 설정)

### SSH 접속 (수동)

```bash
ssh dcom2000@dcom.co.kr
```

### Claude Code MCP 도구

Claude Code에서 SSH MCP를 통해 원격 작업 가능:
- `mcp__ssh-mcp-server__execute-command` - 원격 명령 실행
- `mcp__ssh-mcp-server__upload` - 파일 업로드
- `mcp__ssh-mcp-server__download` - 파일 다운로드

## 개발 환경 설정

### 파일 인코딩
- 레거시 파일: EUC-KR
- 신규 파일: UTF-8
- VSCode 설정: `.vscode/settings.json`에 자동 인코딩 감지 설정됨

### 코드 스타일
- 상세 가이드라인: `CLAUDE.md` 참조
- 기존 코드 패턴 유지

## 주요 기능 및 페이지 상세

### 인증 (`as/`)

| 파일 | 설명 |
|------|------|
| `index.php` | 진입점, 로그인 페이지로 리다이렉트 |
| `login.php` | 로그인 폼 화면 |
| `login_process.php` | 로그인 인증 처리 (세션 생성) |
| `logout.php` | 로그아웃 (세션 파기) |
| `dashboard.php` | 로그인 후 메인 대시보드 |

### AS 접수/수리 (`as/as_task/`)

| 파일 | 설명 |
|------|------|
| `as_requests.php` | AS 접수 목록, 검색/필터/페이징 |
| `as_request_handler.php` | AS 접수 CRUD AJAX 처리 |
| `as_receipt.php` | AS 접수 등록/수정 폼 |
| `as_repair.php` | AS 수리 처리 화면 (자재 사용 기록) |
| `as_repair_handler.php` | AS 수리 완료 AJAX 처리 |
| `as_repair_get_parts.php` | 자재 자동완성 검색 API |

### 주문/판매 (`as/orders/`)

| 파일 | 설명 |
|------|------|
| `orders.php` | 주문 목록, 검색/필터/페이징 |
| `order_handler.php` | 주문 CRUD AJAX 처리 |
| `order_receipt.php` | 주문 등록 폼 |
| `order_edit.php` | 주문 수정 폼 |
| `order_payment.php` | 결제 상태 처리 |

### 고객/회원 (`as/customers/`)

| 파일 | 설명 |
|------|------|
| `members.php` | 회원 목록, 검색/페이징 |
| `member_add.php` | 회원 등록 폼 |
| `member_edit.php` | 회원 수정 폼 |
| `member_delete.php` | 회원 삭제 AJAX 처리 |

### 제품 관리 (`as/products/`)

| 파일 | 설명 |
|------|------|
| `products.php` | 제품 관리 (3탭: 모델/불량증상/AS결과) |
| `product_add.php` | 제품 모델 등록 |
| `product_edit.php` | 제품 모델 수정 |
| `poor_add.php` | 불량증상 유형 등록 |
| `poor_edit.php` | 불량증상 유형 수정 |
| `result_add.php` | AS결과 유형 등록 |
| `result_edit.php` | AS결과 유형 수정 |

### 자재 관리 (`as/parts/`)

| 파일 | 설명 |
|------|------|
| `parts.php` | 자재 관리 (5탭: 자재/카테고리/...) |
| `parts_add.php` | 자재 등록 |
| `parts_edit.php` | 자재 수정 |
| `category_add.php` | 자재 카테고리 등록 |
| `category_edit.php` | 자재 카테고리 수정 |

### 통계/리포트 (`as/stat/`)

| 파일 | 설명 |
|------|------|
| `statistics.php` | 통계 대시보드 |
| `export_as_report.php` | AS 리포트 엑셀 다운로드 |
| `export_monthly_report.php` | 월간 리포트 엑셀 다운로드 |
| `export_sales_report.php` | 판매 리포트 엑셀 다운로드 |

### 설정/유틸리티

| 파일 | 설명 |
|------|------|
| `db_config.php` | 운영 DB 접속 설정 |
| `db_config.local.php` | 로컬 개발용 DB 설정 (gitignore) |
| `mysql_compat.php` | mysql_* → mysqli 호환성 레이어 |

## 보안 주의사항

- 설정 파일(`db_config.php`) 외부 접근 차단
- SQL 인젝션 방지를 위한 입력값 검증 필요
- XSS 방지를 위한 출력값 이스케이프
- `sftp.json`에 비밀번호 포함 - 공개 저장소 주의

## Git 저장소

- **Repository**: https://github.com/HANSOLJJ/AS_center_page.git
- **Branch**: main

### 로컬 ↔ GitHub ↔ 원격 서버 관계

```
[로컬 PC]  ----git push---->  [GitHub]
    |
    +----SFTP upload---->  [원격 서버 dcom.co.kr]
```

- **Git**: 로컬 ↔ GitHub 버전 관리
- **SFTP**: 로컬 → 원격 서버 배포 (독립적)

---

**최종 업데이트**: 2025-12-11
**버전**: 1.1
