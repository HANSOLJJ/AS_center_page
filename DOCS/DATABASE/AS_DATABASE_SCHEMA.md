# AS 시스템 데이터베이스 스키마 (Application System)

## 개요
AS 시스템은 전자제품 수리/서비스 관리를 위한 단계별(step1-step21) 데이터베이스 테이블로 구성되어 있습니다.

마지막 업데이트: 2025-11-05
현재 인코딩: UTF-8 (utf8mb4)

---

## 핵심 AS 관련 테이블

### 1. step13_as (AS 요청/신청 메인 테이블)
**설명**: AS 수리 요청의 기본 정보를 저장하는 메인 테이블

```sql
CREATE TABLE step13_as (
  s13_asid INT AUTO_INCREMENT PRIMARY KEY,        -- AS ID (자동증가)
  s13_as_center VARCHAR(100),                      -- AS 지점 ID
  s13_as_in_date DATETIME,                        -- 입고 일자
  s13_as_in_how VARCHAR(50),                      -- 수탁 방법 (내방/택배)
  s13_as_in_no VARCHAR(100),                      -- AS 입고 번호
  s13_as_in_no2 VARCHAR(100),                     -- AS 입고 번호 2
  s13_meid VARCHAR(100),                          -- 제조 일련번호/MEID
  s13_dex_no VARCHAR(100),                        -- 배송 번호
  
  -- 상태 및 진행도
  s13_as_level VARCHAR(10),                       -- AS 레벨 (1~4단계)
  s13_as_name1 VARCHAR(100),                      -- AS 처리자 이름1
  s13_as_name2 VARCHAR(100),                      -- AS 처리자 이름2
  s13_as_name3 VARCHAR(100),                      -- AS 처리자 이름3
  s13_as_out_date DATETIME,                       -- AS 출고 일자
  s13_as_time DATETIME,                           -- AS 생성 시간
  
  -- 비용 및 세금
  s13_total_cost VARCHAR(100),                    -- 총 비용
  s13_bank_check VARCHAR(100),                    -- 은행 확인/입금 확인 일자
  s13_bankcheck_w VARCHAR(50),                    -- 은행 확인 상태
  s13_tax_code VARCHAR(100),                      -- 세금 코드
  
  -- 배송 정보
  s13_dex_send VARCHAR(100),                      -- 배송 업체
  s13_dex_send_name VARCHAR(100),                 -- 배송 담당자
  
  -- SMS 및 기타
  s13_sms1 VARCHAR(50),                           -- SMS1
  s13_sms2 VARCHAR(50),                           -- SMS2
  
  -- 추가 필드 (레거시)
  ex_tel VARCHAR(50),
  ex_sms_no VARCHAR(50),
  ex_sec1 VARCHAR(100),
  ex_sec2 VARCHAR(100),
  ex_company VARCHAR(100),
  ex_man VARCHAR(100),
  ex_address VARCHAR(255),
  ex_address_no VARCHAR(100),
  ex_company_no VARCHAR(100),
  ex_total_cost VARCHAR(100),
  
  -- UI에서 표시되는 필드
  s13_name VARCHAR(100),                          -- 고객명
  s13_product VARCHAR(100),                       -- 제품명
  s13_date DATETIME,                              -- 수리 일자
  s13_status VARCHAR(50)                          -- 상태 (대기중/처리중/완료/취소)
);
```

**주요 필드 설명**:
- `s13_as_level`: AS 처리 단계 (1=입고, 2=검진, 3=수리, 4=출고)
- `s13_as_center`: 처리 지점 (step2_center와 연결)
- `s13_bank_check`: 입금 확인 날짜/상태
- `s13_total_cost`: 수리 비용 합계

---

### 2. step14_as_item (AS 항목 테이블)
**설명**: 각 AS 요청에 포함된 아이템/제품 정보

```sql
CREATE TABLE step14_as_item (
  s14_aiid INT AUTO_INCREMENT PRIMARY KEY,        -- AS Item ID
  s14_asid INT,                                   -- step13_as 참조 (Foreign Key)
  s14_model VARCHAR(100),                         -- 모델 ID (step15_as_model 참조)
  s14_poor VARCHAR(100),                          -- 불량증상 ID (step16_as_poor 참조)
  s14_stat VARCHAR(50),                           -- 상태 (진행중/완료 등)
  s14_asrid VARCHAR(100),                         -- AS Result ID (step19_as_result 참조)
  s14_cart VARCHAR(100),                          -- 카트 ID (step18_as_cure_cart 참조)
  
  -- 비용 정보
  cost_name VARCHAR(100),                         -- 비용 항목명
  cost_sn VARCHAR(100),                           -- 시리얼/버전
  cost_sn_hand VARCHAR(100),                      -- 손작업 시리얼
  cost1 DECIMAL(10,2),                            -- 비용1
  cost2 DECIMAL(10,2),                            -- 비용2
  cost3 DECIMAL(10,2),                            -- 비용3
  cost_sec VARCHAR(100),                          -- 비용 분류
  
  -- 결과
  s14_as_nae VARCHAR(255),                        -- AS 내용
  as_start_view VARCHAR(255),                     -- AS 시작 보기
  as_end_result VARCHAR(255),                     -- AS 종료 결과
  
  -- 특별 비용
  s18_sp_cost VARCHAR(100)                        -- 특별 비용
);
```

**관계도**:
- s14_model → step15_as_model.s15_amid (모델 정보)
- s14_poor → step16_as_poor.s16_apid (불량증상)
- s14_asrid → step19_as_result.s19_asrid (처리 결과)
- s14_asid → step13_as.s13_asid (AS 요청)

---

### 3. step15_as_model (AS 모델 관리 테이블)
**설명**: AS 대상 제품의 모델 정보

```sql
CREATE TABLE step15_as_model (
  s15_amid INT AUTO_INCREMENT PRIMARY KEY,        -- 모델 ID
  s15_model_name VARCHAR(100),                    -- 모델명 (예: XXXX-2000)
  s15_model_sn VARCHAR(100)                       -- 시리얼/버전 (예: v1.0)
);
```

**사용예**:
- s15_model_name: "A100", "B200" 등
- s15_model_sn: "v1.0", "v1.1" 등

---

### 4. step16_as_poor (불량증상 관리 테이블)
**설명**: AS 수리 시 사용되는 불량 증상 분류

```sql
CREATE TABLE step16_as_poor (
  s16_apid INT AUTO_INCREMENT PRIMARY KEY,        -- 불량증상 ID
  s16_poor VARCHAR(100)                           -- 불량증상명 (예: 화면불량, 전원불량 등)
);
```

**사용예**:
- "화면 불량"
- "전원 불량"
- "배터리 문제"
- "음성 출력 불량"

---

### 5. step17_as_item_cure (AS 항목 수리 정보 테이블)
**설명**: 각 AS 항목에 대한 수리 상세 정보

```sql
CREATE TABLE step17_as_item_cure (
  s17_acrid INT AUTO_INCREMENT PRIMARY KEY,       -- AS Item Cure ID
  -- 상세 수리 정보 (컬럼 구조는 as_center5 코드에서 참조)
);
```

---

### 6. step18_as_cure_cart (AS 수리 카트 테이블)
**설명**: 수리 항목들을 카트에 담는 임시 저장소

```sql
CREATE TABLE step18_as_cure_cart (
  s18_accid INT AUTO_INCREMENT PRIMARY KEY,       -- AS Cure Cart ID
  s18_aiid INT,                                   -- step14_as_item 참조
  s18_asid INT,                                   -- step13_as 참조 (또는 s18_asid)
  s18_quantity INT,                               -- 수량
  s18_signdate DATETIME,                          -- 생성 일시
  s18_end VARCHAR(10),                            -- 종료 여부 (Y/N)
  s18_uid VARCHAR(100),                           -- 사용자 ID
  
  -- 비용 정보
  cost_name VARCHAR(100),                         -- 비용 항목명
  cost_sn VARCHAR(100),                           -- 시리얼/버전
  cost1 DECIMAL(10,2),                            -- 비용1
  cost2 DECIMAL(10,2),                            -- 비용2
  cost3 DECIMAL(10,2),                            -- 비용3
  cost_sec VARCHAR(100),                          -- 비용 분류
  s18_sp_cost VARCHAR(100)                        -- 특별 비용
);
```

---

### 7. step19_as_result (AS 결과 타입 테이블)
**설명**: AS 수리 결과/처리 방식 분류

```sql
CREATE TABLE step19_as_result (
  s19_asrid INT AUTO_INCREMENT PRIMARY KEY,       -- AS Result ID
  s19_result VARCHAR(100)                         -- 결과타입 (예: 수리완료, 부품교체, 폐기 등)
);
```

**사용예**:
- "수리 완료"
- "부품 교체"
- "폐기"
- "반품"
- "기타"

---

## 지원 테이블 (관계 테이블)

### 8. step2_center (AS 센터/지점 관리 테이블)
**설명**: AS 처리 지점/센터 정보

```sql
CREATE TABLE step2_center (
  s2_cid INT AUTO_INCREMENT PRIMARY KEY,          -- 센터 ID
  s2_center_id VARCHAR(100),                      -- 센터 고유 ID
  s2_center VARCHAR(100),                         -- 센터 이름
  s2_center_tel VARCHAR(50)                       -- 센터 전화번호
);
```

**step13_as와의 관계**:
- s13_as_center → s2_center_id

---

### 9. step3_member (AS 스탭/멤버 관리 테이블)
**설명**: AS 시스템 접근 권한이 있는 관리자/스탭 정보

```sql
CREATE TABLE step3_member (
  s3_meid INT AUTO_INCREMENT PRIMARY KEY,         -- 멤버 ID
  s3_id VARCHAR(100),                             -- 로그인 ID
  s3_passwd VARCHAR(255),                         -- 비밀번호 (해시화됨)
  s3_userlevel VARCHAR(10),                       -- 사용자 레벨
  s3_center_id VARCHAR(100),                      -- 소속 센터 ID (step2_center 참조)
  s3_name VARCHAR(100)                            -- 이름
);
```

---

### 10. step11_member (고객/거래처 정보 테이블)
**설명**: AS 신청 고객 및 거래처 정보

```sql
CREATE TABLE step11_member (
  s11_meid INT AUTO_INCREMENT PRIMARY KEY,        -- 회원 ID
  s11_sec VARCHAR(100),                           -- 분류 (개인/업체 등)
  s11_com_name VARCHAR(100),                      -- 업체명 또는 이름
  s11_com_man VARCHAR(100),                       -- 담당자
  s11_phone1 VARCHAR(10),                         -- 전화번호 1부
  s11_phone2 VARCHAR(10),                         -- 전화번호 2부
  s11_phone3 VARCHAR(10),                         -- 전화번호 3부
  s11_phone4 VARCHAR(10),                         -- 전화번호 4부
  s11_phone5 VARCHAR(10),                         -- 전화번호 5부
  s11_phone6 VARCHAR(10),                         -- 전화번호 6부
  s11_com_num1 VARCHAR(10),                       -- 사업자번호 1부
  s11_com_num2 VARCHAR(10),                       -- 사업자번호 2부
  s11_com_num3 VARCHAR(10),                       -- 사업자번호 3부
  s11_com_zip1 VARCHAR(10),                       -- 우편번호 1부
  s11_com_zip2 VARCHAR(10),                       -- 우편번호 2부
  s11_oaddr VARCHAR(255),                         -- 주소
  s11_com_sec1 VARCHAR(100),                      -- 분류1
  s11_com_sec2 VARCHAR(100)                       -- 분류2
);
```

---

### 11. step5_category (자재/부품 카테고리)
**설명**: 자재 관리 시스템의 카테고리

```sql
CREATE TABLE step5_category (
  s5_caid VARCHAR(10) PRIMARY KEY,                -- 카테고리 ID (0001, 0002 등)
  s5_category VARCHAR(100)                        -- 카테고리명
);
```

---

### 12. step1_parts (자재/부품 정보)
**설명**: AS 수리에 사용되는 부품/자재

```sql
CREATE TABLE step1_parts (
  s1_uid VARCHAR(100) PRIMARY KEY,                -- 부품 고유 ID
  s1_name VARCHAR(100),                           -- 부품명
  s1_erp VARCHAR(100),                            -- ERP 코드
  -- 기타 부품 정보
);
```

---

## 데이터 흐름 (AS 요청 프로세스)

### 1단계: 입고 (Level 1)
```
AS 신청 생성 (step13_as 입력)
├─ s13_as_center: 지점 선택
├─ s13_as_in_date: 입고 일자
├─ s13_as_in_how: 수탁 방법 (내방/택배)
└─ s13_as_level: "1"로 설정
```

### 2단계: 검진/수리 (Level 2)
```
AS 항목 추가 (step14_as_item 입력)
├─ s14_asid: 위의 s13_asid 참조
├─ s14_model: step15_as_model 선택
├─ s14_poor: step16_as_poor 선택
└─ s14_stat: "진행중"

수리 카트 추가 (step18_as_cure_cart 입력)
├─ s18_aiid: 위의 s14_aiid 참조
└─ s18_quantity: 수량

처리자 지정
└─ s13_as_name2: 수리 담당자 이름
```

### 3단계: 결과 등록 (Level 3)
```
AS 결과 입력 (step19_as_result 참조)
├─ s14_asrid: step19_as_result ID
└─ s13_as_level: "3"으로 업데이트

비용 계산
└─ s13_total_cost: 총 비용 계산
```

### 4단계: 출고 (Level 4)
```
배송 정보 입력
├─ s13_dex_send: 배송 업체
├─ s13_dex_send_name: 배송 담당자
└─ s13_dex_no: 배송 추적 번호

최종 처리
├─ s13_as_name3: 출고 담당자
├─ s13_as_out_date: 출고 일자
└─ s13_as_level: "4"로 업데이트
```

---

## 상태 값 정의

### s13_as_level (AS 처리 단계)
| 값 | 설명 |
|----|------|
| 1 | 입고 (Received) |
| 2 | 검진/수리 중 (In Repair) |
| 3 | 수리 완료 (Repaired) |
| 4 | 출고 (Shipped Out) |
| 5 | 외부업체 처리 |

### s14_stat (항목 상태)
| 값 | 설명 |
|----|------|
| 진행중 | 수리 중 |
| 완료 | 수리 완료 |
| 대기중 | 대기 중 |

### s13_bank_check (입금 상태)
| 값 | 설명 |
|----|------|
| (날짜) | 입금 확인 일자 |
| N | 입금 미확인 |
| '' | 입금 확인 대기 |

### s13_tax_code (세금 처리)
| 값 | 설명 |
|----|------|
| (날짜) | 세금 처리 일자 |
| '' | 미처리 |

---

## 검색 및 조회 쿼리 예제

### AS 요청 목록 조회
```php
// as_requests.php에서 사용
SELECT s13_id, s13_name, s13_product, s13_date, s13_status
FROM step13_as
WHERE s13_id LIKE '%$keyword%' 
   OR s13_name LIKE '%$keyword%'
   OR s13_product LIKE '%$keyword%'
ORDER BY s13_id DESC
LIMIT 10;
```

### 특정 AS에 포함된 항목 조회
```php
// as_center/add_list.php에서 사용
SELECT s14_aiid, s14_asid, s14_model, s14_poor, s14_stat, s14_asrid
FROM step14_as_item
WHERE s14_asid = '$s13_asid';
```

### 모델 및 증상 정보 조회
```php
// 모델명 조회
SELECT s15_model_name, s15_model_sn
FROM step15_as_model
WHERE s15_amid = '$s14_model';

// 불량증상 조회
SELECT s16_poor
FROM step16_as_poor
WHERE s16_apid = '$s14_poor';
```

### AS 결과 조회
```php
SELECT s19_result
FROM step19_as_result
WHERE s19_asrid = '$s14_asrid';
```

---

## 중요 주의사항

### 1. 문자 인코딩
- **현재**: UTF-8 (utf8mb4)
- **레거시**: EUC-KR (일부 구버전 코드)
- 마이그레이션: `migration_to_utf8mb4.sql` 참조

### 2. 데이터 타입 선택
| 필드 타입 | 권장 | 주의사항 |
|----------|------|---------|
| VARCHAR | 텍스트 필드 | 길이 명시 필수 |
| DATETIME | 날짜/시간 | timestamp 대신 사용 |
| INT | ID, 수량 | AUTO_INCREMENT 사용 |
| DECIMAL | 금액 | DECIMAL(10,2) 권장 |

### 3. Foreign Key 관계
현재 FK 제약이 없으나, 논리적 관계:
- step13_as.s13_as_center → step2_center.s2_center_id
- step13_as.s13_asid ← step14_as_item.s14_asid
- step14_as_item.s14_model → step15_as_model.s15_amid
- step14_as_item.s14_poor → step16_as_poor.s16_apid
- step14_as_item.s14_asrid → step19_as_result.s19_asrid

### 4. NULL 처리
- 대부분의 필드가 NULL 허용
- 필수 필드: s13_as_center, s13_as_in_how, s13_as_in_date

---

## 수정 및 확장 가이드

### 새로운 AS 필드 추가 시
1. step13_as에 컬럼 추가
2. as_requests.php의 SELECT 문 업데이트
3. 해당 입력 폼 (as_center/write.php 등) 수정
4. 처리 로직 (as_center/write_process.php) 업데이트

### 새로운 상태 값 추가 시
1. s13_as_level 또는 해당 컬럼에 값 정의
2. 상태에 따른 UI 업데이트 (status-badge 클래스 추가)
3. 상태 전환 로직 검증

### 테이블 스키마 변경 시
1. 마이그레이션 파일 생성 (migration_YYYYMMDD.sql)
2. DB_MIGRATION_STEPS.md에 변경사항 기록
3. 테스트 환경에서 검증 후 운영 환경에 적용

---

## 참고 자료

- **설정 파일**: as/@config.php
- **로그인**: as/as_login_process.php
- **메인 페이지**: as/as_requests.php
- **대시보드**: as/dashboard.php
- **상세 처리**: as/as_center*/index.php (as_center, as_center1~5)
- **마이그레이션**: as/migration_to_utf8mb4.sql

---

*마지막 작성: 2025-11-05*
*다음 업데이트: AS 시스템 추가 기능 개발 시*
