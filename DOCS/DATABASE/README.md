# DATABASE 문서

mic4u AS 시스템의 데이터베이스 스키마와 구조에 관한 문서들입니다.

**현재 인코딩**: UTF-8 (utf8mb4)
**마지막 업데이트**: 2025-11-06

---

## 📚 문서 목록

### 1. AS_DATABASE_SCHEMA.md
**전체 데이터베이스 스키마 개요**

- 21개 핵심 테이블 정의
- 테이블 간 관계도 및 FK 관계
- AS 요청 프로세스 4단계
- 상태 값 정의
- 검색 및 조회 쿼리 예제

**주요 테이블**:
- step13_as - AS 신청 메인 테이블
- step14_as_item - AS 항목 (1:N 관계)
- step15_as_model - 모델 정보
- step16_as_poor - 불량증상 분류
- step20_sell - 자재 판매 주문
- step21_sell_cart - 판매 주문 상세

---

### 2. STEP13_AS_TABLE_STRUCTURE.md
**step13_as 테이블 상세 분석**

- step13_as의 전체 컬럼 구조 (30+ 컬럼)
- 데이터 타입 상세 분석
- 필수 vs 선택 필드
- 상태 값 정의
- PHP 코드 예시
- 성능 최적화 권장사항

---

## 🔄 데이터베이스 구조

### AS 요청 프로세스 (step13_as 중심)
```
step13_as (AS 신청 메인)
  ├─ step14_as_item (AS 항목 - 1:N)
  │  ├─ step15_as_model (모델)
  │  └─ step16_as_poor (불량증상)
  ├─ step2_center (AS 센터)
  ├─ step3_member (처리 담당자)
  └─ step11_member (고객/거래처)
```

### 자재 판매 프로세스 (step20_sell 중심)
```
step20_sell (판매 주문 메인)
  ├─ step21_sell_cart (판매 상세 - 1:N)
  │  └─ step1_parts (자재/부품)
  └─ step11_member (거래처)
```

---

## 📖 어떤 문서부터 읽을까?

**DBA/Database 엔지니어**:
1. AS_DATABASE_SCHEMA.md - 전체 구조 파악
2. STEP13_AS_TABLE_STRUCTURE.md - 핵심 테이블 이해

**백엔드 개발자**:
1. AS_DATABASE_SCHEMA.md - 테이블 관계 파악
2. STEP13_AS_TABLE_STRUCTURE.md - 쿼리 작성 참고
3. ../PAGES/README.md - 데이터 흐름 확인

**운영자**:
1. AS_DATABASE_SCHEMA.md - 대략적인 구조만 확인
2. 백업 및 성능 관리 문서

---

**마지막 수정**: 2025-11-06
