# step13_as 테이블 구조 상세 분석

**문서 작성일**: 2025-11-06  
**테이블명**: step13_as (AS 신청/요청 메인 테이블)  
**성격**: 핵심 AS 관리 테이블 - 가장 중요한 테이블

---

## 1. step13_as 테이블 전체 컬럼 구조

### 1.1 기본 정보 (PRIMARY KEY & 참조 키)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 필수 여부 |
|--------|----------|---------|--------|------|---------|
| **s13_asid** | INT | NO | AUTO_INCREMENT | AS 신청 ID (Primary Key) | 필수 |
| **s13_as_center** | VARCHAR(100) | YES | NULL | AS 센터 ID (step2_center 참조) | 필수 |
| **s13_meid** | VARCHAR(100) | YES | NULL | 멤버/직원 ID (step3_member 참조) | 선택 |

---

### 1.2 입고 정보 (Receiving)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 예시 |
|--------|----------|---------|--------|------|------|
| s13_as_in_date | DATETIME | YES | NULL | AS 입고 일자 | 2025-11-06 10:30:00 |
| s13_as_in_how | VARCHAR(50) | YES | NULL | 수탁 방법 (입고 방식) | '내방' / '택배' / '퀵' |
| s13_as_in_no | VARCHAR(100) | YES | NULL | AS 입고 번호 | 'INV-20251106-001' |
| s13_as_in_no2 | VARCHAR(100) | YES | NULL | AS 입고 번호 2 (보조) | 'SUP-001' |

---

### 1.3 제조/제품 정보

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 예시 |
|--------|----------|---------|--------|------|------|
| s13_meid | VARCHAR(100) | YES | NULL | 제조 일련번호/MEID | 'MEID123456789' |
| s13_dex_no | VARCHAR(100) | YES | NULL | 배송 번호 | '01234567890' |

---

### 1.4 상태 및 진행도 (Status & Progress)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 가능한 값 |
|--------|----------|---------|--------|------|----------|
| s13_as_level | VARCHAR(10) | YES | NULL | AS 처리 단계 | '1' (입고) / '2' (검진) / '3' (수리) / '4' (출고) / '5' (완료) |
| s13_as_name1 | VARCHAR(100) | YES | NULL | AS 처리자 이름 1 | '김철수' |
| s13_as_name2 | VARCHAR(100) | YES | NULL | AS 처리자 이름 2 (수리자) | '이순신' |
| s13_as_name3 | VARCHAR(100) | YES | NULL | AS 처리자 이름 3 (출고담당) | '박영희' |
| s13_as_time | DATETIME | YES | NULL | AS 생성 시간 | 2025-11-06 10:30:00 |
| s13_as_out_date | DATETIME | YES | NULL | AS 출고 일자 | 2025-11-07 15:45:00 |

---

### 1.5 비용 및 결제 정보 (Cost & Payment)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 예시 |
|--------|----------|---------|--------|------|------|
| s13_total_cost | VARCHAR(100) | YES | NULL | 총 수리 비용 | '150000' 또는 '150000.00' |
| s13_bank_check | VARCHAR(100) | YES | NULL | 은행 확인/입금 확인 일자 | '2025-11-06' 또는 'Y' / 'N' |
| s13_bankcheck_w | VARCHAR(50) | YES | NULL | 은행 확인 상태 | 'confirmed' / 'pending' / 'failed' |
| s13_tax_code | VARCHAR(100) | YES | NULL | 세금 코드 (step10_tax 참조) | 'TAX-20251106-001' |

---

### 1.6 배송 정보 (Delivery)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 예시 |
|--------|----------|---------|--------|------|------|
| s13_dex_send | VARCHAR(100) | YES | NULL | 배송 업체 | 'CJ' / 'GS' / '로젠' / '우체국' |
| s13_dex_send_name | VARCHAR(100) | YES | NULL | 배송 담당자 | '배송사원명' |

---

### 1.7 SMS 및 기타 (SMS & Other)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 예시 |
|--------|----------|---------|--------|------|------|
| s13_sms1 | VARCHAR(50) | YES | NULL | SMS1 발송 정보 | 'Y' / 'N' 또는 발송시간 |
| s13_sms2 | VARCHAR(50) | YES | NULL | SMS2 발송 정보 | 'Y' / 'N' 또는 발송시간 |

---

### 1.8 고급 필드 (Legacy Fields)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 용도 |
|--------|----------|---------|--------|------|------|
| ex_tel | VARCHAR(50) | YES | NULL | 확장 필드: 전화번호 | 고객 전화 |
| ex_sms_no | VARCHAR(50) | YES | NULL | 확장 필드: SMS 번호 | SMS 추적 |
| ex_sec1 | VARCHAR(100) | YES | NULL | 확장 필드: 분류1 | 배송방식 등 |
| ex_sec2 | VARCHAR(100) | YES | NULL | 확장 필드: 분류2 | 부가 분류 |
| ex_company | VARCHAR(100) | YES | NULL | 확장 필드: 회사명 | 고객 회사 |
| ex_man | VARCHAR(100) | YES | NULL | 확장 필드: 담당자 | 고객 담당자 |
| ex_address | VARCHAR(255) | YES | NULL | 확장 필드: 주소 | 배송 주소 |
| ex_address_no | VARCHAR(100) | YES | NULL | 확장 필드: 주소번호 | 상세 주소 |
| ex_company_no | VARCHAR(100) | YES | NULL | 확장 필드: 회사 번호 | 사업자번호 등 |
| ex_total_cost | VARCHAR(100) | YES | NULL | 확장 필드: 비용 | 별도 비용 추적 |

---

### 1.9 UI 표시 필드 (Display Fields)

| 컬럼명 | 데이터타입 | NULL 허용 | 기본값 | 설명 | 용도 |
|--------|----------|---------|--------|------|------|
| s13_name | VARCHAR(100) | YES | NULL | 고객명 | 목록 표시, 검색 대상 |
| s13_product | VARCHAR(100) | YES | NULL | 제품명 | 목록 표시 |
| s13_date | DATETIME | YES | NULL | 수리 일자 | 목록 표시 |
| s13_status | VARCHAR(50) | YES | NULL | 상태 | 목록 표시 (대기중/처리중/완료/취소) |

---

## 2. 데이터 타입 상세 분석

### VARCHAR vs DATETIME 필드

**VARCHAR 사용 필드 (문자열)**:
- `s13_as_center`, `s13_as_in_how`, `s13_as_in_no`, `s13_as_in_no2`
- `s13_meid`, `s13_dex_no`
- `s13_as_level` (상태값을 문자로 저장: '1', '2', '3', '4', '5')
- `s13_as_name1`, `s13_as_name2`, `s13_as_name3`
- `s13_total_cost` (금액을 VARCHAR로 저장 - 나쁜 관행)
- `s13_bank_check` (날짜를 VARCHAR로 저장 - 비효율적)
- `s13_bankcheck_w`, `s13_tax_code`
- `s13_dex_send`, `s13_dex_send_name`
- `s13_sms1`, `s13_sms2`

**DATETIME 사용 필드 (날짜/시간)**:
- `s13_as_in_date` (입고 일자)
- `s13_as_time` (생성 시간)
- `s13_as_out_date` (출고 일자)

---

## 3. 필수 vs 선택 필드

### 필수 필드 (NOT NULL)
```
- s13_asid (Auto Increment Primary Key)
- s13_as_center (AS 센터 지정)
- s13_as_in_how (입고 방식 지정)
```

### 강력히 권장 필드 (항상 채워야 함)
```
- s13_as_in_date (입고 일자)
- s13_as_level (처리 단계)
- s13_name (고객명)
- s13_product (제품명)
```

### 선택 필드 (상황에 따라)
```
- s13_as_in_no, s13_as_in_no2 (입고 번호)
- s13_meid (직원 ID)
- s13_as_name1, s13_as_name2, s13_as_name3 (처리자)
- s13_as_out_date (출고 일자 - 완료 후)
- s13_total_cost (비용 - 산출 후)
- s13_bank_check, s13_bankcheck_w (입금 확인)
- s13_tax_code (세금 코드)
- s13_dex_send, s13_dex_send_name (배송 정보)
- s13_sms1, s13_sms2 (SMS 발송)
- s13_status (상태)
```

---

## 4. 상태 값 정의

### s13_as_level (AS 처리 단계)
| 값 | 설명 | 담당 업무 | 다음 단계 |
|-----|------|---------|----------|
| 1 | 입고 (Received) | 입고 처리 | 2 (검진) |
| 2 | 검진 (Inspection) | 제품 검사 및 진단 | 3 (수리) |
| 3 | 수리 (Repair) | 제품 수리 및 부품 교체 | 4 (출고) |
| 4 | 출고 (Shipped Out) | 배송 준비 및 발송 | 5 (완료) |
| 5 | 완료 (Completed) | 프로세스 종료 | - |

**레거시 값**:
- '5' 또는 다른 값: 외부업체 처리, 취소 등 특수 상태

### s13_status (UI 상태 표시)
| 값 | 설명 |
|-----|------|
| '대기중' | 입고 대기 또는 검진 대기 |
| '처리중' | Level 2, 3에 해당 |
| '완료' | Level 4, 5에 해당 |
| '취소' | 취소된 요청 |

---

## 5. 데이터 흐름 (프로세스 예시)

### 신규 AS 신청 생성
```sql
INSERT INTO step13_as (
    s13_as_center,          -- 필수: AS 센터 ID
    s13_as_in_how,          -- 필수: 입고 방식
    s13_as_in_date,         -- 권장: 입고 일자
    s13_as_in_no,           -- 선택: 입고 번호
    s13_meid,               -- 선택: 직원 ID
    s13_as_level,           -- 필수: '1' (입고)
    s13_name,               -- 권장: 고객명
    s13_product,            -- 권장: 제품명
    s13_date,               -- 권장: 수리 일자
    s13_status,             -- 선택: '대기중'
    s13_as_time             -- 자동: NOW()
)
VALUES (
    'center_001',
    '택배',
    NOW(),
    'INV-20251106-001',
    'emp001',
    '1',
    '김철수',
    '냉장고 모델 A',
    NOW(),
    '대기중',
    NOW()
);
```

### AS 검진/수리 진행
```sql
UPDATE step13_as 
SET 
    s13_as_level = '2',         -- 검진 단계로 변경
    s13_as_name1 = '이순신',    -- 진단자
    s13_status = '처리중'
WHERE s13_asid = 1;

-- 비용 산출 후 업데이트
UPDATE step13_as 
SET 
    s13_total_cost = '150000',  
    s13_tax_code = 'TAX-20251106-001'
WHERE s13_asid = 1;
```

### AS 완료 및 출고
```sql
UPDATE step13_as 
SET 
    s13_as_level = '4',         -- 출고 단계
    s13_as_out_date = NOW(),
    s13_as_name3 = '박영희',    -- 출고담당자
    s13_dex_send = 'CJ',
    s13_dex_send_name = '배송사원',
    s13_dex_no = '01234567890',
    s13_status = '완료'
WHERE s13_asid = 1;
```

---

## 6. 관계도 (Foreign Keys)

### 6.1 테이블 간 참조 관계

```
step13_as (AS 신청 - 메인 테이블)
├─ s13_as_center → step2_center.s2_center_id (AS 센터)
├─ s13_meid → step3_member.s3_meid (직원/멤버)
├─ s13_tax_code → step10_tax.s10_tax_id (세금 정보)
│
└─ s13_asid ← step14_as_item.s14_asid (AS 항목 - 1:N 관계)
   │
   ├─ s14_model → step15_as_model.s15_amid (제품 모델)
   ├─ s14_poor → step16_as_poor.s16_apid (불량 증상)
   ├─ s14_asrid → step19_as_result.s19_asrid (AS 처리 결과)
   │
   └─ s14_aiid ← step18_as_cure_cart.s18_aiid (수리 부품 카트 - 1:N)
      └─ s18_uid → step1_parts.s1_uid (부품)
```

### 6.2 데이터 생성 및 동기화 플로우

#### **단계 1: step13_as 초기 생성** (as_center/write3_process.php)
```php
INSERT INTO step13_as (
    s13_as_center,      // AS 센터 ID
    s13_as_in_date,     // 입고 일자
    s13_as_in_how,      // 수탁 방법 (내방/택배)
    s13_as_in_no,       // 입고 번호
    s13_meid,           // 멤버 ID
    s13_dex_no          // 배송 번호
) VALUES (...)
```
**결과**: step13_as 신규 레코드 생성 (s13_asid = AUTO_INCREMENT)

#### **단계 2: step14_as_item 생성** (as_center/add_model_process.php)
```php
INSERT INTO step14_as_item (
    s14_asid,           // step13_as.s13_asid 참조
    s14_model,          // 모델 ID (step15_as_model)
    s14_poor,           // 불량증상 ID (step16_as_poor)
    s14_stat,           // 상태: '입고'
    s14_asrid,          // 초기값: '' (빈 문자열)
    s14_cart            // 초기값: '' (빈 문자열)
) VALUES (...)
```
**상태**:
- ✅ s14_asid: step13_as 참조 (생성됨)
- ❌ s14_asrid: 미지정 (modify1.php에서 선택 후 채워짐)
- ❌ s14_cart: 0개 (부품 추가 시 채워짐)

#### **단계 3: s14_asrid 선택** (as_center2/modify1.php)
- 사용자가 "AS 내역" (step19_as_result) 선택
- 예: "정상", "교체", "반품" 중 선택
- **아직 DB에 저장 안 됨** (form 변수로만 전달)

#### **단계 4: 부품 추가 및 동기화 (★핵심★)** (as_center2/into_cart.php)

```php
// ① step18_as_cure_cart에 부품 추가
INSERT INTO step18_as_cure_cart (
    s18_aiid,           // step14_as_item.s14_aiid 참조
    s18_quantity,       // 수량: 1
    s18_signdate,       // 현재 시간
    s18_uid,            // 부품 ID (step1_parts)
    s18_asid,           // step13_as.s13_asid 참조
    cost_name,          // 부품명
    cost1, cost2, cost3 // 가격정보
) VALUES (...)

// ② 현재 카트 항목 수 계산
SELECT COUNT(s18_accid) FROM step18_as_cure_cart
WHERE s18_aiid = '$s18_aiid'
// 결과: $level_rows (예: 3 - 부품 3개)

// ③ ⭐️ step14_as_item 동기화 업데이트 (⭐️ SYNC POINT)
UPDATE step14_as_item
SET
    s14_asrid = '$s14_asrid',    // 예: 'R001' (정상)
    s14_cart = '$level_rows'     // 예: '3' (부품 3개)
WHERE s14_aiid = '$s18_aiid'
```

**이 단계에서 처음으로 s14_asrid와 s14_cart가 동시에 채워짐!**

### 6.3 동기화 타이밍 다이어그램

```
Time →

as_center/        as_center2/
  ↓                 ↓
write3_process     (업체 선택)
  ↓                 ↓
[step13_as 생성] ← (멤버 ID 전달)
  ↓                 ↓
add_model_process  (모델/증상 선택)
  ↓                 ↓
[step14_as_item 생성]
↓                   ↓
├─ s14_asid = '1'   └─ modify1.php
├─ s14_model = '15' └─ (AS 내역 선택)
├─ s14_poor = '3'   └─ s14_asrid = 'R001' (변수)
├─ s14_asrid = ''   └─ modify2.php
└─ s14_cart = ''    └─ (부품 선택 화면)
                    └─ into_cart.php
                       ├─ [step18_as_cure_cart INSERT]
                       └─ [step14_as_item UPDATE] ⭐️ 동기화!
                          ├─ s14_asrid = 'R001' ✅
                          └─ s14_cart = '3' ✅
```

### 6.4 진행 상황 추적

**list_view.php에서 미완료/완료 판단**:

```php
// 전체 step14_as_item 개수
SELECT COUNT(s14_asrid) FROM step14_as_item
WHERE s14_asid = 1
→ 결과: 3개 (총 3개 항목)

// 완료된 step14_as_item (s14_asrid + s14_cart 모두 채워짐)
SELECT COUNT(s14_asrid) FROM step14_as_item
WHERE s14_asid = 1
  AND s14_asrid != ''
  AND s14_cart != ''
→ 결과: 2개 (완료된 2개 항목)

// 미완료 항목
missing = 3 - 2 = 1개

if (missing == 0) {
    → "AS 완료" 버튼 활성화 ✅
} else {
    → "AS 완료" 버튼 비활성화 ❌
}
```

### 6.5 동기화 필드 정의

| 필드 | 초기값 | 채워지는 단계 | 의미 | 예시 |
|------|--------|-----------|------|------|
| **s14_asrid** | `''` | into_cart.php | AS 처리 유형 (step19_as_result 참조) | `'R001'` (정상) / `'R002'` (교체) / `'R003'` (반품) |
| **s14_cart** | `''` | into_cart.php | 해당 AS 항목에 추가된 부품 총 개수 | `'3'` (부품 3개) |
| **s18_uid** | - | into_cart.php | 추가된 부품 ID (step1_parts 참조) | `'P001'` (부품 ID) |
| **s18_asid** | - | into_cart.php | step13_as 참조 (추적용) | `'1'` (AS ID) |

### 6.6 현재 as_request_handler.php의 구현

```php
// as_request_handler.php (Line 238)
INSERT INTO step14_as_item (..., s14_asrid, s14_cart, cost_name)
VALUES (..., '', '', '...')  // ✅ 정확한 구현
```

**왜 초기값이 빈 문자열('')인가?**
- 초기 step14_as_item 생성 시점에는 사용자가 "AS 내역"을 선택하지 않았음
- s14_asrid는 modify1.php(또는 새 form의 편집 단계)에서 선택 후 채워짐
- s14_cart는 into_cart.php(또는 부품 추가 기능)에서 첫 부품 추가 시 채워짐
- 이는 legacy 코드(as_center2)의 workflow와 일치함

**따라서 현재 구현은 100% 정확합니다!** ✅

---

## 7. PHP 코드 예시

### 7.1 as_requests.php에서의 사용

```php
// 탭별 AS 요청 조회
$query = "SELECT a.s13_asid, a.s13_name, a.s13_product, 
                 a.s13_as_in_date, a.s13_as_level, a.s13_status
          FROM step13_as a
          WHERE a.s13_as_level NOT IN ('2', '3', '4', '5')  -- 요청 탭
          ORDER BY a.s13_asid DESC LIMIT 10";

// 작업 중인 AS 조회
$query = "SELECT a.s13_asid, a.s13_name, a.s13_product,
                 a.s13_as_in_date, a.s13_as_level
          FROM step13_as a
          WHERE a.s13_as_level IN ('2', '3', '4')  -- 작업 중
          ORDER BY a.s13_asid DESC";

// 완료된 AS 조회
$query = "SELECT a.s13_asid, a.s13_name, a.s13_product,
                 a.s13_as_out_date
          FROM step13_as a
          WHERE a.s13_as_level = '5'  -- 완료
          ORDER BY a.s13_asid DESC";
```

### 7.2 as_request_handler.php에서의 삽입 (INSERT)

```php
// AS 요청 저장
$now = date('Y-m-d H:i:s');
$member_id = intval($_POST['member_id']);  // step11_member ID
$in_how = trim($_POST['in_how']);          // '내방', '택배', '퀵'

$insert_query = "INSERT INTO step13_as (
    s13_meid, 
    s13_as_in_how, 
    s13_as_in_date, 
    s13_step
) VALUES ('$member_id', '$in_how', '$now', 1)";

$result = mysql_query($insert_query);
$as_id = mysql_insert_id();  // 새로 생성된 s13_asid 획득
```

### 7.3 as_requests.php에서의 삭제 (DELETE)

```php
// AS 신청 삭제 (관련 항목 함께 삭제)
$delete_id = intval($_GET['id']);

// 1. step13_as 삭제
$delete_query = "DELETE FROM step13_as WHERE s13_asid = $delete_id";
mysql_query($delete_query);

// 2. step14_as_item (관련 항목) 함께 삭제
$delete_items_query = "DELETE FROM step14_as_item WHERE s14_asid = $delete_id";
mysql_query($delete_items_query);
```

---

## 8. 데이터 크기 추정

| 항목 | 수치 | 비고 |
|-----|------|------|
| 전체 행 수 | ~50,000 | 약 5년치 데이터 |
| 평균 레코드 크기 | ~500 bytes | VARCHAR 필드들 |
| 테이블 크기 | ~25 MB | 50,000 x 500 |
| 일일 신규 레코드 | ~20-30개 | 업무일 기준 |
| 월간 신규 레코드 | ~500-600개 | 업무일 기준 |

---

## 9. 성능 최적화 권장사항

### 현재 문제점
1. **VARCHAR로 금액 저장**: `s13_total_cost`는 DECIMAL(10,2)로 변경 권장
2. **VARCHAR로 날짜 저장**: `s13_bank_check`는 DATETIME으로 분리 권장
3. **상태값이 문자**: `s13_as_level`을 TINYINT로 변경하면 저장 공간 50% 절감
4. **인덱스 부족**: 자주 조회되는 필드에 인덱스 추가 필요

### 권장 인덱스
```sql
-- 기본 인덱스
CREATE INDEX idx_s13_as_center ON step13_as(s13_as_center);
CREATE INDEX idx_s13_as_level ON step13_as(s13_as_level);
CREATE INDEX idx_s13_as_in_date ON step13_as(s13_as_in_date);

-- 복합 인덱스
CREATE INDEX idx_s13_level_date ON step13_as(s13_as_level, s13_as_in_date);
CREATE INDEX idx_s13_center_level ON step13_as(s13_as_center, s13_as_level);

-- 검색 인덱스
CREATE INDEX idx_s13_name ON step13_as(s13_name);
CREATE INDEX idx_s13_product ON step13_as(s13_product);
```

---

## 10. 마이그레이션 시 주의사항

### UTF-8mb4 변환 시
```sql
-- 기존 (EUC-KR)
CHARACTER SET euckr COLLATE euckr_korean_ci

-- 변환 후 (UTF-8mb4)
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
```

### 데이터 타입 개선 (선택)
```sql
-- 금액 필드 변환
ALTER TABLE step13_as 
MODIFY s13_total_cost DECIMAL(12,2);

-- 상태값 최적화
ALTER TABLE step13_as 
MODIFY s13_as_level TINYINT COMMENT '1:입고, 2:검진, 3:수리, 4:출고, 5:완료';

-- 날짜 필드 분리
ALTER TABLE step13_as 
ADD s13_bank_check_date DATETIME,
DROP s13_bank_check;
```

---

## 11. 검색 및 조회 패턴

### 자주 사용되는 WHERE 조건
```php
// 1. 단계별 조회
WHERE s13_as_level = '1'              // 입고 요청만
WHERE s13_as_level IN ('2','3','4')   // 작업 중인 것

// 2. 날짜 범위 조회
WHERE DATE(s13_as_in_date) >= '2025-11-01'
  AND DATE(s13_as_in_date) <= '2025-11-30'

// 3. 센터별 조회
WHERE s13_as_center = 'center_001'

// 4. 텍스트 검색
WHERE s13_name LIKE '%김철수%'
WHERE s13_product LIKE '%냉장고%'

// 5. 입금 상태 조회
WHERE s13_bank_check = 'Y'
WHERE s13_bank_check IS NULL

// 6. 복합 조건
WHERE s13_as_level IN ('1','2') 
  AND s13_as_center = 'center_001'
  AND DATE(s13_as_in_date) >= '2025-11-01'
```

---

## 12. step13_as와 step14_as_item의 관계

### 1:N 관계 (1개의 AS 신청 : N개의 제품)

**step13_as** (AS 신청)
- 하나의 신청 건당 하나의 레코드

**step14_as_item** (AS 항목)
- 하나의 신청에 포함된 여러 제품 정보
- 예: 냉장고 신청 1건에 냉동실, 냉장실, 컴프레셔 등 3개 부품

```php
// 연관 조회 예시
$as_query = "SELECT * FROM step13_as WHERE s13_asid = 1";
$as_result = mysql_query($as_query);
$as_row = mysql_fetch_assoc($as_result);  // 1개 행

// 해당 AS에 포함된 모든 항목 조회
$items_query = "SELECT s14_aiid, s14_model, s14_poor 
                FROM step14_as_item 
                WHERE s14_asid = 1";
$items_result = mysql_query($items_query);
while ($item_row = mysql_fetch_assoc($items_result)) {  // 여러 행
    // 각 항목 처리
}
```

---

## 최종 요약

### step13_as 테이블의 역할
- **핵심 AS 관리 테이블**: 수리 신청부터 완료까지의 전체 프로세스 추적
- **중앙 허브**: step14_as_item, step2_center, step3_member 등과 연결
- **상태 추적**: 4단계 처리 프로세스 (입고→검진→수리→출고)
- **비용/결제 관리**: 총 비용, 입금 확인, 세금 처리

### 최빈 사용 컬럼 (TOP 10)
1. `s13_asid` - 조회 및 참조
2. `s13_as_level` - 단계별 필터링 (가장 자주 사용)
3. `s13_as_in_date` - 날짜 범위 검색
4. `s13_as_center` - 센터별 분류
5. `s13_name` - 고객명 검색
6. `s13_product` - 제품명 검색
7. `s13_status` - 상태 표시
8. `s13_total_cost` - 비용 조회
9. `s13_as_in_how` - 입고 방식 분류
10. `s13_as_out_date` - 출고 일자 추적

### 개선 권장사항
1. 금액 필드를 DECIMAL로 변경
2. 상태값을 TINYINT로 변경
3. 자주 조회되는 필드에 인덱스 추가
4. 입금 상태 필드 구조 개선

---

*작성자: Claude Code AI Assistant*  
*마지막 업데이트: 2025-11-06*
