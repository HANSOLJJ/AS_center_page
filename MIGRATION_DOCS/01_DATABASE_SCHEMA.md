# Phase 1: 데이터베이스 스키마 & 관계도 문서

**작성일**: 2025-10-23  
**시스템**: mic4u AS (After-Sales) 관리 시스템  
**현재 DB**: MySQL (호스트: 원격 호스팅)  
**목표 DB**: MariaDB 10.x

---

## 📊 1. 데이터베이스 테이블 목록 (21개)

### 1.1 코어 테이블

#### 1. `step1_parts` - AS 부품 마스터

**용도**: 모든 AS 처리에 필요한 부품 정보 관리

| 필드명      | 타입          | 설명               | 예시         |
| ----------- | ------------- | ------------------ | ------------ |
| s1_caid     | INT PK        | 부품 ID (자동증가) | 1001         |
| s1_name     | VARCHAR(255)  | 부품명             | 컴프레셔     |
| s1_erp      | VARCHAR(50)   | ERP 코드           | CPR-001-2023 |
| s1_cost_c_1 | DECIMAL(10,2) | AS CENTER 비용     | 15000.00     |
| s1_cost_a_1 | DECIMAL(10,2) | 판매점 비용1       | 18000.00     |
| s1_cost_a_2 | DECIMAL(10,2) | 판매점 비용2       | 16000.00     |
| s1_cost_n_1 | DECIMAL(10,2) | 일반 판매 비용1    | 20000.00     |
| s1_cost_n_2 | DECIMAL(10,2) | 일반 판매 비용2    | 18000.00     |
| s1_cost_s_1 | DECIMAL(10,2) | 특별 비용          | 22000.00     |
| s1_cost_won | DECIMAL(10,2) | 원화 환율          | 1.0          |

**데이터량**: ~500-1000개  
**접근 패턴**: parts/ 모듈에서 CRUD, as_center에서 SELECT  
**마이그레이션 난이도**: ⭐ 쉬움

---

#### 2. `step2_center` - AS 센터 정보

**용도**: 6개의 AS 처리 센터 관리

| 필드명       | 타입           | 설명     | 예시          |
| ------------ | -------------- | -------- | ------------- |
| s2_center_id | VARCHAR(20) PK | 센터 ID  | center_001    |
| s2_center    | VARCHAR(100)   | 센터명   | 서울 AS센터   |
| s2_addr1     | VARCHAR(255)   | 주소1    | 서울시 강남구 |
| s2_addr2     | VARCHAR(255)   | 주소2    | 테헤란로 123  |
| s2_zipcode   | VARCHAR(10)    | 우편번호 | 06234         |
| s2_phone1    | VARCHAR(20)    | 전화1    | 02-1234-5678  |
| s2_phone2    | VARCHAR(20)    | 전화2    | 02-1234-5679  |
| s2_fax       | VARCHAR(20)    | 팩스     | 02-1234-5680  |

**데이터량**: 6개  
**접근 패턴**: center/ 모듈에서 CRUD, 로그인 시 센터 확인  
**마이그레이션 난이도**: ⭐ 쉬움

---

#### 3. `step3_member` - 센터 직원 (AS 사용자)

**용도**: 각 센터의 AS 신청 권한자 관리

| 필드명       | 타입           | 설명                     | 예시                   |
| ------------ | -------------- | ------------------------ | ---------------------- |
| s3_id        | VARCHAR(20) PK | 로그인 ID                | emp001                 |
| s3_name      | VARCHAR(50)    | 직원명                   | 김철수                 |
| s3_passwd    | VARCHAR(41)    | 비밀번호 (PASSWORD 해시) | 7f0c9a...              |
| s3_userlevel | INT            | 권한 레벨                | 1-5 (1=일반, 5=관리자) |
| s3_center_id | VARCHAR(20) FK | 센터 ID                  | center_001             |
| s3_phone     | VARCHAR(20)    | 전화번호                 | 010-1234-5678          |
| s3_email     | VARCHAR(100)   | 이메일                   | emp001@company.com     |

**데이터량**: ~50-100개  
**접근 패턴**: 로그인 검증, member/ 모듈 CRUD  
**마이그레이션 난이도**: ⭐⭐ 중간 (비밀번호 해시 방식 변경 필요)

---

#### 4. `step6_order` - AS 주문

**용도**: AS 센터로부터 본사에 올라온 주문 관리

| 필드명          | 타입          | 설명                                      |
| --------------- | ------------- | ----------------------------------------- |
| s6_order_id     | INT PK        | 주문 ID                                   |
| s6_as_id        | INT FK        | AS 신청 ID (step13_as)                    |
| s6_order_date   | DATETIME      | 주문 일시                                 |
| s6_order_status | VARCHAR(20)   | 상태 (pending/approved/shipped/completed) |
| s6_total_cost   | DECIMAL(12,2) | 총액                                      |

**데이터량**: ~1000-5000개/월  
**접근 패턴**: order/ 모듈에서 조회/수정  
**마이그레이션 난이도**: ⭐⭐ 중간

---

### 1.2 AS 관련 테이블

#### 5. `step13_as` - AS 신청 건

**용도**: 핵심 AS 신청 정보 저장 (가장 중요)

| 필드명            | 타입           | 설명                     | 예시                |
| ----------------- | -------------- | ------------------------ | ------------------- |
| s13_asid          | INT PK         | AS 신청 ID               | 20250001            |
| s13_as_center     | VARCHAR(20) FK | 센터 ID                  | center_001          |
| s13_as_in_date    | DATE           | 입고 일자                | 2025-10-23          |
| s13_as_in_how     | VARCHAR(100)   | 입고 방식                | 직접배송 / 택배     |
| s13_as_in_no      | VARCHAR(50)    | 입고 번호                | INV-20250001        |
| s13_meid          | VARCHAR(20) FK | 고객사 ID (step3_member) | emp001              |
| s13_dex_no        | VARCHAR(50)    | 배송 번호                | 012345678901        |
| s13_sms1          | VARCHAR(255)   | SMS 발송 정보1           | [발송 여부 / 시간]  |
| s13_sms2          | VARCHAR(255)   | SMS 발송 정보2           | [발송 여부 / 시간]  |
| s13_bank_check    | INT            | 뱅크 확인 여부           | 0/1                 |
| s13_tax_code      | VARCHAR(50) FK | 세금 코드 (step10_tax)   | TAX-2025-001        |
| s13_dex_send      | INT            | 배송 발송 여부           | 0/1                 |
| s13_dex_send_name | VARCHAR(50)    | 배송 업체                | CJ / GS / 로젠      |
| s13_as_out_date   | DATE           | 출고 날짜                | 2025-10-25          |
| s13_status        | VARCHAR(20)    | 상태                     | 신청/진행/완료/취소 |
| s13_reg_date      | DATETIME       | 등록 일시                | 2025-10-23 10:30:00 |

**데이터량**: ~10000-50000개/년  
**접근 패턴**: as_center/ 모듈에서 집중 접근, 가장 빈번한 CRUD  
**마이그레이션 난이도**: ⭐⭐⭐ 어려움 (복잡한 관계도)

---

#### 6. `step14_as_item` - AS 신청 제품

**용도**: 각 AS 신청에 포함된 제품 목록

| 필드명         | 타입         | 설명                   |
| -------------- | ------------ | ---------------------- |
| s14_itemid     | INT PK       | 제품 ID                |
| s14_asid       | INT FK       | AS 신청 ID (step13_as) |
| s14_item_name  | VARCHAR(255) | 제품명                 |
| s14_item_model | VARCHAR(100) | 제품 모델              |
| s14_serial_no  | VARCHAR(100) | 시리얼 번호            |

**데이터량**: ~50000-100000개 (AS당 평균 2-3개)  
**접근 패턴**: as_center에서 빈번한 INSERT/SELECT  
**마이그레이션 난이도**: ⭐⭐ 중간

---

#### 7. `step16_as_poor` - AS 부실 사유

**용도**: AS 신청이 거절되거나 불완전한 이유 관리

| 필드명       | 타입     | 설명       |
| ------------ | -------- | ---------- |
| s16_poorid   | INT PK   | 부실 ID    |
| s16_asid     | INT FK   | AS 신청 ID |
| s16_reason   | TEXT     | 거절 사유  |
| s16_reg_date | DATETIME | 등록 일시  |

**데이터량**: ~1000-5000개/년  
**접근 패턴**: 조회 위주  
**마이그레이션 난이도**: ⭐ 쉬움

---

#### 8. `step19_as_result` - AS 결과

**용도**: AS 처리 결과 저장 (가장 중요한 리포트 테이블)

| 필드명            | 타입           | 설명                           |
| ----------------- | -------------- | ------------------------------ |
| s19_resultid      | INT PK         | 결과 ID                        |
| s19_asid          | INT FK         | AS 신청 ID                     |
| s19_as_center     | VARCHAR(20) FK | 센터 ID                        |
| s19_result_date   | DATE           | 결과 처리 일자                 |
| s19_result_status | VARCHAR(20)    | 결과 상태 (완료/거절/부분처리) |
| s19_result_detail | TEXT           | 결과 상세 설명                 |
| s19_total_cost    | DECIMAL(12,2)  | 총 비용                        |
| s19_parts_cost    | DECIMAL(12,2)  | 부품 비용                      |
| s19_labor_cost    | DECIMAL(12,2)  | 기술비                         |
| s19_fee           | DECIMAL(12,2)  | 수수료                         |

**데이터량**: ~5000-20000개/년  
**접근 패턴**: 보고서/통계 조회 위주  
**마이그레이션 난이도**: ⭐⭐ 중간

---

### 1.3 부품 관련 테이블

#### 9. `step5_category` - 부품 카테고리

**용도**: 부품 분류

| 필드명  | 타입         | 설명          |
| ------- | ------------ | ------------- |
| s5_caid | INT PK       | 카테고리 ID   |
| s5_name | VARCHAR(100) | 카테고리명    |
| s5_code | VARCHAR(20)  | 카테고리 코드 |

**데이터량**: ~50-100개  
**마이그레이션 난이도**: ⭐ 쉬움

---

#### 10. `step7_center_parts` - 센터별 부품 재고

**용도**: 각 센터의 부품 재고 현황

| 필드명         | 타입           | 설명                  |
| -------------- | -------------- | --------------------- |
| s7_id          | INT PK         | ID                    |
| s7_center_id   | VARCHAR(20) FK | 센터 ID               |
| s7_part_id     | INT FK         | 부품 ID (step1_parts) |
| s7_quantity    | INT            | 재고 수량             |
| s7_last_update | DATETIME       | 마지막 수정 일시      |

**데이터량**: ~5000개  
**마이그레이션 난이도**: ⭐⭐ 중간

---

### 1.4 주문/배송 관련 테이블

#### 11. `step4_cart` - 장바구니

**용도**: 임시 주문 데이터 저장

| 필드명    | 타입          | 설명        |
| --------- | ------------- | ----------- |
| s4_cartid | INT PK        | 장바구니 ID |
| s4_asid   | INT FK        | AS 신청 ID  |
| s4_partid | INT FK        | 부품 ID     |
| s4_qty    | INT           | 수량        |
| s4_price  | DECIMAL(10,2) | 가격        |

**데이터량**: ~1000개 (임시 데이터이므로 자주 정리됨)  
**마이그레이션 난이도**: ⭐ 쉬움

---

#### 12. `step8_sendbox` - 배송 정보

**용도**: 배송 기록 관리

| 필드명         | 타입        | 설명                |
| -------------- | ----------- | ------------------- |
| s8_sendid      | INT PK      | 배송 ID             |
| s8_asid        | INT FK      | AS 신청 ID          |
| s8_tracking_no | VARCHAR(50) | 추적 번호           |
| s8_carrier     | VARCHAR(50) | 배송사 (CJ/GS/로젠) |
| s8_send_date   | DATETIME    | 배송 일시           |
| s8_status      | VARCHAR(20) | 배송 상태           |

**데이터량**: ~10000개/년  
**마이그레이션 난이도**: ⭐⭐ 중간

---

#### 13. `step9_out` - 출고 관리

**용도**: 부품 출고 기록

| 필드명      | 타입     | 설명       |
| ----------- | -------- | ---------- |
| s9_outid    | INT PK   | 출고 ID    |
| s9_asid     | INT FK   | AS 신청 ID |
| s9_partid   | INT FK   | 부품 ID    |
| s9_qty      | INT      | 출고 수량  |
| s9_out_date | DATETIME | 출고 일시  |

**데이터량**: ~50000개  
**마이그레이션 난이도**: ⭐⭐ 중간

---

### 1.5 회계/세금 관련 테이블

#### 14. `step10_tax` - 세금 청구

**용도**: 세금 계산 및 청구서 관리

| 필드명             | 타입          | 설명           |
| ------------------ | ------------- | -------------- |
| s10_taxid          | INT PK        | 세금 청구 ID   |
| s10_asid           | INT FK        | AS 신청 ID     |
| s10_tax_code       | VARCHAR(50)   | 세금 코드      |
| s10_tax_date       | DATE          | 세금 계산 일자 |
| s10_taxable_amount | DECIMAL(12,2) | 과세 금액      |
| s10_tax_amount     | DECIMAL(12,2) | 세금액         |
| s10_total          | DECIMAL(12,2) | 합계           |

**데이터량**: ~5000개/년  
**마이그레이션 난이도**: ⭐⭐ 중간

---

### 1.6 SMS/알림 관련 테이블

#### 15. `step12_sms_sample` - SMS 템플릿

**용도**: SMS 메시지 템플릿 관리

| 필드명            | 타입         | 설명      |
| ----------------- | ------------ | --------- |
| s12_smsid         | INT PK       | SMS ID    |
| s12_template_name | VARCHAR(100) | 템플릿명  |
| s12_content       | TEXT         | SMS 내용  |
| s12_created_date  | DATETIME     | 생성 일자 |

**데이터량**: ~50개  
**마이그레이션 난이도**: ⭐ 쉬움

---

### 1.7 판매 관련 테이블

#### 16. `step20_sell` - 판매 정보

**용도**: 부품 판매 거래 관리

| 필드명          | 타입          | 설명      |
| --------------- | ------------- | --------- |
| s20_sellid      | INT PK        | 판매 ID   |
| s20_partid      | INT FK        | 부품 ID   |
| s20_qty         | INT           | 판매 수량 |
| s20_unit_price  | DECIMAL(10,2) | 단가      |
| s20_total_price | DECIMAL(12,2) | 합계      |
| s20_sell_date   | DATE          | 판매 일자 |

**데이터량**: ~10000개/년  
**마이그레이션 난이도**: ⭐⭐ 중간

---

#### 17. `step21_sell_cart` - 판매 장바구니

**용도**: 판매 임시 데이터

| 필드명     | 타입   | 설명        |
| ---------- | ------ | ----------- |
| s21_cartid | INT PK | 장바구니 ID |
| s21_sellid | INT FK | 판매 ID     |
| s21_partid | INT FK | 부품 ID     |
| s21_qty    | INT    | 수량        |

**데이터량**: ~500개  
**마이그레이션 난이도**: ⭐ 쉬움

---

### 1.8 관리자 테이블

#### 18. `2010_admin_member` - 관리자 계정

**용도**: 관리자 로그인

| 필드명       | 타입           | 설명                     |
| ------------ | -------------- | ------------------------ |
| admin_id     | VARCHAR(20) PK | 관리자 ID                |
| admin_name   | VARCHAR(50)    | 관리자명                 |
| admin_passwd | VARCHAR(41)    | 비밀번호 (PASSWORD 해시) |
| admin_level  | INT            | 권한 레벨                |

**데이터량**: ~20개  
**마이그레이션 난이도**: ⭐ 쉬움

---

#### 19. `2010_member` - 회원 정보

**용도**: 일반 회원 관리 (거의 사용 안 함)

**데이터량**: ~100개  
**마이그레이션 난이도**: ⭐ 쉬움

---

### 1.9 AS 모델/수리 관련 테이블

#### 20. `step15_as_model` - AS 신청 모델

**용도**: AS 대상 제품 모델 정보

| 필드명         | 타입         | 설명       |
| -------------- | ------------ | ---------- |
| s15_modelid    | INT PK       | 모델 ID    |
| s15_asid       | INT FK       | AS 신청 ID |
| s15_model_name | VARCHAR(100) | 모델명     |

**데이터량**: ~30000개  
**마이그레이션 난이도**: ⭐⭐ 중간

---

#### 21. `step17_as_item_cure` & `step18_as_cure_cart` - 수리 항목/장바구니

**용도**: 수리 항목 관리

**데이터량**: ~50000개  
**마이그레이션 난이도**: ⭐⭐ 중간

---

## 📈 2. 테이블 관계도 (ER Diagram)

```
┌─────────────────────────────────────────────────────────────┐
│                    관리자 시스템                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  2010_admin_member ──┐                                       │
│                      │                                       │
│  2010_member ────────┼──── step3_member (센터 직원)         │
│                      │           │                           │
│                      └───────────┼───────────────────────┐  │
│                                  │                       │  │
│           step2_center ◄─────────┘                       │  │
│           (AS 센터)                                      │  │
│                │                                         │  │
│                └────────────┬────────────────────────┐   │  │
│                             │                        │   │  │
│          ┌──────────────────▼────────┐               │   │  │
│          │   step13_as                │               │   │  │
│          │  (AS 신청 건 - 핵심)      │               │   │  │
│          │  ├─ s13_asid (PK)         │               │   │  │
│          │  ├─ s13_as_center (FK) ◄──┼───────────────┼───┼──┘
│          │  ├─ s13_meid (FK) ◄───────┼───────────────┘   │
│          │  ├─ s13_tax_code (FK) ────┼──┐                │
│          │  └─ s13_dex_no            │  │                │
│          └────┬─────────┬─────────────┘  │                │
│               │         │                │                │
│       ┌───────▼──┐  ┌───▼─────────┐  ┌──▼──────────┐     │
│       │ step14_as_item  │ step19_as_result │ step10_tax  │     │
│       │ (제품)     │  (결과)       │ (세금)        │     │
│       └────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              부품 & 재고 시스템                         │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │                                                        │ │
│  │  step1_parts (부품) ◄──┐                              │ │
│  │       │                │                              │ │
│  │       │            step5_category (카테고리)          │ │
│  │       │                                               │ │
│  │  ┌────▼────────┬──────────┬──────────┐               │ │
│  │  │             │          │          │               │ │
│  │ step7_center_parts  step4_cart  step9_out            │ │
│  │ (센터 재고)   (장바구니)  (출고)                     │ │
│  │                                                       │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              배송 & 판매 시스템                         │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │                                                        │ │
│  │ step8_sendbox ◄── step13_as                           │ │
│  │ (배송)                                                 │ │
│  │                                                        │ │
│  │ step20_sell ◄──── step1_parts                         │ │
│  │ (판매)                                                 │ │
│  │      │                                                 │ │
│  │      └──► step21_sell_cart (판매 장바구니)            │ │
│  │                                                        │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔑 3. Primary Key & Foreign Key 정의

### 필수 인덱스 목록

```sql
-- step13_as (핵심 테이블)
CREATE INDEX idx_as_center ON step13_as(s13_as_center);
CREATE INDEX idx_as_meid ON step13_as(s13_meid);
CREATE INDEX idx_as_tax_code ON step13_as(s13_tax_code);
CREATE INDEX idx_as_date ON step13_as(s13_as_in_date);
CREATE INDEX idx_as_status ON step13_as(s13_status);

-- step14_as_item
CREATE INDEX idx_item_asid ON step14_as_item(s14_asid);

-- step19_as_result
CREATE INDEX idx_result_asid ON step19_as_result(s19_asid);
CREATE INDEX idx_result_center ON step19_as_result(s19_as_center);
CREATE INDEX idx_result_date ON step19_as_result(s19_result_date);

-- step3_member
CREATE INDEX idx_member_center ON step3_member(s3_center_id);

-- step1_parts
CREATE INDEX idx_parts_name ON step1_parts(s1_name);
CREATE INDEX idx_parts_erp ON step1_parts(s1_erp);

-- step7_center_parts
CREATE INDEX idx_center_parts_center ON step7_center_parts(s7_center_id);
CREATE INDEX idx_center_parts_part ON step7_center_parts(s7_part_id);
```

---

## 📊 4. 데이터 통계 (예상치)

| 테이블           | 월간 증가량 | 총 예상 행 수 | 인덱스 크기 |
| ---------------- | ----------- | ------------- | ----------- |
| step13_as        | 1,000~2,000 | 50,000        | 5MB         |
| step14_as_item   | 2,000~3,000 | 150,000       | 10MB        |
| step19_as_result | 1,000~2,000 | 50,000        | 5MB         |
| step1_parts      | 50          | 1,000         | 500KB       |
| step2_center     | 0           | 6             | 1KB         |
| step3_member     | 50          | 200           | 50KB        |
| step6_order      | 500~1,000   | 20,000        | 2MB         |
| **합계**         |             | **500,000+**  | **~50MB**   |

---

## 🔄 5. 마이그레이션 전 체크리스트

- [ ] 현재 MySQL 데이터베이스 백업 생성
- [ ] 모든 테이블의 character set 확인 (EUC-KR → UTF-8MB4)
- [ ] 비밀번호 필드 인코딩 방식 확인 (MySQL PASSWORD() 해시)
- [ ] 외래키(Foreign Key) 제약 조건 문서화
- [ ] 각 테이블의 인덱스 현황 파악
- [ ] 스토리지 엔진 확인 (MyISAM → InnoDB 변경 필요)
- [ ] 데이터 정합성 검증 (고아 레코드 확인)

---

## 📝 6. 인코딩 변환 전략

### 현재

- **DB Charset**: euc-kr
- **Table Charset**: euc-kr
- **Connection**: SET NAMES euc_kr

### 목표 (MariaDB)

- **DB Charset**: utf8mb4
- **Table Charset**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Connection**: SET NAMES utf8mb4

### 마이그레이션 SQL

```sql
-- 단계별 변환
ALTER DATABASE mic4u41 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE step13_as CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step14_as_item CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ... (모든 테이블)
```

---

## 🔐 7. 비밀번호 해시 변환 전략

### 현재

- MySQL의 `PASSWORD()` 함수 사용
- 형식: 41자의 16진수 문자열
- 예: `7f0c9a27a8c4a8f8b5c7e9d0f1a2b3c4d5e6f7`

### 목표

- PHP의 `password_hash()` 사용 (bcrypt)
- 형식: `$2y$10$...` (60자)

### 마이그레이션 방안

```php
// 1단계: 직접 변환 불가능 (원본 비밀번호 필요)
// 2단계: 비밀번호 초기화 후 재설정 요청
// 3단계: 임시 비밀번호 생성 및 메일 발송
```

---

## 📋 8. 마이그레이션 순서

1. **신규 MariaDB 데이터베이스 생성**

   ```sql
   CREATE DATABASE mic4u41_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **테이블 마이그레이션** (의존도 순서)

   - step1_parts (부품)
   - step2_center (센터)
   - step3_member (직원)
   - step5_category (카테고리)
   - step12_sms_sample (SMS)
   - step6_order (주문)
   - step13_as (AS 신청) ⭐ 중요
   - step14_as_item (AS 제품)
   - step15_as_model (AS 모델)
   - step16_as_poor (AS 부실)
   - step17_as_item_cure (수리)
   - step18_as_cure_cart (수리 카트)
   - step19_as_result (AS 결과) ⭐ 중요
   - step4_cart (장바구니)
   - step7_center_parts (센터 부품)
   - step8_sendbox (배송)
   - step9_out (출고)
   - step10_tax (세금)
   - step20_sell (판매)
   - step21_sell_cart (판매 카트)
   - 2010_admin_member (관리자)
   - 2010_member (회원)

3. **데이터 검증**

   - 행 수 비교
   - 주요 필드 값 샘플 확인
   - 참조 무결성 검증

4. **인덱스 생성**

5. **비밀번호 초기화 및 재설정**

---

이 문서는 마이그레이션 진행 중 지속적으로 업데이트됩니다.
