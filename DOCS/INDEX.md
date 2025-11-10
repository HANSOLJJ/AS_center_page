# mic4u AS 시스템 문서 INDEX

**마지막 업데이트**: 2025-11-06
**시스템 상태**: 운영 중 (UTF-8MB4 인코딩 마이그레이션 완료)

---

## 📖 문서 구조

### DOCS/
```
DOCS/
├── INDEX.md (이 파일)
├── DATABASE/
│   ├── README.md
│   ├── AS_DATABASE_SCHEMA.md - 전체 데이터베이스 스키마
│   └── STEP13_AS_TABLE_STRUCTURE.md - step13_as 테이블 상세 분석
└── PAGES/
    ├── README.md
    └── ORDERS_SYSTEM_STRUCTURE.md - 자재 판매 시스템 구조
```

---

## 🎯 빠른 시작 가이드

### "데이터베이스를 이해하고 싶어요"
**→ 순서**:
1. DOCS/DATABASE/README.md (5분)
   - 문서 목록과 구조 파악
2. DOCS/DATABASE/AS_DATABASE_SCHEMA.md (15분)
   - 전체 테이블 관계도 이해
3. DOCS/DATABASE/STEP13_AS_TABLE_STRUCTURE.md (20분)
   - 핵심 테이블 step13_as 상세 분석

**예상 시간**: 40분

---

### "페이지 개발을 시작하고 싶어요"
**→ 순서**:
1. DOCS/PAGES/README.md (5분)
   - 현재 구현된 페이지 목록
2. DOCS/PAGES/ORDERS_SYSTEM_STRUCTURE.md (15분)
   - 자재 판매 시스템 흐름 이해
3. DOCS/DATABASE/AS_DATABASE_SCHEMA.md (필요시)
   - 테이블 관계 참고
4. 코드 작성 시 DOCS/DATABASE/STEP13_AS_TABLE_STRUCTURE.md 참고
   - SQL 쿼리 예제 활용

**예상 시간**: 30분 + 실제 개발 시간

---

### "운영을 담당하고 있어요"
**→ 순서**:
1. DOCS/DATABASE/README.md (5분)
   - 데이터베이스 구조 대략 파악
2. 백업 및 성능 관리 문서 (별도)
   - 아직 미작성, 필요시 문의

**예상 시간**: 5분

---

### "AS 시스템 흐름을 전체적으로 이해하고 싶어요"
**→ 순서**:
1. DOCS/PAGES/README.md (5분)
   - 현재 페이지들의 구조
2. DOCS/DATABASE/README.md (5분)
   - 데이터베이스 기본 구조
3. DOCS/PAGES/ORDERS_SYSTEM_STRUCTURE.md (15분)
   - 자재 판매 시스템의 상세 흐름
4. DOCS/DATABASE/STEP13_AS_TABLE_STRUCTURE.md (20분)
   - 핵심 테이블 상세 분석

**예상 시간**: 45분

---

## 📚 전체 문서 맵

### DATABASE (데이터베이스 관련)

#### DOCS/DATABASE/README.md
- **목적**: DATABASE 폴더의 문서 소개
- **대상**: 모든 개발자
- **읽는 시간**: 5분
- **포함 내용**:
  - 문서 목록 및 설명
  - 테이블 관계도
  - 테이블별 행 수 기준
  - 주요 쿼리 패턴
  - 주요 주의사항

#### DOCS/DATABASE/AS_DATABASE_SCHEMA.md
- **목적**: 전체 데이터베이스 스키마 개요
- **대상**: DBA, 데이터베이스 엔지니어, 백엔드 개발자
- **읽는 시간**: 15-20분
- **포함 내용**:
  - 21개 데이터베이스 테이블 정의
  - 테이블 간 관계도
  - Foreign Key 관계
  - AS 요청 프로세스 4단계
  - 상태 값 정의
  - 검색 및 조회 쿼리 예제
  - UTF-8MB4 마이그레이션 정보
  - 데이터 흐름 및 상태 전이도

#### DOCS/DATABASE/STEP13_AS_TABLE_STRUCTURE.md
- **목적**: step13_as 테이블 상세 분석 (가장 중요한 테이블)
- **대상**: 개발자, DB 관리자, AS 시스템 운영자
- **읽는 시간**: 20-30분
- **포함 내용**:
  - step13_as 전체 컬럼 구조 (30+ 컬럼)
  - 컬럼별 데이터 타입 설명
  - 필수 vs 선택 필드
  - 상태 값 정의 및 상태 전이도
  - PHP 코드 예시 (SELECT, INSERT, UPDATE, DELETE)
  - 성능 최적화 권장사항
  - 자주 사용되는 WHERE 조건
  - step13_as와 step14_as_item의 1:N 관계

---

### PAGES (페이지 구조 및 역할)

#### DOCS/PAGES/README.md
- **목적**: PAGES 폴더의 문서 소개
- **대상**: 모든 개발자
- **읽는 시간**: 5분
- **포함 내용**:
  - 현재 구현된 주요 페이지 목록
  - 페이지별 탭 구조
  - 공통 패턴 설명
  - UI 스타일 일관성
  - 다음 개발 계획

#### DOCS/PAGES/ORDERS_SYSTEM_STRUCTURE.md
- **목적**: 자재 판매 시스템 (Orders System) 상세 구조
- **대상**: 백엔드 개발자, 시스템 운영자
- **읽는 시간**: 20-25분
- **포함 내용**:
  - 시스템 개요 및 흐름도
  - 5개 핵심 페이지 상세 설명
    - order_handler.php (신규 주문)
    - orders.php (주문 목록)
    - order_edit.php (주문 수정)
    - order_payment.php (상태 업데이트)
    - receipt.php (영수증)
  - 각 페이지의 API 액션 설명
  - 상태 전이도
  - 페이지 요청/응답 흐름
  - 주요 SQL 쿼리 예제
  - 접수번호 생성 로직

---

## 🔍 목적별 문서 찾기

### "step13_as 테이블 구조를 알고 싶어요"
→ DOCS/DATABASE/STEP13_AS_TABLE_STRUCTURE.md

### "step13_as에 새로운 컬럼을 추가하고 싶어요"
→ 읽기: STEP13_AS_TABLE_STRUCTURE.md (필드 정의)
→ 참고: AS_DATABASE_SCHEMA.md (전체 관계도)

### "AS 신청 목록 페이지를 수정하고 싶어요"
→ 읽기: DOCS/PAGES/README.md (as_requests.php 섹션)
→ 참고: AS_DATABASE_SCHEMA.md (테이블 관계)
→ 참고: STEP13_AS_TABLE_STRUCTURE.md (SQL 쿼리)

### "새로운 판매 기능을 추가하고 싶어요"
→ 읽기: ORDERS_SYSTEM_STRUCTURE.md
→ 참고: AS_DATABASE_SCHEMA.md (step20_sell, step21_sell_cart)

### "데이터 마이그레이션을 하고 싶어요"
→ 읽기: AS_DATABASE_SCHEMA.md (마이그레이션 섹션)
→ 참고: STEP13_AS_TABLE_STRUCTURE.md (데이터 타입)

### "성능 문제를 해결하고 싶어요"
→ 읽기: STEP13_AS_TABLE_STRUCTURE.md (성능 최적화 섹션)
→ 참고: AS_DATABASE_SCHEMA.md (인덱스 정보)

---

## 🛠️ 관련 파일 위치

### 설정 파일
- `as/@config.php` - 데이터베이스 설정
- `as/mysql_compat.php` - MySQL 호환성 및 UTF-8MB4 설정

### 메인 페이지
- `as/as_requests.php` - AS 신청 목록
- `as/dashboard.php` - 대시보드
- `as/orders.php` - 판매 주문

### 자재 관리
- `as/parts.php` - 자재 관리
- `as/products.php` - 제품/모델 관리

### 고객 관리
- `as/members.php` - 고객/거래처 관리

### 판매 주문
- `as/order_handler.php` - 주문 API
- `as/order_edit.php` - 주문 수정
- `as/order_payment.php` - 주문 상태
- `as/receipt.php` - 영수증

---

## 📊 현재 시스템 상태

### 구현 완료
✅ AS 신청 관리 (as_requests.php)
✅ 자재 관리 (parts.php)
✅ 제품 관리 (products.php)
✅ 고객 관리 (members.php)
✅ 판매 주문 관리 (orders.php, order_*.php)
✅ UTF-8MB4 인코딩 마이그레이션
✅ 데이터베이스 스키마 문서화

### 진행 중
🟠 페이지 구조 및 역할 문서화
🟠 MIGRATION_DOCS 정리

### 예정
⏳ as_request_handler.php (AS 신청 AJAX API)
⏳ 추가 기능 개발

---

## 📋 문서 관리

### 버전 관리
- 각 문서는 "마지막 수정" 날짜를 포함합니다
- 문서 수정 시 날짜를 업데이트하세요
- 예: `**마지막 수정**: 2025-11-06`

### 문서 추가 시
1. DOCS/DATABASE/ 또는 DOCS/PAGES/에 .md 파일 추가
2. 해당 폴더의 README.md에 문서 추가
3. 이 INDEX.md에 항목 추가
4. Git commit 실행

### 문서 수정 시
1. 해당 .md 파일 수정
2. "마지막 수정" 날짜 업데이트
3. 필요시 INDEX.md 업데이트
4. Git commit 실행

---

## 💬 문서 관련 문의

### "이 문서에 오류가 있어요"
→ 문서 파일 직접 수정 후 Git commit

### "새로운 기능 문서가 필요해요"
→ 새로운 .md 파일 생성 → README.md에 추가 → INDEX.md 업데이트

### "문서가 너무 복잡해요"
→ 더 간단한 설명으로 수정 요청

---

## 🚀 다음 단계

### 즉시 필요
1. MIGRATION_DOCS 폴더 정리 (오래된 문서 삭제)
2. as_request_handler.php AJAX API 문서 작성
3. 대시보드 기능 문서 작성

### 중기 계획 (1-2주)
1. 각 페이지의 상세 개발 가이드 작성
2. 추가 기능 문서화 (SMS, 이메일 등)
3. 성능 최적화 가이드 작성

### 장기 계획 (1개월+)
1. API 문서 작성 (RESTful 구조로 개선 시)
2. 통합 테스트 가이드
3. 배포 및 모니터링 가이드

---

**마지막 수정**: 2025-11-06
**관리자**: Claude Code
**버전**: 1.0

---

🎯 **TIP**: 이 INDEX.md를 북마크하고 문서가 필요할 때마다 참고하세요!
