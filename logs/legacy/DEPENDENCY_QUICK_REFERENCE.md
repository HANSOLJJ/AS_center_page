# AS 시스템 의존성 - 빠른 참조 가이드

**용도**: 개발 중 빠르게 파일 간 의존성 확인  
**최종 업데이트**: 2025-11-10

---

## 1. 파일별 필수 포함 (Includes)

### 세션/인증 체크 패턴

```php
// 패턴 1: mysql_compat.php만 사용 (가장 많음)
<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: login.php');
    exit;
}
require_once 'mysql_compat.php';
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);
```

**사용 파일**:
- parts.php, parts_add.php, parts_edit.php
- products.php, product_add.php, product_edit.php
- members.php, member_add.php, member_edit.php
- orders.php, order_edit.php, order_handler.php
- as_requests.php, as_request_handler.php
- as_repair.php, as_repair_handler.php
- receipt.php

### 패턴 2: @config.php 포함 (레거시)

```php
// 패턴: @config.php 포함 시 session_start, DB 설정 자동
<?php
require_once '@session_inc.php';
// 이미 session_start(), DB 연결, 권한 확인 포함됨
```

**사용 파일**:
- as_request_view.php (명시적 포함)
- as_center1, as_center2, as_center3, as_center4, as_center5 (레거시)

---

## 2. 테이블별 필드 요약

### step1_parts (자재)

| 필드 | 타입 | 설명 |
|------|------|------|
| s1_uid | INT | Primary Key (Auto Increment) |
| s1_name | VARCHAR | 자재명 (필수) |
| s1_caid | VARCHAR | 카테고리 ID (FK to step5_category) |
| s1_cost_c_1 | INT | AS center 공급가 |
| s1_cost_a_1 | INT | 대리점 개별 판매가 |
| s1_cost_a_2 | INT | 대리점 수리용 판매가 |
| s1_cost_n_1 | INT | 일반 개별 판매가 |
| s1_cost_n_2 | INT | 일반 수리용 판매가 |
| s1_cost_s_1 | INT | 특별공급가 |

**사용 페이지**: parts.php, as_repair.php

---

### step5_category (자재 카테고리)

| 필드 | 타입 | 설명 |
|------|------|------|
| s5_caid | VARCHAR | Primary Key (4자리, 0001부터) |
| s5_category | VARCHAR | 카테고리명 |

**사용 페이지**: parts.php, category_add/edit.php

---

### step11_member (고객)

| 필드 | 타입 | 설명 |
|------|------|------|
| s11_meid | INT | Primary Key (Auto Increment) |
| s11_sec | VARCHAR | 분류 (일반, 대리점, 딜러) |
| s11_com_name | VARCHAR | 업체명 (필수) |
| s11_phone1, 2, 3 | VARCHAR | 전화번호 (필수) |
| s11_com_man | VARCHAR | 담당자명 |
| s11_oaddr | VARCHAR | 주소 |
| s11_phone4, 5, 6 | VARCHAR | 추가 전화번호 |
| s11_com_num1, 2, 3 | VARCHAR | 사업자번호 |
| s11_com_zip1, 2 | VARCHAR | 우편번호 |
| s11_com_sec1, 2 | VARCHAR | 분류 추가 정보 |

**사용 페이지**: members.php, member_add/edit.php, orders.php, as_requests.php

---

### step13_as (AS 요청)

| 필드 | 타입 | 설명 |
|------|------|------|
| s13_asid | INT | Primary Key |
| s13_meid | INT | Member ID (FK to step11_member) |
| s13_as_level | CHAR | 상태 (1=요청, 2-4=작업중, 5=완료) |
| s13_as_in_date | DATETIME | 입고일 |
| s13_as_out_date | DATETIME | 출고일 |
| s13_as_in_how | VARCHAR | 입고 방법 |

**사용 페이지**: as_requests.php, as_request_view.php, as_request_handler.php

---

### step14_as_item (AS 아이템)

| 필드 | 타입 | 설명 |
|------|------|------|
| s14_aiid | INT | Primary Key |
| s14_asid | INT | AS ID (FK to step13_as) |
| s14_model | INT | 모델 ID (FK to step15_as_model) |
| s14_poor | INT | 불량증상 ID (FK to step16_as_poor) |
| s14_as_start_view | VARCHAR | 시작 화면 표시 |

**사용 페이지**: as_requests.php, as_request_view.php, as_repair.php

---

### step15_as_model (제품 모델)

| 필드 | 타입 | 설명 |
|------|------|------|
| s15_amid | INT | Primary Key |
| s15_model_name | VARCHAR | 모델명 (필수) |
| s15_model_sn | VARCHAR | 시리얼/버전 (필수) |

**사용 페이지**: products.php, product_add/edit.php, as_requests.php

---

### step16_as_poor (불량증상)

| 필드 | 타입 | 설명 |
|------|------|------|
| s16_apid | INT | Primary Key |
| s16_poor | VARCHAR | 불량증상명 (필수) |

**사용 페이지**: products.php, poor_add/edit.php, as_requests.php

---

### step18_as_cure_cart (수리용 자재)

| 필드 | 타입 | 설명 |
|------|------|------|
| s18_accid | INT | Primary Key |
| s18_aiid | INT | AS Item ID (FK to step14_as_item) |
| s18_uid | INT | 자재 ID (FK to step1_parts) |
| s18_cost | INT | 비용 |
| s18_quantity | INT | 수량 |
| s18_cost_sec | VARCHAR | 비용 분류 |

**사용 페이지**: as_repair.php, as_repair_handler.php, as_request_view.php

---

### step19_as_result (AS 결과 타입)

| 필드 | 타입 | 설명 |
|------|------|------|
| s19_asrid | INT | Primary Key |
| s19_result | VARCHAR | AS 결과명 (필수) |

**사용 페이지**: products.php, result_add/edit.php

---

### step20_sell (판매 주문)

| 필드 | 타입 | 설명 |
|------|------|------|
| s20_sellid | INT | Primary Key |
| s20_as_level | CHAR | 상태 |
| s20_sell_in_date | DATETIME | 접수일 |
| s20_as_out_date | DATETIME | 완료일 |
| s20_bank_check | DATETIME | 입금 확인일 |
| s20_meid | INT | Member ID (FK to step11_member) |
| s20_total_cost | INT | 총액 |
| ex_company | VARCHAR | 거래처명 |
| ex_tel | VARCHAR | 전화 |
| ex_man | VARCHAR | 담당자 |
| ex_address | VARCHAR | 주소 |

**사용 페이지**: orders.php, order_edit.php, order_handler.php, receipt.php

---

### step21_sell_cart (판매 장바구니)

| 필드 | 타입 | 설명 |
|------|------|------|
| s21_cartid | INT | Primary Key |
| s21_sellid | INT | Sales ID (FK to step20_sell) |
| s21_uid | INT | 자재 ID (FK to step1_parts) |
| s21_quantity | INT | 수량 |
| s21_cost | INT | 비용 |

**사용 페이지**: orders.php, order_handler.php, receipt.php

---

## 3. 주요 쿼리 패턴

### 삽입 (INSERT)

```php
// 자재 추가
INSERT INTO step1_parts (s1_name, s1_caid, s1_cost_c_1, ...) 
VALUES ('자재명', 1, 50000, ...)

// 고객 추가
INSERT INTO step11_member (s11_sec, s11_com_name, s11_phone1, ...) 
VALUES ('일반', '업체명', '032', ...)
```

### 조회 (SELECT)

```php
// 자재 목록 (페이징)
SELECT * FROM step1_parts WHERE 1=1 LIMIT 10 OFFSET 0

// AS 요청 상세 (조인)
SELECT a.*, m.s11_com_name, COUNT(ai.s14_aiid) as item_count
FROM step13_as a
LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
LEFT JOIN step14_as_item ai ON a.s13_asid = ai.s14_asid
WHERE a.s13_asid = 123
GROUP BY a.s13_asid
```

### 업데이트 (UPDATE)

```php
// 자재 수정
UPDATE step1_parts SET s1_name = '새이름' WHERE s1_uid = 123

// AS 상태 변경
UPDATE step13_as SET s13_as_level = '2' WHERE s13_asid = 123
```

### 삭제 (DELETE)

```php
// 자재 삭제
DELETE FROM step1_parts WHERE s1_uid = 123

// AS 요청 삭제 (자식 먼저)
DELETE FROM step14_as_item WHERE s14_asid = 123
DELETE FROM step13_as WHERE s13_asid = 123
```

---

## 4. URL 파라미터 체크리스트

### 공통 파라미터

| 파라미터 | 타입 | 사용 페이지 | 설명 |
|---------|------|-----------|------|
| id | INT | *_add.php, *_edit.php | 엔티티 ID |
| tab | STRING | parts.php, products.php, orders.php, as_requests.php | 탭 선택 |
| page | INT | 대부분 | 페이지 번호 |
| action | STRING | *_handler.php, 삭제 | 액션 타입 |
| deleted | BOOL | *_add/edit.php | 삭제 완료 플래그 |
| inserted | BOOL | *_add/edit.php | 삽입 완료 플래그 |

### 검색 파라미터

```php
// parts.php
?tab=tab1&search_keyword=자재명&category=0001&page=1

// products.php
?tab=model&search_keyword=모델명&page=1

// members.php
?search_type=company&search_keyword=업체명&page=1

// orders.php
?tab=request&search_customer=업체명&search_phone=032&search_start_date=2025-01-01&page=1

// as_requests.php
?tab=request&search_customer=업체명&search_start_date=2025-01-01&page=1
```

---

## 5. AJAX 요청 매핑

### orders.php → order_handler.php

```javascript
// 고객 검색
POST order_handler.php?action=search_member
{ search_name: '업체명' }
→ { success: true, members: [...] }

// 새 고객 추가
POST order_handler.php?action=add_member
{ com_name, phone1, phone2, phone3, sec }
→ { success: true, member_id: 456 }

// 주문 삭제
GET order_handler.php?action=delete_order&id=123&tab=request
→ redirect to orders.php?deleted=1
```

### as_requests.php → as_request_handler.php

```javascript
// AS 요청 데이터 로드 (수정용)
POST as_request_handler.php?action=get_as_request_data
{ as_id: 123 }
→ { success: true, as_info, member_info, products }

// Step 3 데이터 로드
POST as_request_handler.php?action=load_step3_data
{ }
→ { success: true, models, poors }
```

### as_repair.php → as_repair_handler.php

```javascript
// 수리 Step 저장
POST as_repair_handler.php
{ action: 'save_repair_step', itemid, as_end_result, parts_data }
→ { success: true, message }

// 부품 로드 (페이징)
GET as_repair_handler.php?action=load_parts&page=1&limit=10
→ { success: true, parts, total_pages }
```

---

## 6. 성공/실패 메시지 처리

### 리다이렉트 패턴

```php
// 성공 시 리다이렉트 + 메시지 표시
header('Location: parts.php?tab=tab1&inserted=1');

// 페이지에서 확인
if (isset($_GET['inserted']) && $_GET['inserted'] === '1') {
    $success_message = '저장 완료되었습니다.';
}
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success_message = '삭제 완료되었습니다.';
}
```

### AJAX JSON 패턴

```json
{
    "success": true,
    "message": "작업 완료",
    "data": { }
}

// 또는 에러

{
    "success": false,
    "message": "작업 실패: 이유"
}
```

---

## 7. 보안 필드 체크

### 항상 확인할 것

- [ ] `$_SESSION['member_id']` 및 `$_SESSION['member_sid']` 확인?
- [ ] `mysql_real_escape_string()` 사용?
- [ ] 외래키 제약 조건 (SET FOREIGN_KEY_CHECKS)?
- [ ] 대량 삭제 시 자식 데이터 처리?

### SQL 인젝션 방지

```php
// 위험
$query = "SELECT * FROM table WHERE id = $id";

// 안전
$id = intval($_GET['id']);
$query = "SELECT * FROM table WHERE id = $id";

// 또는
$escaped = mysql_real_escape_string($_GET['search']);
$query = "SELECT * FROM table WHERE name LIKE '%$escaped%'";
```

---

## 8. 개발 시 자주 사용하는 쿼리

### 테이블 전체 행 수

```php
$result = mysql_query("SELECT COUNT(*) as cnt FROM table_name");
$row = mysql_fetch_assoc($result);
$total = $row['cnt'];
```

### 페이징 계산

```php
$per_page = 10;
$page = max(1, intval($_GET['page']));
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total / $per_page);
```

### 최신 ID 확인

```php
$insert_id = mysql_insert_id();
```

### 영향받은 행 수

```php
$affected = mysql_affected_rows();
if ($affected > 0) { echo "성공"; }
```

---

## 9. 주요 함수 (mysql_compat.php)

| 함수 | 설명 |
|------|------|
| mysql_connect() | DB 연결 |
| mysql_select_db() | DB 선택 |
| mysql_query() | 쿼리 실행 |
| mysql_fetch_assoc() | 연관배열로 행 조회 |
| mysql_fetch_array() | 배열(BOTH, NUM, ASSOC) |
| mysql_fetch_row() | 인덱스 배열 |
| mysql_num_rows() | 조회 행 수 |
| mysql_affected_rows() | 영향받은 행 수 |
| mysql_insert_id() | 마지막 INSERT ID |
| mysql_real_escape_string() | SQL 문자열 이스케이프 |
| mysql_error() | 에러 메시지 |
| mysql_close() | 연결 종료 |

---

## 10. 자주 실수하는 부분

### 1. 외래키 제약 무시

```php
// 잘못된 순서 (에러 발생)
DELETE FROM step13_as WHERE s13_asid = 123  // 자식이 있으면 실패

// 올바른 순서
DELETE FROM step14_as_item WHERE s14_asid = 123
DELETE FROM step13_as WHERE s13_asid = 123
```

### 2. 타입 캐스팅 누락

```php
// 위험 (타입 체크 없음)
$id = $_GET['id'];

// 안전
$id = intval($_GET['id']);
$id = (int) $_GET['id'];
```

### 3. 세션 확인 누락

```php
// 항상 페이지 상단에
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: login.php');
    exit;
}
```

### 4. 리다이렉트 후 코드 계속 실행

```php
// 잘못됨
header('Location: parts.php?inserted=1');
// 이 아래 코드도 실행됨 (보안 이슈)

// 올바름
header('Location: parts.php?inserted=1');
exit;  // 필수!
```

### 5. 페이징 미적용

```php
// 대량 데이터 쿼리는 항상 LIMIT 사용
$query = "SELECT * FROM step13_as LIMIT $limit OFFSET $offset";
```

---

## 11. 테스트 시 유용한 쿼리

### 테이블 행 수 확인

```sql
SELECT COUNT(*) FROM step1_parts;
SELECT COUNT(*) FROM step11_member;
SELECT COUNT(*) FROM step13_as;
SELECT COUNT(*) FROM step14_as_item;
SELECT COUNT(*) FROM step20_sell;
```

### 데이터 정합성 확인

```sql
-- 고아 데이터 확인 (AS 요청은 있는데 고객 없음)
SELECT s13_asid FROM step13_as 
WHERE s13_meid NOT IN (SELECT s11_meid FROM step11_member);

-- 중복 확인
SELECT s1_name, COUNT(*) FROM step1_parts GROUP BY s1_name HAVING COUNT(*) > 1;
```

### 최근 데이터 조회

```sql
SELECT * FROM step13_as ORDER BY s13_asid DESC LIMIT 10;
SELECT * FROM step1_parts ORDER BY s1_uid DESC LIMIT 10;
```

---

**마지막 업데이트**: 2025-11-10  
**유지보수자**: Development Team

