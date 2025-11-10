# DB 수정 작업 체크리스트

DB에 Column 삭제, 추가, 인덱스 추가 등의 수정을 가할 때마다 이 체크리스트를 따르세요.

---

## ✅ 수정 전 준비

- [ ] **DB 백업 생성** - 변경 전 반드시 백업
  ```bash
  docker exec as_mysql mysqldump -u mic4u_user -pchange_me mic4u > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] **변경 내용 계획서 작성** - 무엇을 왜 바꾸는지 정리
- [ ] **영향받는 테이블/컬럼 파악** - 어떤 테이블이 영향받는지 확인
- [ ] **관련 PHP 코드 확인** - 변경된 컬럼을 사용하는 PHP 코드 찾기
- [ ] **테스트 환경 준비** - 프로덕션 전에 테스트 DB에서 먼저 실행

---

## ✏️ 수정 실행

### Step 1: SQL 작성

```sql
-- YYYY-MM-DD: [변경 설명]
ALTER TABLE [테이블명] [작업];
```

수정 유형별 예시:

**Column 삭제**:
```sql
-- 2025-11-03: 불필요한 컬럼 삭제
ALTER TABLE step20_sell DROP COLUMN s20_old_column;
```

**Column 추가**:
```sql
-- 2025-11-03: 새로운 상태 컬럼 추가
ALTER TABLE step20_sell ADD COLUMN s20_status VARCHAR(50) DEFAULT 'pending';
```

**Column 타입 수정**:
```sql
-- 2025-11-03: 컬럼 타입 변경
ALTER TABLE step20_sell MODIFY COLUMN s20_as_level INT NOT NULL DEFAULT 1;
```

**인덱스 추가**:
```sql
-- 2025-11-03: 검색 성능 향상을 위해 인덱스 추가
CREATE INDEX idx_s20_status ON step20_sell(s20_status);
```

### Step 2: 테스트 환경에서 실행

```bash
# 테스트 DB에서 먼저 실행
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < test_migration.sql

# 결과 확인
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "DESCRIBE step20_sell;"
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SHOW INDEXES FROM step20_sell;"
```

### Step 3: 프로덕션 환경에서 실행

```bash
# 실제 DB에 적용
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < migration.sql

# 결과 확인
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "DESCRIBE step20_sell;"
```

---

## 🔧 PHP 코드 수정

- [ ] **관련 PHP 파일 검색** - grep으로 변경된 컬럼 사용 부분 찾기
  ```bash
  grep -r "s20_old_column" E:\web_shadow\mic4u\www\
  ```

- [ ] **쿼리 수정** - SELECT, INSERT, UPDATE 등에서 컬럼명 변경
  ```php
  // 변경 전
  $result = mysql_query("SELECT s20_old_column FROM step20_sell");

  // 변경 후
  $result = mysql_query("SELECT s20_new_column FROM step20_sell");
  ```

- [ ] **PHP 변수 수정** - 해당 변수들도 함께 업데이트
  ```php
  // 변경 전
  $old_value = $row['s20_old_column'];

  // 변경 후
  $new_value = $row['s20_new_column'];
  ```

- [ ] **테스트** - 브라우저에서 해당 기능이 정상 작동하는지 확인

---

## 📚 문서화

- [ ] **DB_MIGRATION_STEPS.md 업데이트**
  - 해당 섹션(5~8번)에 수정 내용 추가
  - 날짜와 설명 기입 (YYYY-MM-DD 형식)

- [ ] **migration_to_utf8mb4.sql 업데이트** (필요시)
  - 새로운 마이그레이션은 migration_v2.sql 등으로 분리 가능

- [ ] **코드 주석 추가** - PHP 파일에 변경 이유 주석
  ```php
  // 2025-11-03: s20_old_column 제거됨
  // 대신 s20_new_column 사용
  $value = $row['s20_new_column'];
  ```

---

## 🧪 검증

### SQL 검증

```bash
# 테이블 구조 확인
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "DESCRIBE step20_sell;"

# 인덱스 확인
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SHOW INDEXES FROM step20_sell;"

# 데이터 샘플 확인
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SELECT * FROM step20_sell LIMIT 1\G"
```

### PHP 검증

```bash
# 관련 페이지 접속하여 에러 없는지 확인
# 예: http://localhost/as/orders.php

# PHP 에러 로그 확인
docker logs as_php | tail -50
```

### 브라우저 확인

- [ ] 해당 기능이 정상 작동하는지 수동 테스트
- [ ] 브라우저 콘솔에 JavaScript 에러 없는지 확인
- [ ] 특수 문자 한글이 정상 표시되는지 확인

---

## 🔄 롤백 (문제 발생 시)

문제가 발생했을 경우 백업에서 복구:

```bash
# 백업 복구
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < backup_20251103_120000.sql

# 또는 컨테이너 재시작
docker restart as_mysql

# 데이터 확인
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "SELECT COUNT(*) FROM step20_sell;"
```

---

## 📋 예시: Column 삭제 작업 흐름

### 1. 계획
- 불필요한 `s20_bank_check` 컬럼 삭제
- 관련 PHP: `www/as/order_payment.php` 라인 15-20

### 2. 백업
```bash
docker exec as_mysql mysqldump -u mic4u_user -pchange_me mic4u > backup_20251103.sql
```

### 3. SQL 실행
```sql
-- 2025-11-03: 불필요한 s20_bank_check 컬럼 삭제
ALTER TABLE step20_sell DROP COLUMN s20_bank_check;
```

### 4. PHP 수정
```php
// 변경 전
$check_date = $row['s20_bank_check'];

// 변경 후 (코드 삭제 또는 다른 컬럼 사용)
$check_date = $row['s20_bankcheck_date'];  // 다른 컬럼 사용
```

### 5. 문서화
DB_MIGRATION_STEPS.md의 **5️⃣ 테이블 구조 변경** 섹션에 추가:
```sql
-- 2025-11-03: 불필요한 s20_bank_check 컬럼 삭제
ALTER TABLE step20_sell DROP COLUMN s20_bank_check;
```

### 6. 검증
```bash
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u -e "DESCRIBE step20_sell;" | grep bank_check
# (결과가 없어야 함)

# 브라우저에서 관련 페이지 확인
```

---

## 🚀 나중에 원본 DB 업데이트할 때

위의 모든 수정사항이 DB_MIGRATION_STEPS.md에 누적되어 있으므로:

```bash
# 원본 DB 복구
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < original_backup.sql

# 마이그레이션 실행
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < database/migrations/migration_to_utf8mb4.sql

# 추가 수정사항이 있으면 migration_v2.sql 등으로 분리하여 실행
docker exec as_mysql mysql -u mic4u_user -pchange_me mic4u < database/migrations/migration_structural_changes.sql
```

---

## 💡 팁

1. **시간이 오래 걸리는 작업**: CONVERT TO CHARACTER SET이나 대용량 데이터 UPDATE는 시간이 걸릴 수 있으니 여유를 가지고 실행
2. **안전한 수정**: 항상 백업을 먼저 생성하고, 테스트 환경에서 먼저 시행
3. **문서화 습관**: 수정할 때마다 즉시 문서화하면 나중에 참고하기 쉬움
4. **버전 관리**: SQL 파일을 여러 버전으로 분리하면 각 단계를 추적할 수 있음
   - migration_to_utf8mb4.sql (UTF-8 변환)
   - migration_v2_structural.sql (테이블 구조 변경)
   - migration_v3_performance.sql (성능 최적화)

---

**마지막 업데이트**: 2025-11-03
