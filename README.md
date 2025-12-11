# AS System - 서비스 관리 시스템

> WordPress 사이트 하위 디렉토리(`/as`)로 통합된 AS(After-Sales) 서비스 관리 애플리케이션

## 프로젝트 개요

AS System은 PHP 기반 AS 및 서비스 관리 플랫폼입니다. 현재 dcom.co.kr 호스팅 서버에 배포되어 운영 중입니다.

**운영 URL**: `https://dcom.co.kr/as/`

## 디렉토리 구조

```
.
├── as/                     # AS System (메인 애플리케이션)
│   ├── db_config.php       # 데이터베이스 설정
│   ├── dashboard.php       # 대시보드
│   ├── as_requests.php     # AS 접수 관리
│   ├── orders.php          # 주문 관리
│   ├── members.php         # 회원 관리
│   ├── products.php        # 제품/모델 관리
│   ├── parts.php           # 자재 관리
│   ├── orders/             # 주문 상세 모듈
│   ├── lib/                # 공통 라이브러리
│   └── css/                # 스타일시트
│
├── vendor/                 # Composer 패키지
├── logs/                   # 로그 파일
├── .claude/                # Claude Code 설정
├── .vscode/                # VSCode 설정 (sftp.json 포함)
├── CLAUDE.md               # 개발 가이드라인
└── README.md               # 이 파일
```

## 서버 환경

| 항목 | 정보 |
|------|------|
| 호스팅 | dcom.co.kr |
| 서버 경로 | `/home/hosting_users/dcom2000/www/as/` |
| PHP 버전 | 7.x |
| 데이터베이스 | MySQL (dcom2000) |
| 문자 인코딩 | UTF-8 |

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

## 주요 기능

- **AS 접수 관리**: 고객 AS 요청 등록/조회/처리
- **주문 관리**: 자재 주문 및 판매 관리
- **회원 관리**: 고객/대리점 정보 관리
- **제품 관리**: 모델, 불량증상, AS결과 유형 관리
- **자재 관리**: 부품/자재 재고 및 카테고리 관리
- **대시보드**: 통계 및 현황 요약

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
