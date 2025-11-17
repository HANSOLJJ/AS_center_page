# 포팅 전략 가이드

**전략명:** 풀 패키지 업로드 방식 (Full Package Upload)
**대상 서버:** dcom.co.kr (Cafe24 공유 호스팅)
**예상 소요 시간:** 3-4시간

---

## 🎯 전략 개요

### 기본 방침
Docker를 사용할 수 없는 Cafe24 공유 호스팅 환경에서, 로컬에서 준비한 모든 파일(소스 + vendor)을 서버에 직접 업로드하여 배포합니다.

### 핵심 원칙
1. **Composer 불필요**: vendor 디렉토리를 통째로 업로드
2. **Docker 미사용**: 서버의 기존 MariaDB 활용
3. **최소 수정**: DB 연결 정보만 변경
4. **WordPress 분리**: 기존 WordPress와 독립적으로 운영

---

## 📦 배포 파일 구조

### 업로드 대상
```
mic4u_as/
├── as/                         # AS 시스템 (필수)
│   ├── mysql_compat.php       # PHP 7 호환 레이어
│   ├── dashboard.php
│   ├── login.php
│   ├── login_process.php
│   ├── logout.php
│   ├── index.php
│   ├── stat/                  # 통계/리포트
│   │   ├── statistics.php
│   │   ├── export_sales_report.php
│   │   ├── export_monthly_report.php
│   │   └── export_as_report.php
│   ├── orders/                # 주문 관리
│   │   ├── orders.php
│   │   ├── order_edit.php
│   │   ├── order_handler.php
│   │   ├── order_receipt.php
│   │   └── order_payment.php
│   ├── parts/                 # 자재 관리
│   │   ├── parts.php
│   │   ├── parts_add.php
│   │   ├── parts_edit.php
│   │   ├── category_add.php
│   │   └── category_edit.php
│   ├── products/              # 제품 관리
│   │   ├── products.php
│   │   ├── product_add.php
│   │   ├── product_edit.php
│   │   ├── poor_add.php
│   │   ├── poor_edit.php
│   │   ├── result_add.php
│   │   └── result_edit.php
│   ├── customers/             # 고객 관리
│   │   ├── members.php
│   │   ├── member_add.php
│   │   └── member_edit.php
│   └── as_task/               # AS 업무
│       ├── as_requests.php
│       ├── as_repair.php
│       ├── as_receipt.php
│       ├── as_request_handler.php
│       └── as_repair_handler.php
│
├── vendor/                     # Composer 패키지 (필수)
│   ├── autoload.php
│   ├── composer/
│   ├── phpoffice/
│   │   └── phpspreadsheet/    # Excel 생성 라이브러리
│   ├── ezyang/
│   ├── maennchen/
│   ├── markbaker/
│   ├── myclabs/
│   ├── psr/
│   └── symfony/
│
├── composer.json               # 의존성 정보 (선택)
└── composer.lock              # 버전 잠금 (선택)
```

### 파일 크기
```
as/ 디렉토리:     ~10MB
vendor/ 디렉토리: 8.9MB
총 크기:          ~20MB
```

### 제외 항목 (업로드 안 함)
```
❌ .git/
❌ node_modules/
❌ .vscode/
❌ .idea/
❌ *.log
❌ .env
❌ DOCS/ (선택)
❌ tests/ (있다면)
```

---

## 🚀 Phase 1: 로컬 환경 준비

### Step 1-1: 파일 정리
```bash
cd E:/web_shadow/mic4u/www

# 업로드할 파일만 확인
ls -la as/
ls -la vendor/
```

### Step 1-2: 압축 파일 생성 (권장)
```bash
# 방법 1: tar.gz 압축 (리눅스/맥)
tar -czf mic4u_as.tar.gz as/ vendor/ composer.json composer.lock

# 방법 2: zip 압축 (Windows)
# 수동으로 as/ 와 vendor/ 폴더를 zip으로 압축
```

### Step 1-3: DB 연결 정보 확인
로컬 설정 확인:
```php
// 현재 (로컬)
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);
```

서버 설정 준비:
```php
// 변경 후 (서버)
$connect = mysql_connect('localhost', 'dcom2000', 'Basserd2@@');
mysql_select_db('dcom2000', $connect);  // 또는 새로 생성한 DB
```

---

## 🗄️ Phase 2: 데이터베이스 준비

### Step 2-1: 데이터베이스 선택

#### 옵션 A: 새 데이터베이스 생성 (권장)
```sql
-- SSH로 서버 접속 후
mysql -u dcom2000 -p'Basserd2@@'

-- 새 DB 생성
CREATE DATABASE mic4u_as DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 권한 확인
SHOW GRANTS FOR 'dcom2000'@'localhost';

-- 사용
USE mic4u_as;
```

**장점:**
- WordPress와 완전 분리
- 테이블 이름 충돌 없음
- 독립적인 백업/복원

**단점:**
- DB 생성 권한 필요 (있음)

#### 옵션 B: 기존 DB 사용
```sql
-- 기존 dcom2000 DB 사용
USE dcom2000;

-- 테이블 prefix로 구분
-- 예: as_s5_parts, as_s5_orders, as_members 등
```

**장점:**
- 추가 설정 불필요
- 즉시 사용 가능

**단점:**
- WordPress 테이블과 혼재
- 백업 시 분리 어려움

### Step 2-2: 테이블 스키마 생성

로컬에서 스키마 내보내기:
```bash
# 로컬 Docker 컨테이너에서
docker exec -it mic4u-mysql mysqldump -u mic4u_user -pchange_me mic4u \
  --no-data --skip-add-drop-table --skip-comments > schema.sql
```

또는 프로젝트의 기존 스키마 파일 사용:
```bash
# 프로젝트에 schema.sql 파일이 있다면
scp schema.sql dcom2000@dcom.co.kr:~/
```

서버에서 스키마 적용:
```bash
# SSH 접속 후
mysql -u dcom2000 -p'Basserd2@@' mic4u_as < schema.sql
```

### Step 2-3: 테이블 확인
```sql
mysql -u dcom2000 -p'Basserd2@@' mic4u_as -e "SHOW TABLES;"
```

---

## 📤 Phase 3: 파일 업로드

### 방법 1: FTP/SFTP (GUI)

**FileZilla 사용 예시:**
```
호스트: sftp://dcom.co.kr
포트: 22
프로토콜: SFTP
사용자명: dcom2000
비밀번호: Noblein12!!

업로드 대상:
로컬: E:/web_shadow/mic4u/www/as/
서버: /home/hosting_users/dcom2000/www/mic4u_as/as/

로컬: E:/web_shadow/mic4u/www/vendor/
서버: /home/hosting_users/dcom2000/www/mic4u_as/vendor/
```

**예상 시간:**
- as/ 업로드: 5분
- vendor/ 업로드: 10분

### 방법 2: SCP (명령줄)

```bash
# 로컬에서 실행
scp -r E:/web_shadow/mic4u/www/as dcom2000@dcom.co.kr:~/www/mic4u_as/
scp -r E:/web_shadow/mic4u/www/vendor dcom2000@dcom.co.kr:~/www/mic4u_as/
```

### 방법 3: 압축 후 업로드 (권장)

**로컬에서:**
```bash
tar -czf mic4u_as.tar.gz as/ vendor/ composer.json composer.lock
```

**Claude Code SSH MCP로 업로드:**
```bash
# SSH MCP 도구 사용
# 로컬 파일을 서버로 업로드
```

**서버에서 압축 해제:**
```bash
# SSH 접속
cd ~/www/
mkdir -p mic4u_as
cd mic4u_as
tar -xzf ~/mic4u_as.tar.gz
```

---

## ⚙️ Phase 4: 설정 파일 수정

### Step 4-1: DB 연결 정보 수정

**수정 대상 파일 (43개):**
```bash
# 서버에서 자동 치환
cd ~/www/mic4u_as/as/

# 방법 1: sed 사용
find . -name "*.php" -type f -exec sed -i \
  "s/mysql_connect('mysql', 'mic4u_user', 'change_me')/mysql_connect('localhost', 'dcom2000', 'Basserd2@@')/g" {} \;

find . -name "*.php" -type f -exec sed -i \
  "s/mysql_select_db('mic4u'/mysql_select_db('mic4u_as'/g" {} \;
```

**또는 수동으로 주요 파일만 수정:**
```php
// 각 PHP 파일에서 찾아서 수정
// 이전:
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

// 이후:
$connect = mysql_connect('localhost', 'dcom2000', 'Basserd2@@');
mysql_select_db('mic4u_as', $connect);
```

**주요 수정 파일 목록:**
```
as/dashboard.php
as/login_process.php
as/stat/statistics.php
as/stat/export_sales_report.php
as/stat/export_monthly_report.php
as/stat/export_as_report.php
as/orders/orders.php
as/orders/order_edit.php
as/orders/order_handler.php
as/orders/order_payment.php
as/parts/parts.php
as/parts/parts_add.php
as/parts/parts_edit.php
as/parts/category_add.php
as/parts/category_edit.php
as/products/products.php
as/products/product_add.php
as/products/product_edit.php
as/products/poor_add.php
as/products/poor_edit.php
as/products/result_add.php
as/products/result_edit.php
as/customers/members.php
as/customers/member_add.php
as/customers/member_edit.php
as/as_task/as_requests.php
as/as_task/as_repair.php
as/as_task/as_receipt.php
as/as_task/as_request_handler.php
as/as_task/as_repair_handler.php
```

### Step 4-2: 파일 권한 설정
```bash
# 서버에서 실행
cd ~/www/mic4u_as/

# 디렉토리 권한
find . -type d -exec chmod 755 {} \;

# PHP 파일 권한
find . -type f -name "*.php" -exec chmod 644 {} \;

# vendor 권한
chmod -R 755 vendor/
```

### Step 4-3: .htaccess 설정 (선택)

```bash
# as/.htaccess 생성
cat > ~/www/mic4u_as/as/.htaccess << 'EOF'
# PHP 설정
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value memory_limit 128M

# 에러 표시 (개발 중에만)
# php_flag display_errors On

# 인덱스 파일
DirectoryIndex login.php index.php

# 보안: 특정 파일 접근 차단
<Files "mysql_compat.php">
    Require all denied
</Files>
EOF
```

---

## 🧪 Phase 5: 테스트

### Step 5-1: 기본 접속 테스트
```
URL: http://dcom.co.kr/mic4u_as/as/login.php
또는: https://dcom.co.kr/mic4u_as/as/login.php

예상 결과: 로그인 페이지 표시
```

### Step 5-2: DB 연결 테스트

**임시 테스트 파일 생성:**
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

```
URL: http://dcom.co.kr/mic4u_as/as/test_db.php
예상 결과: 테이블 목록 표시
```

### Step 5-3: PhpSpreadsheet 테스트

**임시 테스트 파일 생성:**
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

    echo "✓ PhpSpreadsheet 로드 성공!<br>";
    echo "✓ Excel 파일 생성 가능<br>";
} catch (Exception $e) {
    echo "✗ 에러: " . $e->getMessage();
}
?>
EOF
```

```
URL: http://dcom.co.kr/mic4u_as/as/test_excel.php
예상 결과: "PhpSpreadsheet 로드 성공!" 메시지
```

### Step 5-4: 로그인 테스트
```
1. 로그인 페이지 접속
2. 테스트 계정으로 로그인
3. 대시보드 접속 확인
4. 각 메뉴 접속 확인
```

### Step 5-5: CRUD 기능 테스트
```
□ 자재 관리: 추가/수정/삭제
□ 제품 관리: 추가/수정/삭제
□ 고객 관리: 추가/수정/삭제
□ 주문 관리: 추가/수정/삭제
□ AS 업무: 추가/수정/삭제
□ 통계: Excel 다운로드
```

---

## 🛠️ 문제 해결

### 문제 1: "Cannot connect to database"
```
원인: DB 연결 정보 오류
해결:
1. mysql_compat.php 확인
2. DB 비밀번호 확인 (Basserd2@@)
3. DB 이름 확인 (mic4u_as 또는 dcom2000)
```

### 문제 2: "Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found"
```
원인: vendor 디렉토리 누락
해결:
1. vendor/ 디렉토리 업로드 확인
2. vendor/autoload.php 존재 확인
3. require_once 경로 확인
```

### 문제 3: "Permission denied"
```
원인: 파일 권한 문제
해결:
chmod -R 755 ~/www/mic4u_as/as/
chmod -R 644 ~/www/mic4u_as/as/**/*.php
```

### 문제 4: 세션 에러
```
원인: 세션 저장 경로 권한
해결:
session_save_path('/tmp'); 또는
php.ini 설정 확인
```

### 문제 5: "500 Internal Server Error"
```
원인: PHP 문법 오류 또는 설정 문제
해결:
1. error_log 확인
2. .htaccess 문제 확인
3. PHP 버전 호환성 확인
```

---

## 📊 배포 타임라인

| 단계 | 작업 | 예상 시간 | 누적 시간 |
|------|------|----------|----------|
| Phase 1 | 로컬 환경 준비 | 10분 | 10분 |
| Phase 2 | DB 준비 | 30분 | 40분 |
| Phase 3 | 파일 업로드 | 15분 | 55분 |
| Phase 4 | 설정 수정 | 60분 | 115분 |
| Phase 5 | 테스트 | 60분 | 175분 |
| 버퍼 | 예상 외 문제 | 30분 | 205분 |
| **총계** | | **~3.5시간** | |

---

## ✅ 배포 완료 체크리스트

```
□ vendor 디렉토리 업로드 완료
□ as 디렉토리 업로드 완료
□ DB 연결 정보 수정 완료 (43개 파일)
□ 파일 권한 설정 완료
□ DB 스키마 생성 완료
□ 테스트 파일로 DB 연결 확인
□ 테스트 파일로 PhpSpreadsheet 확인
□ 로그인 테스트 성공
□ 각 메뉴 접속 확인
□ CRUD 기능 테스트 완료
□ Excel 다운로드 테스트 완료
□ 테스트 파일 삭제 (test_db.php, test_excel.php)
```

---

**다음 단계:**
- [SSL 분석](03_SSL_ANALYSIS.md) - 보안 설정
- [배포 체크리스트](05_DEPLOYMENT_CHECKLIST.md) - 상세 체크리스트
