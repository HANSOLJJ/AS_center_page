# 자재 판매(Consumables Sales) 테이블 분석

**작성일**: 2025-11-03
**분석 대상**: step20_sell, step21_sell_cart 테이블
**용도**: 부품/자재 판매 거래 관리
**상태**: 분석 완료 ✅

---

## 📊 1. 테이블 구조 상세 분석

### 1.1 step20_sell 테이블 (판매 정보)

판매 거래의 메인 정보를 저장하는 핵심 테이블입니다.

| 필드명                | 타입     | 길이   | PK  | FK  | 설명                                                  |
| --------------------- | -------- | ------ | --- | --- | ----------------------------------------------------- |
| **s20_sellid**        | INT      | -      | ✅  | -   | 판매 ID (자동증가)                                    |
| **s20_as_in_no**      | VARCHAR  | (50)   | -   | -   | **판매 접수 번호** (사용자 표시용)                    |
| **s20_as_in_no2**     | VARCHAR  | (50)   | -   | -   | **시퀀스 번호** (내부 max() 계산용)                   |
| **s20_partid**        | INT      | -      | -   | ✅  | 부품 ID (step1_parts 참조)                            |
| **s20_qty**           | INT      | -      | -   | -   | 판매 수량                                             |
| **s20_unit_price**    | DECIMAL  | (10,2) | -   | -   | 단가                                                  |
| **s20_total_price**   | DECIMAL  | (12,2) | -   | -   | 합계금액 (수량 × 단가)                                |
| **s20_total_cost**    | DECIMAL  | (12,2) | -   | -   | 판매 총액                                             |
| **s20_sell_date**     | DATE     | -      | -   | -   | 판매 일자                                             |
| **s20_as_level**      | VARCHAR  | (10)   | -   | -   | 판매 레벨/분류                                        |
| **s20_as_center**     | VARCHAR  | (20)   | -   | ✅  | AS센터 ID (step2_center 참조)                         |
| **s20_sell_in_date**  | DATETIME | -      | -   | -   | **판매 입고/등록 일시** (HH:00:00) ✅                 |
| **s20_bank_check**    | DATETIME | -      | -   | -   | **입금 확인 일시** (HH:00:00) ✅                      |
| **s20_bankcheck_w**   | VARCHAR  | (20)   | -   | -   | **입금 확인자** ('center'=센터 현금, 'base'=계좌이체) |
| **s20_sell_name1**    | VARCHAR  | (100)  | -   | -   | AS 처리 기사명                                        |
| **s20_meid**          | VARCHAR  | (20)   | -   | -   | 직원 ID                                               |
| **s20_tax_code**      | VARCHAR  | (50)   | -   | -   | 세금계산서 여부 ('on'/'')                             |
| **s20_dex_send**      | INT      | -      | -   | -   | 배송 발송 여부                                        |
| **s20_dex_send_name** | VARCHAR  | (50)   | -   | -   | 배송사 명 (CJ/GS/로젠)                                |
| **s20_dex_no**        | VARCHAR  | (50)   | -   | -   | 배송 추적번호                                         |
| **s20_as_out_date**   | DATETIME | -      | -   | -   | **판매 완료/출고 일시** (HH:00:00) ✅                 |
| **ex_tel**            | VARCHAR  | (50)   | -   | -   | 고객 연락처                                           |
| **ex_sms_no**         | VARCHAR  | (20)   | -   | -   | 고객 휴대폰                                           |
| **ex_sec1**           | VARCHAR  | (20)   | -   | -   | 고객 분류 ('일반'/'대리점'/'딜러')                    |
| **ex_sec2**           | VARCHAR  | (50)   | -   | -   | 고객 추가 분류                                        |
| **ex_company**        | VARCHAR  | (255)  | -   | -   | 고객 회사명/업체명                                    |
| **ex_man**            | VARCHAR  | (50)   | -   | -   | 고객 담당자 명                                        |
| **ex_address**        | VARCHAR  | (255)  | -   | -   | 고객 주소                                             |
| **ex_address_no**     | VARCHAR  | (50)   | -   | -   | 고객 상세주소                                         |
| **ex_company_no**     | VARCHAR  | (50)   | -   | -   | 고객 사업자번호                                       |

**데이터량**: ~10,000개/년
**마이그레이션 난이도**: ⭐⭐ 중간
**접근 패턴**: orders.php에서 조회 위주, 월간 통계 집계

**📝 필드 타입 변환 (2025-11-03)**:

- s20_sell_in_date: VARCHAR(255) timestamp → **DATETIME** (YYYY-MM-DD HH:00:00)
- s20_bank_check: INT timestamp → **DATETIME** (YYYY-MM-DD HH:00:00)
- s20_as_out_date: VARCHAR(255) timestamp → **DATETIME** (YYYY-MM-DD HH:00:00)
- **분/초는 모두 :00:00으로 통일됨** (시간 단위로만 표현)

---

### 1.2 step21_sell_cart 테이블 (판매 장바구니)

판매 처리 중 임시로 선택된 부품 항목들을 저장합니다.

| 필드명                          | 타입      | 길이   | PK  | FK  | 설명                                |
| ------------------------------- | --------- | ------ | --- | --- | ----------------------------------- |
| **s21_cartid** (또는 s21_accid) | INT       | -      | ✅  | -   | 장바구니 ID (자동증가)              |
| **s21_sellid**                  | INT       | -      | -   | ✅  | 판매 ID (step20_sell 참조)          |
| **s21_uid**                     | INT       | -      | -   | ✅  | 부품 ID (step1_parts.s1_uid 참조)   |
| **s21_quantity**                | INT       | -      | -   | -   | 수량                                |
| **s21_signdate**                | TIMESTAMP | -      | -   | -   | 등록 일시 (Unix timestamp)          |
| **s21_end**                     | VARCHAR   | (1)    | -   | -   | 종료 여부 ('Y'/'N')                 |
| **s21_sp_cost**                 | DECIMAL   | (12,2) | -   | -   | 특정 비용                           |
| **cost_name**                   | VARCHAR   | (255)  | -   | -   | 부품명 (s1_name 저장)               |
| **cost_sn**                     | VARCHAR   | (50)   | -   | -   | **부품 ERP 코드** (s1_erp 저장)     |
| **cost1**                       | DECIMAL   | (10,2) | -   | -   | 주 비용 (고객 분류별로 선택된 가격) |
| **cost2**                       | DECIMAL   | (10,2) | -   | -   | 특별공급가 (s1_cost_s_1)            |
| **cost3**                       | DECIMAL   | (10,2) | -   | -   | 원가 (s1_cost_won)                  |
| **cost_sec**                    | VARCHAR   | (20)   | -   | -   | 고객 분류 ('일반'/'대리점'/'딜러')  |

**데이터량**: ~500개 (임시 데이터이므로 자주 정리됨)
**마이그레이션 난이도**: ⭐⭐ 중간 (다수의 비용 필드 포함)
**접근 패턴**: 판매 처리 중 CRUD 작업

### ⚠️ 주의사항

- **cost_sn**: s1_erp 값을 저장하는데, 레거시 데이터에서는 많은 부품이 ERP 코드 없이 등록되어 NULL이거나 빈 값일 수 있음
- **cost_name, cost_sn**: step1_parts 정보를 역정규화(denormalize)해서 저장하므로 데이터 무결성 검증 필요

---

## 🔗 2. 테이블 관계도

```
┌─────────────────────────────────────────────┐
│  자재 판매(Consumables Sales) 시스템      │
└─────────────────────────────────────────────┘
                       │
         ┌─────────────┼─────────────┐
         ↓             ↓             ↓
    step1_parts   step2_center    step20_sell
    (부품)        (센터)          (판매 메인)
                                     ↓
                              step21_sell_cart
                              (판매 장바구니)
```

### 2.1 Foreign Key 관계

```
step20_sell.s20_partid → step1_parts.s1_caid
step20_sell.s20_as_center → step2_center.s2_center_id
step21_sell_cart.s21_sellid → step20_sell.s20_sellid
step21_sell_cart.s21_partid → step1_parts.s1_caid
```

---

## 📈 3. 실제 사용 사례 (xls2.php 기반)

### 3.1 판매 건수 조회 쿼리

```php
// 특정 센터의 월간 판매 건수
$query = "SELECT count(*) FROM step20_sell
          WHERE s20_as_level LIKE '2'
          AND s20_as_center = 'center_001'
          AND s20_sell_in_date BETWEEN '2025-10-01' AND '2025-10-31'";
```

**사용 시나리오**:

- 월간 판매 현황 통계
- 센터별 판매 건수 비교

---

### 3.2 판매 금액 합계 쿼리

```php
// 특정 센터의 월간 판매 합계 금액
$query = "SELECT SUM(s20_total_cost) FROM step20_sell
          WHERE s20_as_level LIKE '2'
          AND s20_as_center = 'center_001'
          AND s20_sell_in_date BETWEEN '2025-10-01' AND '2025-10-31'";
```

**사용 시나리오**:

- 월간 판매 수익 계산
- 센터별 매출 리포트

---

### 3.3 부품별 판매량 조회

```php
// 부품별 판매 통계 (step1_parts 조인)
$query = "SELECT sp.s1_name, COUNT(*) as cnt, SUM(ss.s20_qty) as total_qty, SUM(ss.s20_total_price) as total_price
          FROM step20_sell ss
          JOIN step1_parts sp ON ss.s20_partid = sp.s1_caid
          WHERE ss.s20_as_level = '2'
          GROUP BY ss.s20_partid
          ORDER BY total_price DESC";
```

---

## 🔑 4. 가격 정책 및 비용 선택 로직

### 4.1 step1_parts의 5가지 가격대

부품마다 다음과 같이 5가지 가격이 저장되어 있습니다:

| 필드명          | 고객 유형 | 설명                     | 사용 시나리오     |
| --------------- | --------- | ------------------------ | ----------------- |
| **s1_cost_c_1** | 딜러      | AS CENTER 공급가         | 내부 수리 시 사용 |
| **s1_cost_a_1** | 대리점    | 대리점 공급가 (개별판매) | 대리점 판매 시    |
| **s1_cost_a_2** | 대리점    | 대리점 공급가 (수리시)   | 대리점 수리 시    |
| **s1_cost_n_1** | 일반      | 일반 판매가 (개별판매)   | 일반인 판매 시    |
| **s1_cost_n_2** | 일반      | 일반 판매가 (수리시)     | 일반인 수리 시    |
| **s1_cost_s_1** | 특별      | 특별공급가               | 특수 고객         |
| **s1_cost_won** | -         | 원가                     | 수익성 분석       |

### 4.2 step21_sell_cart의 비용 저장 방식

```php
// 고객 분류(s11_sec)에 따라 cost1을 선택
if($s11_sec == "일반") {
    $cost1 = $my_s1_cost_n_1;  // 일반 개별판매가
} else if($s11_sec == "대리점") {
    $cost1 = $my_s1_cost_a_1;  // 대리점 개별판매가
} else if($s11_sec == "딜러") {
    $cost1 = $my_s1_cost_c_1;  // AS CENTER 가격
}

// 모든 경우 동일하게 저장
$cost2 = $my_s1_cost_s_1;      // 특별공급가 (비교용)
$cost3 = $my_s1_cost_won;      // 원가 (수익성 계산용)
```

---

## 🔑 5. 접수번호 생성 로직 (s20_as_in_no vs s20_as_in_no2)

### 5.1 두 필드의 역할

| 필드              | 형식                       | 용도                   | 표시 여부        | 예시           |
| ----------------- | -------------------------- | ---------------------- | ---------------- | -------------- |
| **s20_as_in_no**  | "NO" + YYMMDD + "-" + 번호 | **사용자에게 표시**    | ✅ 영수증에 표시 | `NO250101-003` |
| **s20_as_in_no2** | YYMMDD + 번호 (숫자만)     | **시퀀스 계산 최적화** | ✗ 내부용만       | `250101003`    |

### 5.2 생성 알고리즘 (set_process.php)

```php
$today = date("ymd");  // 예: "250101"

// 최적화된 쿼리: no2를 사용해서 빠른 계산
$query = "SELECT max(s20_as_in_no2) FROM step20_sell
          WHERE s20_as_time = '250101'";
// 결과: "250101002" (숫자 비교라 빠름)

// 마지막 3자리 추출 후 +1
$str1 = substr("250101002", 6, 3);  // "002"
$mcount = $str1 + 1;                  // 3
$mcount = sprintf("%03d", 3);         // "003"

// 최종 생성
$s20_as_in_no = "NO250101-003";      // 사용자 표시 ✅
$s20_as_in_no2 = "250101003";        // 내부 계산용
```

### 5.3 no2를 사용하는 이유 (성능 최적화)

**문제**: 문자열 max() 계산은 느림

```sql
-- ❌ 느림 (문자열 비교)
SELECT max(s20_as_in_no) FROM step20_sell
WHERE s20_as_time = '250101'
-- MySQL: "NO250101-001" vs "NO250101-002" 문자열 비교
```

**해결**: 숫자만 저장해서 빠른 max() 계산

```sql
-- ✅ 빠름 (숫자 비교)
SELECT max(s20_as_in_no2) FROM step20_sell
WHERE s20_as_time = '250101'
-- MySQL: 250101001 vs 250101002 숫자 비교 (훨씬 빠름!)
```

---

## 🔑 6. s20_as_level (판매 분류) 코드

현재 코드에서 사용되는 level 값:

| 값      | 의미        | 설명                             |
| ------- | ----------- | -------------------------------- |
| **'2'** | 자재 판매   | 부품/소모품을 판매한 건          |
| **'5'** | (다른 용도) | 수리 관련 (step13_as에서도 사용) |

**참고**: s20_as_level은 step13_as의 s13_as_level과 유사한 분류 체계를 사용합니다.

---

## 🎯 7. 입금 확인 기능 (뱅크 체크)

### 7.1 입금 확인 프로세스

**파일**: `as/sell/bank_process.php`

```php
$signdate = time();
$query = "UPDATE $db20 SET s20_bank_check = '$signdate', s20_bankcheck_w = 'center' WHERE s20_sellid = '$bcode'";
```

**동작 원리**:

```
사용자가 "입금 확인" 버튼 클릭
        ↓
bank_process.php 실행
        ↓
step20_sell 업데이트:
  - s20_bank_check = 현재 Unix timestamp (입금 시간 기록)
  - s20_bankcheck_w = 'center' (센터 현금 또는 'base' 계좌이체)
        ↓
list_view2.php로 리다이렉트 (판매 완료 목록)
```

### 6.2 입금 확인 방식 (s20_bankcheck_w)

| 값            | 의미           | 설명                      |
| ------------- | -------------- | ------------------------- |
| **'center'**  | 센터 현금 납부 | 현금을 센터에서 직접 수금 |
| **'base'**    | 계좌 이체      | 은행 계좌로 입금 확인     |
| **''** (빈값) | 미확인         | 아직 입금 확인 안 함      |

### 6.3 판매 완료 상태 (s20_as_level) ⭐

**파일**: `as/sell/set_process.php` (라인 9)

```php
$s20_as_level = "2";  // 판매 완료로 설정
$query = "UPDATE $db20 SET s20_as_level = '$s20_as_level', ... WHERE s20_sellid = '$number'";
```

**상태 값**:

| 값                  | 상태         | 표시 페이지    | 설명                      |
| ------------------- | ------------ | -------------- | ------------------------- |
| **'1'** (또는 NULL) | 판매 진행 중 | list_view.php  | 부품 추가 중, 입금 미확인 |
| **'2'**             | 판매 완료    | list_view2.php | 입금 확인 후 최종 완료    |

**동작 흐름**:

1. 판매 등록 (write_20_process.php) → s20_as_level = 초기값
2. 입금 확인 클릭 → bank_process.php (s20_bank_check, s20_bankcheck_w 업데이트)
3. 판매 완료 클릭 → set_process.php (s20_as_level = '2' 변경) ✅
4. list_view2.php로 리다이렉트 (판매 완료 목록에 표시)

### 6.4 입금 확인 후 상태 표시

**set2.php에서 표시 로직 (라인 43):**

```php
$s13 = $row->s20_bankcheck_w;
if($s13 == 'center') {
    $s13 = "센터 현금납부";
} elseif($s13 == 'base') {
    $s13 = "계좌이체";
} elseif($s13 == '') {
    $s13 = "3월 8일 이후 확인가능";
}
```

**영수증(set2.php)에 표시되는 항목:**

- **대금지급**: 센터 현금납부 / 계좌이체 / 미확인
- **입금일자**: s20_bank_check 타임스탐프를 "Y년 m월 d일" 형식으로 표시

---

## 🎯 7. 데이터 흐름 및 사용 시나리오

### 7.1 판매 처리 프로세스

```
사용자가 판매 주문 생성
        ↓
step21_sell_cart에 임시 부품 추가
(여러 부품 선택 가능)
        ↓
판매 확정
        ↓
step20_sell에 판매 정보 저장
        ↓
step21_sell_cart 해당 레코드 삭제 (또는 아카이브)
```

### 6.2 주요 페이지 및 모듈

| 파일            | 기능                | 테이블                        |
| --------------- | ------------------- | ----------------------------- |
| **orders.php**  | 판매 목록 조회/관리 | step20_sell                   |
| **sell/\*.php** | 판매 추가/수정/삭제 | step20_sell, step21_sell_cart |

---

## 💾 7. 마이그레이션 전략

### 7.1 EUC-KR → UTF-8MB4 변환

```sql
-- step20_sell 테이블 변환
ALTER TABLE step20_sell CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- step21_sell_cart 테이블 변환
ALTER TABLE step21_sell_cart CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 스토리지 엔진 변경 (MyISAM → InnoDB)
ALTER TABLE step20_sell ENGINE=InnoDB;
ALTER TABLE step21_sell_cart ENGINE=InnoDB;
```

### 7.2 데이터 검증

```sql
-- 행 수 확인
SELECT COUNT(*) FROM step20_sell;      -- 약 10,000행
SELECT COUNT(*) FROM step21_sell_cart; -- 약 500행

-- 샘플 데이터 확인
SELECT * FROM step20_sell LIMIT 5;
SELECT * FROM step21_sell_cart LIMIT 5;

-- 참조 무결성 검증
SELECT COUNT(*) FROM step20_sell
WHERE s20_partid NOT IN (SELECT s1_caid FROM step1_parts);
-- 결과: 0 (고아 레코드 없음)
```

### 7.3 마이그레이션 순서

**우선순위**: 🟡 중간 (step1_parts, step2_center 후에 수행)

```
1. step1_parts (부품) → 완료 ✅
2. step2_center (센터) → 완료 ✅
3. step20_sell (판매) → 진행 예정
4. step21_sell_cart (판매 카트) → 진행 예정
```

---

## 📋 8. 구현 체크리스트 (orders.php)

### 8.1 필수 기능

- [ ] 판매 목록 조회 (step20_sell)

  - [ ] 페이지네이션
  - [ ] 검색 (부품명, 판매 일자, 센터)
  - [ ] 정렬 (판매일, 수량, 금액)

- [ ] 부품명 표시 (step1_parts 조인)

  - [ ] s20_partid → s1_name 변환
  - [ ] 부품 카테고리 표시

- [ ] 판매 금액 표시

  - [ ] s20_unit_price (단가)
  - [ ] s20_total_price (합계)
  - [ ] 월간 합계

- [ ] CRUD 기능
  - [ ] 판매 추가
  - [ ] 판매 수정
  - [ ] 판매 삭제

### 8.2 추가 기능

- [ ] 센터별 통계

  - [ ] 월간 판매건수
  - [ ] 월간 판매금액
  - [ ] 부품별 판매순위

- [ ] 리포트
  - [ ] 월간 판매 현황
  - [ ] 센터별 비교
  - [ ] 부품별 분석

---

## 🔐 9. 보안 고려사항

### 9.1 SQL Injection 방지

**현재 (위험):**

```php
$query = "SELECT * FROM step20_sell WHERE s20_as_center = '$center'";
```

**개선 (안전):**

```php
$stmt = $pdo->prepare("SELECT * FROM step20_sell WHERE s20_as_center = ?");
$stmt->execute([$center]);
```

### 9.2 입력 검증

- s20_qty: 양의 정수만 허용
- s20_unit_price: 양의 숫자만 허용
- s20_sell_date: 유효한 날짜 형식 확인
- s20_as_center: step2_center에 존재하는 ID만 허용

---

## 📊 10. 성능 최적화

### 10.1 필수 인덱스

```sql
-- 조회 성능 향상
CREATE INDEX idx_sell_center ON step20_sell(s20_as_center);
CREATE INDEX idx_sell_date ON step20_sell(s20_sell_in_date);
CREATE INDEX idx_sell_level ON step20_sell(s20_as_level);

-- 카트 성능 향상
CREATE INDEX idx_cart_sellid ON step21_sell_cart(s21_sellid);
CREATE INDEX idx_cart_partid ON step21_sell_cart(s21_partid);
```

### 10.2 쿼리 최적화

- 월간 데이터 조회 시 DATE_FORMAT() 사용
- 센터별 통계는 GROUP BY로 집계
- JOIN은 FK 기준으로 수행

---

## 📝 11. 다음 단계

### 즉시 (이번 주)

1. **orders.php 기본 구조 작성**

   - step20_sell 목록 조회
   - 부품명 표시 (step1_parts JOIN)

2. **테이블 스타일링**

   - parts.php, products.php와 동일한 스타일 적용
   - 센터명, 부품명, 수량, 금액 컬럼 표시

3. **검색/필터링**
   - 부품명 검색
   - 판매 날짜 범위 검색

### 2주 차

4. **CRUD 기능 구현**

   - 판매 추가 (add 페이지)
   - 판매 수정 (edit 페이지)
   - 판매 삭제

5. **통계 기능**
   - 월간 판매 통계
   - 센터별 비교

---

## 🔄 12. 참고 파일

- **분석 기반**: `/as/exl/xls2.php` (실제 판매 데이터 활용)
- **구현 참고**: `/as/sell/into_cart.php`, `/as/sell/1_add_list.php` (비용 정책, 필드 매핑)
- **테이블 스키마**: `01_DATABASE_SCHEMA.md`
- **실제 DB 분석**: `05_ACTUAL_DATABASE_ANALYSIS.md`
- **관련 부품 정보**: step1_parts 테이블 (다양한 가격 필드 참조)

---

## ✅ 13. 완료 기준

- [ ] 모든 필드 정확히 매핑
- [ ] step1_parts JOIN 동작 확인
- [ ] 월간 통계 쿼리 테스트
- [ ] 참조 무결성 검증 완료
- [ ] 마이그레이션 SQL 테스트 완료

---

**상태**: ✅ 분석 완료
**다음**: orders.php 구현 시작
**마지막 업데이트**: 2025-11-03
