# 배포 체크리스트

**대상 서버:** dcom.co.kr (Cafe24 공유 호스팅)
**배포 전략:** 풀 패키지 업로드
**예상 소요 시간:** 3-4시간

---

## 📋 사전 준비 (1일 전)

### 환경 확인
```
□ 서버 SSH 접속 테스트 완료
  - 호스트: dcom.co.kr
  - 사용자: dcom2000
  - 비밀번호: Noblein12!!

□ 서버 환경 확인 완료
  - PHP 7.0 확인
  - MariaDB 10.1.13 확인
  - 필수 PHP 확장 확인

□ 로컬 환경 정상 작동 확인
  - 로컬 테스트 완료
  - 모든 기능 정상 작동
  - Excel 다운로드 테스트 완료
```

### 파일 준비
```
□ as/ 디렉토리 최신 버전 확인
□ vendor/ 디렉토리 확인 (8.9MB)
□ composer.json, composer.lock 확인
□ 불필요한 파일 제거 (.git/, node_modules/)
□ 압축 파일 생성 (mic4u_as.tar.gz)
```

### 데이터베이스 준비
```
□ DB 스키마 파일 준비 (schema.sql)
□ 초기 데이터 파일 준비 (있다면)
□ DB 연결 정보 확인
  - 호스트: localhost
  - 사용자: dcom2000
  - 비밀번호: Basserd2@@
  - DB명: mic4u_as (또는 dcom2000)
```

### 백업
```
□ 로컬 프로젝트 백업
□ 서버 기존 파일 백업 (WordPress)
□ 서버 데이터베이스 백업
```

---

## 🚀 Phase 1: 데이터베이스 설정 (30분)

### Step 1.1: SSH 접속
```bash
ssh dcom2000@dcom.co.kr
# 비밀번호: Noblein12!!

□ SSH 접속 성공
□ 홈 디렉토리 확인: pwd
```

### Step 1.2: 데이터베이스 생성 (옵션 A)
```bash
mysql -u dcom2000 -p'Basserd2@@'
```

```sql
-- 새 DB 생성 (권장)
CREATE DATABASE mic4u_as
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- 확인
SHOW DATABASES;
USE mic4u_as;

-- 종료
EXIT;
```

```
□ mic4u_as 데이터베이스 생성 완료
```

### Step 1.3: 스키마 적용
```bash
# 스키마 파일 업로드 (로컬에서)
scp schema.sql dcom2000@dcom.co.kr:~/

# 서버에서 적용
cd ~
mysql -u dcom2000 -p'Basserd2@@' mic4u_as < schema.sql
```

```
□ 테이블 생성 완료
```

### Step 1.4: 테이블 확인
```sql
mysql -u dcom2000 -p'Basserd2@@' mic4u_as -e "SHOW TABLES;"
```

**예상 출력:**
```
s5_parts
s5_categories
s5_products
s5_poor_types
s5_result_types
s5_orders
s5_order_parts
members
as_requests
as_receipts
as_repairs
as_repair_parts
```

```
□ 모든 테이블 정상 생성 확인
```

---

## 📤 Phase 2: 파일 업로드 (15-30분)

### Step 2.1: 서버 디렉토리 생성
```bash
# SSH 접속 상태에서
cd ~/www/
mkdir -p mic4u_as
cd mic4u_as
```

```
□ ~/www/mic4u_as/ 디렉토리 생성 완료
```

### Step 2.2: 파일 업로드

#### 방법 A: 압축 파일 업로드 (권장)
```bash
# 로컬에서 (Git Bash)
cd E:/web_shadow/mic4u/www
tar -czf mic4u_as.tar.gz as/ vendor/ composer.json composer.lock

# 서버로 업로드
scp mic4u_as.tar.gz dcom2000@dcom.co.kr:~/www/mic4u_as/

# 서버에서 압축 해제
cd ~/www/mic4u_as/
tar -xzf mic4u_as.tar.gz

# 압축 파일 삭제
rm mic4u_as.tar.gz
```

```
□ 압축 파일 생성 완료
□ 서버 업로드 완료
□ 압축 해제 완료
```

#### 방법 B: 직접 업로드 (FileZilla)
```
로컬: E:/web_shadow/mic4u/www/as/
서버: /home/hosting_users/dcom2000/www/mic4u_as/as/

로컬: E:/web_shadow/mic4u/www/vendor/
서버: /home/hosting_users/dcom2000/www/mic4u_as/vendor/
```

```
□ as/ 디렉토리 업로드 완료
□ vendor/ 디렉토리 업로드 완료
```

### Step 2.3: 파일 확인
```bash
cd ~/www/mic4u_as/

# 디렉토리 구조 확인
ls -la

# as/ 디렉토리 확인
ls -la as/

# vendor/ 디렉토리 확인
ls -la vendor/
```

**예상 출력:**
```
as/
vendor/
composer.json
composer.lock
```

```
□ 모든 파일 업로드 확인
```

### Step 2.4: 파일 권한 설정
```bash
cd ~/www/mic4u_as/

# 디렉토리 권한
find . -type d -exec chmod 755 {} \;

# PHP 파일 권한
find . -type f -name "*.php" -exec chmod 644 {} \;

# vendor 권한
chmod -R 755 vendor/
```

```
□ 파일 권한 설정 완료
```

---

## ⚙️ Phase 3: 설정 수정 (60분)

### Step 3.1: DB 연결 정보 일괄 수정 (자동)
```bash
cd ~/www/mic4u_as/as/

# mysql_connect 변경
find . -name "*.php" -type f -exec sed -i \
  "s/mysql_connect('mysql', 'mic4u_user', 'change_me')/mysql_connect('localhost', 'dcom2000', 'Basserd2@@')/g" {} \;

# mysql_select_db 변경
find . -name "*.php" -type f -exec sed -i \
  "s/mysql_select_db('mic4u'/mysql_select_db('mic4u_as'/g" {} \;

# 확인
grep -r "mysql_connect" . | head -5
```

```
□ DB 연결 정보 일괄 수정 완료
□ 수정 결과 확인
```

### Step 3.2: 주요 파일 수동 확인

**확인 대상 파일:**
```bash
# dashboard.php 확인
head -20 ~/www/mic4u_as/as/dashboard.php | grep mysql

# login_process.php 확인
head -20 ~/www/mic4u_as/as/login_process.php | grep mysql

# statistics.php 확인
head -20 ~/www/mic4u_as/as/stat/statistics.php | grep mysql
```

**예상 출력:**
```php
$connect = mysql_connect('localhost', 'dcom2000', 'Basserd2@@');
mysql_select_db('mic4u_as', $connect);
```

```
□ dashboard.php 확인
□ login_process.php 확인
□ statistics.php 확인
□ 기타 주요 파일 확인
```

### Step 3.3: .htaccess 설정 (선택)
```bash
cat > ~/www/mic4u_as/as/.htaccess << 'EOF'
# PHP 설정
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value memory_limit 128M

# 에러 표시 (개발 시에만)
# php_flag display_errors On

# 인덱스 파일
DirectoryIndex login.php index.php

# 보안: 특정 파일 접근 차단
<Files "mysql_compat.php">
    Require all denied
</Files>

<Files "test_*.php">
    Require all denied
</Files>
EOF
```

```
□ .htaccess 파일 생성 완료
```

---

## 🧪 Phase 4: 기본 테스트 (30분)

### Step 4.1: vendor 로드 테스트
```bash
cat > ~/www/mic4u_as/as/test_vendor.php << 'EOF'
<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists('Composer\Autoload\ClassLoader')) {
    echo "✓ Composer autoloader 로드 성공<br>";
} else {
    echo "✗ Composer autoloader 로드 실패<br>";
}

if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo "✓ PhpSpreadsheet 클래스 로드 성공<br>";
} else {
    echo "✗ PhpSpreadsheet 클래스 로드 실패<br>";
}
?>
EOF
```

**브라우저 접속:**
```
URL: http://dcom.co.kr/mic4u_as/as/test_vendor.php
또는: https://dcom.co.kr/mic4u_as/as/test_vendor.php
```

**예상 결과:**
```
✓ Composer autoloader 로드 성공
✓ PhpSpreadsheet 클래스 로드 성공
```

```
□ vendor 로드 테스트 통과
```

### Step 4.2: DB 연결 테스트
```bash
cat > ~/www/mic4u_as/as/test_db.php << 'EOF'
<?php
require_once 'mysql_compat.php';

$connect = mysql_connect('localhost', 'dcom2000', 'Basserd2@@');
if ($connect) {
    echo "✓ DB 연결 성공!<br>";

    mysql_select_db('mic4u_as', $connect);
    $result = mysql_query("SHOW TABLES");

    echo "<h3>테이블 목록:</h3>";
    while ($row = mysql_fetch_array($result)) {
        echo "- " . $row[0] . "<br>";
    }

    mysql_close($connect);
} else {
    echo "✗ DB 연결 실패: " . mysql_error();
}
?>
EOF
```

**브라우저 접속:**
```
URL: http://dcom.co.kr/mic4u_as/as/test_db.php
```

**예상 결과:**
```
✓ DB 연결 성공!

테이블 목록:
- s5_parts
- s5_categories
- ...
```

```
□ DB 연결 테스트 통과
```

### Step 4.3: Excel 생성 테스트
```bash
cat > ~/www/mic4u_as/as/test_excel.php << 'EOF'
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Hello World!');

    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    if (file_exists($tempFile) && filesize($tempFile) > 0) {
        echo "✓ Excel 파일 생성 성공 (" . filesize($tempFile) . " bytes)<br>";
        unlink($tempFile);
    } else {
        echo "✗ Excel 파일 생성 실패<br>";
    }
} catch (Exception $e) {
    echo "✗ 에러: " . $e->getMessage();
}
?>
EOF
```

**브라우저 접속:**
```
URL: http://dcom.co.kr/mic4u_as/as/test_excel.php
```

**예상 결과:**
```
✓ Excel 파일 생성 성공 (~5000 bytes)
```

```
□ Excel 생성 테스트 통과
```

### Step 4.4: 로그인 페이지 테스트
**브라우저 접속:**
```
URL: http://dcom.co.kr/mic4u_as/as/login.php
또는: https://dcom.co.kr/mic4u_as/as/login.php
```

**확인 사항:**
```
□ 로그인 페이지 정상 표시
□ CSS 스타일 정상 적용
□ 에러 메시지 없음
```

---

## ✅ Phase 5: 기능 테스트 (60분)

### Step 5.1: 로그인 테스트
```
1. 로그인 페이지 접속
   URL: http://dcom.co.kr/mic4u_as/as/login.php

2. 테스트 계정으로 로그인
   - 계정 정보 입력
   - "로그인" 버튼 클릭

3. 대시보드 접속 확인
   - 자동 리다이렉트 확인
   - 대시보드 페이지 표시 확인
```

```
□ 로그인 성공
□ 대시보드 접속 성공
□ 세션 정상 작동
```

### Step 5.2: 메뉴 접근 테스트
```
각 메뉴 순차적으로 접속:

□ 자재 관리 (parts.php)
  - Tab1: AS 자재 관리
  - Tab2: 자재 카테고리 관리

□ 제품 관리 (products.php)
  - Tab1: 모델 관리
  - Tab2: 불량증상 타입
  - Tab3: AS결과 타입

□ 고객 관리 (members.php)

□ 주문 관리 (orders.php)
  - Tab1: 소모품판매
  - Tab2: 구매신청

□ AS 업무 (as_requests.php)
  - Tab1: 접수 (미완료)
  - Tab2: 완료

□ 통계 (statistics.php)
```

```
□ 모든 메뉴 정상 접근
□ 페이지 로딩 정상
□ 에러 없음
```

### Step 5.3: CRUD 기능 테스트

#### 자재 관리 테스트
```
1. 자재 추가
   - "자재 추가" 버튼 클릭
   - 정보 입력 및 저장
   - 목록에서 확인

2. 자재 수정
   - 목록에서 "수정" 클릭
   - 정보 수정 및 저장
   - 변경사항 확인

3. 자재 삭제
   - 목록에서 "삭제" 클릭
   - 확인 메시지
   - 삭제 완료 확인
```

```
□ 자재 추가 성공
□ 자재 수정 성공
□ 자재 삭제 성공
```

#### 제품 관리 테스트
```
1. 모델 추가
2. 모델 수정
3. 모델 삭제
```

```
□ 제품 추가 성공
□ 제품 수정 성공
□ 제품 삭제 성공
```

#### 고객 관리 테스트
```
1. 고객 추가
2. 고객 수정
3. 고객 삭제
```

```
□ 고객 추가 성공
□ 고객 수정 성공
□ 고객 삭제 성공
```

### Step 5.4: Excel 다운로드 테스트
```
1. 통계 페이지 접속
2. "판매 리포트 다운로드" 클릭
3. Excel 파일 다운로드
4. Excel 파일 열기
5. 데이터 확인
```

```
□ Excel 다운로드 성공
□ 파일 정상 열림
□ 데이터 정상 표시
```

---

## 🧹 Phase 6: 정리 (10분)

### Step 6.1: 테스트 파일 삭제
```bash
cd ~/www/mic4u_as/as/
rm -f test_vendor.php test_db.php test_excel.php
```

```
□ 테스트 파일 삭제 완료
```

### Step 6.2: .htaccess 보안 강화
```bash
# 테스트 파일 접근 차단 확인
cat ~/www/mic4u_as/as/.htaccess
```

```
□ 보안 설정 확인
```

### Step 6.3: 에러 로그 확인
```bash
# PHP 에러 로그 확인 (있다면)
tail -50 ~/www/error_log
```

```
□ 에러 로그 확인
□ 심각한 에러 없음
```

---

## 📊 최종 검증

### 전체 기능 재확인
```
□ 로그인/로그아웃
□ 자재 관리 (추가/수정/삭제)
□ 제품 관리 (추가/수정/삭제)
□ 고객 관리 (추가/수정/삭제)
□ 주문 관리 (조회/수정/결제)
□ AS 업무 (접수/수리/완료)
□ 통계 (조회/Excel 다운로드)
```

### 성능 확인
```
□ 페이지 로딩 속도 정상
□ Excel 생성 시간 정상 (10초 이내)
□ DB 쿼리 응답 정상
```

### 보안 확인
```
□ 로그인 필요 페이지 보호
□ 세션 타임아웃 작동
□ SQL Injection 방어 (기본)
```

---

## 🛠️ 문제 해결 가이드

### 문제 1: "Cannot connect to database"
```
원인:
- DB 연결 정보 오류
- DB 생성 안 됨

해결:
1. DB 연결 정보 재확인
   mysql -u dcom2000 -p'Basserd2@@'

2. DB 존재 확인
   SHOW DATABASES;

3. test_db.php로 테스트
```

### 문제 2: "Class not found"
```
원인:
- vendor 디렉토리 누락
- autoload.php 경로 오류

해결:
1. vendor 디렉토리 확인
   ls -la ~/www/mic4u_as/vendor/

2. autoload.php 확인
   ls -la ~/www/mic4u_as/vendor/autoload.php

3. test_vendor.php로 테스트
```

### 문제 3: "Permission denied"
```
원인:
- 파일 권한 문제

해결:
chmod -R 755 ~/www/mic4u_as/as/
chmod -R 644 ~/www/mic4u_as/as/**/*.php
```

### 문제 4: "500 Internal Server Error"
```
원인:
- PHP 문법 오류
- .htaccess 설정 오류

해결:
1. error_log 확인
   tail -50 ~/www/error_log

2. .htaccess 임시 제거
   mv .htaccess .htaccess.bak

3. PHP 에러 표시 활성화
   php_flag display_errors On
```

### 문제 5: 세션 에러
```
원인:
- 세션 저장 경로 권한

해결:
1. 세션 경로 확인
   php -i | grep session.save_path

2. 권한 확인 및 수정
```

---

## 🔄 롤백 절차

만약 배포 실패 시:

### 긴급 롤백
```bash
# 서버에서
cd ~/www/
mv mic4u_as mic4u_as.failed
```

### DB 롤백
```sql
-- DB 삭제
DROP DATABASE IF EXISTS mic4u_as;
```

### 백업 복원
```bash
# 백업에서 복원
```

---

## ✅ 배포 완료 체크리스트

### 필수 항목
```
□ 데이터베이스 생성 및 스키마 적용
□ 파일 업로드 (as/ + vendor/)
□ DB 연결 정보 수정 (43개 파일)
□ 파일 권한 설정
□ test_vendor.php 테스트 통과
□ test_db.php 테스트 통과
□ test_excel.php 테스트 통과
□ 로그인 테스트 성공
□ 각 메뉴 접근 확인
□ CRUD 기능 테스트 완료
□ Excel 다운로드 테스트 완료
□ 테스트 파일 삭제
```

### 선택 항목
```
□ HTTPS 강제 설정
□ 세션 보안 강화
□ .htaccess 보안 설정
□ 에러 로그 모니터링 설정
```

---

## 📞 배포 후 모니터링

### 첫 주 모니터링
```
□ 매일 에러 로그 확인
□ 사용자 피드백 수집
□ 성능 모니터링
□ 보안 이슈 확인
```

### 정기 점검
```
□ 주간 백업 확인
□ DB 최적화
□ 세션 파일 정리
□ 로그 파일 정리
```

---

## 🎉 배포 완료!

**배포 일시:** _________
**배포자:** _________
**소요 시간:** _________

**다음 단계:**
- 사용자 교육
- 모니터링 시작
- 피드백 수집
- 개선 계획 수립

---

**관련 문서:**
- [서버 환경 분석](01_SERVER_ENVIRONMENT.md)
- [포팅 전략 가이드](02_PORTING_STRATEGY.md)
- [SSL 분석](03_SSL_ANALYSIS.md)
- [Composer 의존성 분석](04_COMPOSER_DEPENDENCIES.md)
