# Database Migration Steps

원본 사이트에서 백업한 DB를 새 환경에 업데이트할 때 적용해야 하는 모든 수정 사항을 정리합니다.

⚠️ **중요**: DB에 새로운 수정사항이 생기면 반드시 이 문서에 추가하세요!

---

## 📋 개요

**최종 업데이트**: 2025-11-03

### 적용된 DB 수정 사항 (누적):

- UTF-8 (utf8mb4) 문자 인코딩 통일
- Database 및 테이블 collation 표준화
- 성능 개선을 위한 인덱스 추가
- MySQL 연결 설정 최적화

---

## 🔄 변경사항 추가 가이드

DB에 다음과 같은 수정을 가할 때마다 이 문서를 업데이트하세요:

| 수정 유형           | 예시                                                      | 추가할 섹션                |
| ------------------- | --------------------------------------------------------- | -------------------------- |
| **Column 삭제**     | `ALTER TABLE step20_sell DROP COLUMN s20_bank_check;`     | **5️⃣ 테이블 구조 변경**    |
| **Column 추가**     | `ALTER TABLE step20_sell ADD COLUMN ...;`                 | **5️⃣ 테이블 구조 변경**    |
| **Column 수정**     | `ALTER TABLE step20_sell MODIFY COLUMN s20_as_level INT;` | **5️⃣ 테이블 구조 변경**    |
| **인덱스 추가**     | `CREATE INDEX idx_... ON table(...);`                     | **6️⃣ 추가 인덱스**         |
| **제약조건 추가**   | `ALTER TABLE ... ADD CONSTRAINT ...;`                     | **7️⃣ 데이터 무결성**       |
| **데이터 업데이트** | `UPDATE table SET column = value WHERE ...;`              | **8️⃣ 데이터 마이그레이션** |

---

## 1️⃣ Docker MySQL 설정 수정

**파일**: `.docker/docker-compose.yml`

MySQL 서비스의 command에 다음 파라미터 추가:

```yaml
command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci --init-connect='SET NAMES utf8mb4'
```

**목적**: MySQL 서버 전체의 기본 문자 인코딩을 UTF-8로 설정

---

## 2️⃣ 원본 DB 복구 후 실행할 SQL 스크립트

새 DB를 복구한 후 다음 SQL을 실행합니다:

```sql
-- ===== 1. Database 기본 설정 =====
ALTER DATABASE mic4u CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ===== 2. 모든 테이블을 utf8mb4_unicode_ci로 변환 =====
ALTER TABLE step1_parts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step2_center CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step3_member CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step4_cart CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step5_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step6_order CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step7_center_parts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step8_sendbox CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step9_out CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step10_tax CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step11_member CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step12_sms_sample CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step13_as CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step14_as_item CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step15_as_model CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step16_as_poor CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step17_as_item_cure CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step18_as_cure_cart CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step19_as_result CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step20_sell CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step21_sell_cart CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AS System 테이블들도 변환
ALTER TABLE 2010_admin_member CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE agency CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE banner CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE category1 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE category2 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE counsel CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE member CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mycart CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE myorder CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE notice CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE pds CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE item1 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE market CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Zboard BBS 테이블들
ALTER TABLE zetyx_admin_table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE zetyx_board_default CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE zetyx_board_category_default CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE zetyx_board_comment_default CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE zetyx_member_table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE zetyx_group_table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Analytics 카운터 테이블들
ALTER TABLE AceMTcounter_browser CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE AceMTcounter_display CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE AceMTcounter_ip CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE AceMTcounter_now CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE AceMTcounter_url CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ===== 3. 성능 최적화 인덱스 추가 =====
-- step20_sell 테이블
CREATE INDEX idx_s20_as_level_date ON step20_sell(s20_as_level, s20_sell_in_date DESC);

-- step21_sell_cart 테이블
CREATE INDEX idx_s21_sellid ON step21_sell_cart(s21_sellid);

-- ===== 4. 검증: UTF-8 설정 확인 =====
SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
FROM INFORMATION_SCHEMA.SCHEMATA
WHERE SCHEMA_NAME = 'mic4u';

SELECT TABLE_NAME, TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'mic4u'
ORDER BY TABLE_NAME;
```

---

## 3️⃣ PHP 코드 수정

**파일**: `www/as/mysql_compat.php`

`mysql_connect()` 함수에 다음 코드 추가:

```php
function mysql_connect($server, $username, $password) {
    $link = mysqli_connect($server, $username, $password);
    if (!$link) {
        trigger_error('mysql_connect(): ' . mysqli_connect_error(), E_USER_WARNING);
        return false;
    }
    // UTF-8 문자 인코딩 및 Collation 설정
    mysqli_set_charset($link, 'utf8mb4');
    // SET COLLATION_CONNECTION을 명시적으로 설정
    $charset_query = "SET collation_connection = 'utf8mb4_unicode_ci'";
    if (!mysqli_query($link, $charset_query)) {
        trigger_error('mysql_connect(): Failed to set collation - ' . mysqli_error($link), E_USER_WARNING);
        return false;
    }
    $GLOBALS['___mysql_link'] = $link;
    return $link;
}
```

**목적**: MySQL 연결 시 collation을 명시적으로 utf8mb4_unicode_ci로 설정하여 collation mismatch 에러 방지

---

## 📝 실행 방법

### Docker 환경에서 SQL 실행:

```bash
# 마이그레이션 SQL 파일 준비 (migration.sql이라는 파일에 위의 SQL 스크립트 저장)
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < migration.sql

# 또는 MySQL CLI로 직접 실행
docker exec -it as_mysql mysql -u mic4u_user -pchange_me mic4u
```

그 후 위의 SQL 스크립트를 복사-붙여넣기로 실행합니다.

### 검증:

```bash
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SHOW VARIABLES LIKE 'collation%';"
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'mic4u' LIMIT 10;"
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SHOW INDEXES FROM step20_sell;"
```

---

## ⚠️ 주의사항

1. **원본 DB 백업**: 원본 사이트의 DB를 백업한 후, 필요하면 먼저 테스트 환경에서 마이그레이션을 시행해볼 것
2. **대용량 데이터**: CONVERT TO CHARACTER SET은 시간이 걸릴 수 있음 (테이블 크기에 따라 수분~수십분)
3. **다운타임**: 마이그레이션 중 데이터베이스가 락될 수 있으므로 사용자가 없을 때 실행
4. **인덱스**: 인덱스 생성 후 `ANALYZE TABLE`을 실행하면 쿼리 최적화 향상

---

## 📅 적용 날짜

- **2025-11-03**: UTF-8 변환, 인덱스 추가, collation 통일 작업 완료

---

## 🔗 관련 파일

- `.docker/docker-compose.yml` - MySQL 서버 설정
- `www/as/mysql_compat.php` - PHP MySQL 연결 설정
- `www/as/orders.php` - 자재 판매 관리 페이지 (쿼리 최적화 적용)

---

## 5️⃣ 테이블 구조 변경 (Column 추가/삭제/수정)

### [추후 추가 예정]

Column 삭제, 추가, 수정 등의 변경사항이 있으면 여기에 추가하세요.

**템플릿**:

```sql
-- 2025-MM-DD: Column 삭제/추가/수정 설명
ALTER TABLE table_name [ADD|MODIFY|DROP] COLUMN ...;
```

---

## 6️⃣ 추가 인덱스

### [추후 추가 예정]

성능 최적화를 위해 새로운 인덱스를 추가하면 여기에 기록하세요.

**템플릿**:

```sql
-- 2025-MM-DD: 인덱스 설명
CREATE INDEX idx_name ON table_name(column_name);
```

---

## 7️⃣ 데이터 무결성 (제약조건)

### [추후 추가 예정]

외래키, 유니크 제약조건 등을 추가하면 여기에 기록하세요.

**템플릿**:

```sql
-- 2025-MM-DD: 제약조건 설명
ALTER TABLE table_name ADD CONSTRAINT constraint_name ...;
```

---

## 8️⃣ 데이터 마이그레이션 (UPDATE/DELETE)

### [추후 추가 예정]

기존 데이터를 수정하거나 삭제하는 작업을 하면 여기에 기록하세요.

**템플릿**:

```sql
-- 2025-MM-DD: 데이터 변경 설명 (변경 전 반드시 백업!)
UPDATE table_name SET column = value WHERE condition;
```

---

## 📝 수정사항 추가 체크리스트

DB에 수정을 가할 때마다 다음을 확인하세요:

- [ ] 변경 날짜 기록 (YYYY-MM-DD 형식)
- [ ] SQL 쿼리 정확히 기록
- [ ] 해당하는 섹션(5~8번)에 추가
- [ ] 관련 PHP 파일 코드도 수정했으면 주석으로 표기
- [ ] 실행 후 검증 방법 문서화
- [ ] migration_to_utf8mb4.sql 파일도 함께 업데이트 (필요시)

---

## 🚨 긴급 대응

DB 마이그레이션 중 문제가 발생했을 때:

1. **롤백**: 마이그레이션 전 백업을 복구
2. **로그 확인**: 에러 메시지 전체 기록
3. **테스트**: 테스트 DB에서 먼저 확인 후 실행
4. **검증**: 마이그레이션 후 항상 데이터 무결성 확인
