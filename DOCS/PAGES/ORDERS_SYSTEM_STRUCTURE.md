# 자재 판매 시스템 (Orders System) 구조 문서

## 📋 시스템 개요

자재 판매 시스템은 다음과 같은 플로우로 동작합니다:

```
[신규 주문 등록] → [주문 목록 조회] → [주문 수정] → [상태 업데이트] → [영수증 출력]
  (order_handler)    (orders.php)    (order_edit)  (order_payment)  (receipt.php)
```

---

## 🗄️ 데이터베이스 테이블

### 1. step20_sell (주문 정보)

```sql
CREATE TABLE step20_sell (
  s20_sellid INT PRIMARY KEY AUTO_INCREMENT,      -- 주문 ID
  s20_meid INT,                                   -- 회원 ID (step11_member)
  s20_sell_in_date VARCHAR(255),                  -- 판매요청 날짜 (datetime)
  s20_total_cost INT,                             -- 총액
  s20_as_level ENUM('1','2','3','4') DEFAULT '1', -- 상태: 1=판매요청, 2=판매완료
  s20_as_time VARCHAR(6),                         -- 접수 시간 (YYMMDD)
  s20_as_in_no VARCHAR(12),                       -- 접수번호 (NO + YYMMDD + - + 순번)
  s20_as_in_no2 VARCHAR(12),                      -- 접수번호2 (YYMMDD + 순번)
  s20_bank_check VARCHAR(32),                     -- 입금확인 날짜 (datetime)
  s20_as_out_date VARCHAR(255),                   -- 판매완료 날짜 (datetime)
  s20_bankcheck_w VARCHAR(32),                    -- 입금확인자 ('center' 등)
  ex_company VARCHAR(255),                        -- 업체명
  ex_tel VARCHAR(255),                            -- 전화번호
  ex_sec1 VARCHAR(255),                           -- 회원 구분 (일반/대리점/딜러)
  s20_as_center VARCHAR(255),                     -- AS센터 ID
  ...
)
```

### 2. step21_sell_cart (주문 상세 - 자재 목록)

```sql
CREATE TABLE step21_sell_cart (
  s21_accid INT PRIMARY KEY AUTO_INCREMENT,      -- 항목 ID
  s21_sellid INT,                                 -- 주문 ID (step20_sell)
  s21_uid INT,                                    -- 자재 ID (step1_parts)
  s21_quantity INT,                               -- 수량
  cost1 INT,                                      -- 단가
  cost_name VARCHAR(255)                          -- 가격 유형
)
```

### 3. step1_parts (자재 정보)

```sql
-- 주요 필드:
s1_uid INT PRIMARY KEY,                           -- 자재 ID
s1_name VARCHAR(255),                             -- 자재명
s1_caid INT,                                      -- 카테고리 ID
s1_cost_c_1 INT,                                  -- AS센터 공급가
s1_cost_a_1 INT,                                  -- 대리점 공급가
s1_cost_n_1 INT,                                  -- 일반 공급가
```

### 4. step5_category (자재 카테고리)

```sql
-- 주요 필드:
s5_caid VARCHAR(4) PRIMARY KEY,                   -- 카테고리 ID (0001, 0002, ...)
s5_category VARCHAR(255)                          -- 카테고리명
```

### 5. step11_member (회원/업체 정보)

```sql
-- 주요 필드:
s11_meid INT PRIMARY KEY AUTO_INCREMENT,         -- 회원 ID
s11_com_name VARCHAR(255),                       -- 업체명
s11_phone1 VARCHAR(255),                          -- 전화번호1
s11_phone2 VARCHAR(255),                          -- 전화번호2
s11_phone3 VARCHAR(255),                          -- 전화번호3
s11_sec VARCHAR(255),                             -- 회원 구분 (일반/대리점/딜러)
```

---

## 📄 페이지별 상세 설명

### 1. order_handler.php - 신규 주문 등록

**역할**: 새로운 자재 판매 신청 생성

**페이지 흐름**:

1. 업체명 검색 (기존 업체) 또는 신규 등록
2. 자재 선택 (카테고리/검색)
3. 선택된 자재 목록 확인
4. 저장 (step20_sell + step21_sell_cart)

**API 액션** (AJAX):

| 액션            | 메서드 | 기능                         | 요청 파라미터                           | 응답                                                        |
| --------------- | ------ | ---------------------------- | --------------------------------------- | ----------------------------------------------------------- |
| `search_member` | POST   | 업체명 검색                  | `search_name`                           | `{success, members: [{s11_meid, s11_com_name, phone}]}`     |
| `add_member`    | POST   | 신규 업체 등록               | `com_name, phone1, phone2, phone3, sec` | `{success, member_id, com_name}`                            |
| `get_parts`     | POST   | 자재 검색 (회원 구분별 가격) | `search_key, category, member_id`       | `{success, parts: [{s1_uid, s1_name, s5_category, price}]}` |
| `save_order`    | POST   | 주문 저장                    | `member_id, items`                      | `{success, sell_id}`                                        |

**데이터베이스 변경사항**:

- `step20_sell` 1행 INSERT (s20_as_level='1')
- `step21_sell_cart` N행 INSERT (자재별 1행)

**리다이렉트**: 저장 후 `orders.php?tab=request` 리다이렉트

---

### 2. orders.php - 주문 목록 및 관리

**역할**: 기존 주문 조회 및 상태 관리 (탭 기반 UI)

**탭 구조**:

- **Tab 1: 판매요청** (s20_as_level='1')
  - 아직 판매가 완료되지 않은 주문
  - 상태: "판매신청", "입금확인", "수정", "삭제" 버튼
- **Tab 2: 판매완료** (s20_as_level='2')
  - 이미 판매가 완료된 주문
  - 상태: 접수번호, "취소", "영수증" 버튼

**페이지 기능**:

1. 탭 전환
2. 검색 (업체명, 상태, 매출액, 입금여부)
3. 페이징 (10개씩)
4. 주문 수정 (order_edit.php로 이동)
5. 주문 삭제 (order_handler.php?action=delete_order)
6. 주문 상태 업데이트 (order_payment.php로 이동)
7. 영수증 조회 (receipt.php 팝업)

**데이터 조회**:

```php
// 판매요청 탭 (s20_as_level='1')
SELECT * FROM step20_sell
WHERE s20_as_level = '1'
  AND (검색조건)
ORDER BY s20_sellid DESC
LIMIT 10 OFFSET page

// 판매완료 탭 (s20_as_level='2')
SELECT * FROM step20_sell
WHERE s20_as_level = '2'
  AND (검색조건)
ORDER BY s20_sellid DESC
LIMIT 10 OFFSET page
```

**외부 연동 페이지**:

- `order_handler.php?action=delete_order` - 주문 삭제
- `order_edit.php?id=SELL_ID` - 주문 수정
- `order_payment.php?id=SELL_ID&action=complete` - 판매완료
- `order_payment.php?id=SELL_ID&action=confirm` - 입금확인
- `order_payment.php?id=SELL_ID&action=cancel` - 취소
- `receipt.php?id=SELL_ID` - 영수증 (새창)

---

### 3. order_edit.php - 주문 수정

**역할**: 기존 주문의 자재 목록 수정 (추가/삭제/수량변경)

**페이지 기능**:

1. 기존 자재 목록 표시
2. 자재 추가 (검색)
3. 수량 변경
4. 자재 삭제

**API 액션** (AJAX):

| 액션               | 메서드 | 기능                    | 요청 파라미터          | 응답                                 |
| ------------------ | ------ | ----------------------- | ---------------------- | ------------------------------------ |
| `get_parts`        | POST   | 자재 검색               | `search_key, category` | `{success, parts}`                   |
| `add_part`         | POST   | 자재 추가 (중복 체크 O) | `part_id, quantity`    | `{success, is_duplicate, new_total}` |
| `update_quantity`  | POST   | 수량 수정               | `accid, quantity`      | `{success, new_total}`               |
| `delete_cart_item` | POST   | 자재 삭제               | `accid`                | `{success, new_total}`               |

**중복 처리 로직**:

```php
// add_part 액션에서:
1. 같은 자재(s21_uid)가 cart에 있는지 확인
2. 있으면: UPDATE s21_quantity (수량 누적)
3. 없으면: INSERT 새 행
```

**데이터베이스 변경사항**:

- `step21_sell_cart` 행 추가/수정/삭제
- `step20_sell.s20_total_cost` 업데이트 (자동 재계산)

**리다이렉트**: 수정 완료 후 `orders.php` 리다이렉트

---

### 4. order_payment.php - 주문 상태 업데이트

**역할**: 주문 상태 변경 (완료/입금확인/취소)

**액션별 동작**:

#### Action: `complete` (판매 완료)

```
s20_as_level = '2'                    // 상태 변경
s20_as_time = 'YYMMDD' (예: 251105)   // 시간 정보
s20_as_in_no = 'NOYYMMDD-순번'        // 접수번호 생성
s20_as_in_no2 = 'YYMMDD순번'          // 접수번호2 생성
s20_bank_check = datetime             // 입금확인 날짜 설정
s20_as_out_date = datetime            // 판매완료 날짜 설정
s20_bankcheck_w = 'center'            // 입금확인자 설정
```

**리다이렉트**: `orders.php?tab=completed`

#### Action: `confirm` (입금 확인)

```
s20_bank_check = datetime             // 입금확인 날짜만 설정
```

**리다이렉트**: `orders.php?tab=request`

#### Action: `cancel` (판매 완료 취소)

```
s20_as_level = '1'                    // 상태 되돌림
s20_as_time = ''                      // 초기화
s20_as_in_no = ''                     // 초기화
s20_as_in_no2 = ''                    // 초기화
s20_bank_check = NULL                 // 초기화
s20_as_out_date = NULL                // 초기화
s20_bankcheck_w = ''                  // 초기화
```

**리다이렉트**: `orders.php?tab=request`

**접수번호 생성 로직**:

```php
// 현재 날짜기준 같은 날의 주문 개수 조회
$count = COUNT(*) FROM step20_sell
         WHERE DATE(s20_as_out_date) = CURDATE()
$seq_no = $count + 1  // 1부터 시작

// s20_as_in_no: NO + YYMMDD + - + 3자리 제로패딩
// 예: NO251105-001, NO251105-002

// s20_as_in_no2: YYMMDD + 3자리 제로패딩
// 예: 251105001, 251105002
```

---

### 5. receipt.php - 영수증 출력

**역할**: 완료된 주문의 영수증 표시 (상세 정보 + 자재 목록)

**페이지 기능**:

1. 주문 정보 표시
2. 자재 목록 표시
3. 총액 계산
4. 인쇄 버튼
5. 닫기 버튼 (window.close())

**데이터 조회**:

```php
// 주문 정보
SELECT * FROM step20_sell WHERE s20_sellid = id

// 자재 목록
SELECT s21_uid, s21_quantity, cost1
FROM step21_sell_cart
WHERE s21_sellid = id

// 자재명
SELECT s1_name FROM step1_parts WHERE s1_uid = uid
```

**페이지 특징**:

- 새창 열기로 표시 (window.open())
- 인쇄 기능 포함 (window.print())
- 닫기 버튼으로 창 종료 (window.close())

---

## 🔄 상태 전이도

```
[신규 주문]
    ↓
[판매요청] (s20_as_level='1')
    ├─ 입금확인 → (s20_bank_check 설정)
    │              (상태는 여전히 '1')
    │
    └─ 판매완료 → [판매완료] (s20_as_level='2')
                      │
                      ├─ 접수번호 생성 (s20_as_in_no, s20_as_in_no2)
                      ├─ 판매일자 기록 (s20_as_out_date)
                      │
                      └─ 취소 → [판매요청] (s20_as_level='1')
                               (모든 정보 초기화)
```

---

## 📊 페이지 요청/응답 흐름

### 신규 주문 생성 흐름

```
1. order_handler.php 로드
2. [업체 검색] → search_member (AJAX)
   ↓ 또는 [새로 등록] → add_member (AJAX)
3. [자재 검색] → get_parts (AJAX)
4. [자재 추가] → 클라이언트 메모리에 저장 (selectedItems[])
5. [저장] → save_order (AJAX)
   ├─ step20_sell INSERT
   └─ step21_sell_cart INSERT (자재별 1행)
6. 리다이렉트 → orders.php?tab=request
```

### 주문 수정 흐름

```
1. orders.php에서 [수정] 클릭
2. order_edit.php?id=SELL_ID 로드
3. [기존 자재 목록] 표시
4. [자재 추가] → add_part (AJAX)
   ├─ 중복 체크 (DB에서)
   ├─ 있으면: UPDATE (수량 누적)
   └─ 없으면: INSERT (새행 추가)
5. [수량 변경] → update_quantity (AJAX)
6. [삭제] → delete_cart_item (AJAX)
7. [수정] 버튼 → orders.php 리다이렉트
```

### 주문 상태 업데이트 흐름

```
1. orders.php에서 액션 버튼 클릭
2. order_payment.php?id=SELL_ID&action=ACTION
3. 해당 액션 처리:
   - complete: 접수번호 생성 + 상태 변경
   - confirm: 입금확인 날짜 기록
   - cancel: 모든 정보 초기화
4. 리다이렉트 → orders.php?tab=TAB
```

---

## 🔧 주요 SQL 쿼리

### 판매요청 목록 조회

```sql
SELECT s.*, c.s1_name as item_name
FROM step20_sell s
LEFT JOIN step21_sell_cart c ON s.s20_sellid = c.s21_sellid
WHERE s.s20_as_level = '1'
ORDER BY s.s20_sellid DESC
LIMIT 10
```

### 판매완료 목록 조회

```sql
SELECT * FROM step20_sell
WHERE s20_as_level = '2'
ORDER BY s20_as_out_date DESC
LIMIT 10
```

### 영수증 자재 목록

```sql
SELECT
  c.s21_uid,
  c.s21_quantity,
  c.cost1,
  p.s1_name,
  (c.cost1 * c.s21_quantity) as item_total
FROM step21_sell_cart c
LEFT JOIN step1_parts p ON c.s21_uid = p.s1_uid
WHERE c.s21_sellid = ?
ORDER BY c.s21_accid
```

---

## 📝 as_requests.php 개발 참고사항

as_requests.php를 개발할 때 order_handler.php의 구조를 참고할 수 있습니다:

### 유사점:

- ✅ 회원/업체 선택 UI
- ✅ 자재 검색 및 카테고리 필터
- ✅ 선택된 항목 목록 관리
- ✅ AJAX 기반 API 액션
- ✅ 데이터 저장 (신규 기록 생성)

### 차이점:

- 🔄 order_handler: step20_sell (자재 판매) 저장
- 🔄 as_requests: step??\_as (AS 작업) 저장 (테이블명 확인 필요)
- 🔄 자재 타입: order_handler는 s1_parts, as_requests는 다를 수 있음
- 🔄 추가 필드: AS 작업은 불량증상, AS결과 등 추가 필드 필요

---

## 🗂️ 파일 목록

| 파일명              | 설명           | 역할                   |
| ------------------- | -------------- | ---------------------- |
| `order_handler.php` | 신규 주문 생성 | 새 판매 신청 등록      |
| `orders.php`        | 주문 목록/관리 | 주문 조회 및 상태 관리 |
| `order_edit.php`    | 주문 수정      | 기존 주문 자재 수정    |
| `order_payment.php` | 상태 업데이트  | 완료/입금/취소 처리    |
| `receipt.php`       | 영수증 출력    | 완료된 주문 상세조회   |

---

**마지막 수정**: 2025-11-05
**작성자**: Claude Code
**버전**: 1.0
