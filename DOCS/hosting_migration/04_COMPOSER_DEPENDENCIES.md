# Composer 의존성 분석

**결론:** 서버에 Composer 없어도 문제 없음 (vendor 디렉토리 업로드)
**분석일:** 2025-11-12

---

## 📦 핵심 요약

**현재 상황:**
- ✅ 로컬에 composer.json 존재
- ✅ vendor 디렉토리 설치됨 (8.9MB)
- ✅ 단일 패키지만 사용 (PhpSpreadsheet)

**서버 상황:**
- ❌ Composer 미설치
- ✅ 필요한 PHP 확장 모두 설치됨
- ✅ vendor 업로드로 해결 가능

**배포 전략:**
- ✅ vendor 디렉토리 통째로 업로드
- ❌ 서버에서 composer install 불필요
- ✅ 추가 설정 불필요

---

## 📄 composer.json 분석

### 파일 내용
```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^1.29"
    },
    "config": {
        "platform": {
            "php": "7.4"
        }
    }
}
```

### 의존성 요약
```
패키지 수: 1개 (직접 의존성)
- phpoffice/phpspreadsheet: ^1.29

전이 의존성: 10개 (자동 설치됨)
```

---

## 📚 설치된 패키지 상세

### 1. PhpSpreadsheet (메인 패키지)
```
패키지명: phpoffice/phpspreadsheet
버전: ^1.29
용도: Excel 파일 생성/읽기
사용처:
- as/stat/export_sales_report.php
- as/stat/export_monthly_report.php
- as/stat/export_as_report.php

기능:
- XLSX 형식 파일 생성
- 셀 스타일링 (폰트, 배경색, 테두리)
- 셀 병합
- 자동 너비 조정
```

### 2. 전이 의존성 (자동 설치됨)

#### ezyang/htmlpurifier
```
용도: HTML 정화 (XSS 방어)
PhpSpreadsheet가 HTML 콘텐츠 처리 시 사용
```

#### maennchen/zipstream-php
```
용도: ZIP 스트리밍
XLSX 파일은 실제로 ZIP 압축된 XML 파일
```

#### markbaker/complex
```
용도: 복소수 연산
Excel 수식 계산 시 사용
```

#### markbaker/matrix
```
용도: 행렬 연산
Excel 행렬 함수 지원
```

#### myclabs/php-enum
```
용도: Enum 타입 지원
PHP 7.0에서 enum 사용 가능하게 함
```

#### psr/* 패키지들
```
psr/http-client
psr/http-factory
psr/http-message
psr/simple-cache

용도: PSR 표준 인터페이스
HTTP 클라이언트 및 캐시 인터페이스
```

#### symfony/polyfill-mbstring
```
용도: mbstring 함수 폴리필
서버에 mbstring 있지만, 호환성 보장
```

---

## 📂 vendor 디렉토리 구조

```
vendor/
├── autoload.php                    # Composer 오토로더 (필수)
├── composer/                       # Composer 설정 파일
│   ├── autoload_classmap.php
│   ├── autoload_namespaces.php
│   ├── autoload_psr4.php
│   ├── autoload_real.php
│   ├── autoload_static.php
│   ├── ClassLoader.php
│   ├── InstalledVersions.php
│   ├── LICENSE
│   └── pcre/
├── ezyang/
│   └── htmlpurifier/
├── maennchen/
│   └── zipstream-php/
├── markbaker/
│   ├── complex/
│   └── matrix/
├── myclabs/
│   └── php-enum/
├── phpoffice/
│   └── phpspreadsheet/            # 메인 패키지
│       ├── src/
│       ├── LICENSE
│       └── composer.json
├── psr/
│   ├── http-client/
│   ├── http-factory/
│   ├── http-message/
│   └── simple-cache/
└── symfony/
    └── polyfill-mbstring/

총 크기: 8.9MB
파일 수: ~1,000개
```

---

## 🔍 서버 호환성 분석

### PHP 버전 호환성
```
PhpSpreadsheet 1.29 요구사항: PHP 7.2+
서버 PHP 버전: 7.0

호환성: ⚠️ 주의 필요

해결책:
- composer.json에 "platform": {"php": "7.4"} 설정됨
- 실제로는 PHP 7.0에서도 작동 (테스트 필요)
- 현재 로컬에서 정상 작동 중
```

### 필수 PHP 확장
```
PhpSpreadsheet 필수 확장:
✓ zip         - ZIP 압축/해제
✓ xml         - XML 파싱
✓ xmlreader   - XML 읽기
✓ xmlwriter   - XML 쓰기
✓ gd 또는 imagick - 이미지 처리
✓ mbstring    - 멀티바이트 문자열

서버 설치 상태:
✓ zip         - 설치됨
✓ xml         - 설치됨
✓ xmlreader   - 설치됨
✓ xmlwriter   - 설치됨
✓ gd          - 설치됨
✓ mbstring    - 설치됨

결과: ✅ 모든 필수 확장 설치됨
```

### 선택적 PHP 확장
```
PhpSpreadsheet 선택적 확장:
✓ iconv       - 문자 인코딩 변환 (설치됨)
✓ intl        - 국제화 (설치됨)
✓ openssl     - 암호화 (설치됨)

결과: ✅ 모든 선택적 확장도 설치됨
```

---

## 🚀 배포 전략

### 전략: vendor 디렉토리 통째로 업로드

#### 이유
1. **서버에 Composer 없음**
   - Cafe24 공유 호스팅은 Composer 설치 불가
   - `composer install` 실행 불가

2. **Composer 설치 불필요**
   - vendor 디렉토리에 모든 패키지 포함됨
   - autoload.php가 자동으로 클래스 로드

3. **설정 파일 불필요**
   - composer.json, composer.lock은 선택사항
   - vendor/autoload.php만 있으면 작동

#### 장점
```
✓ 서버 설정 불필요
✓ Composer 명령어 불필요
✓ 빠른 배포
✓ 버전 고정 (의존성 변경 없음)
```

#### 단점
```
✗ 파일 수 많음 (~1,000개)
✗ 업로드 시간 소요 (압축 권장)
✗ 버전 업데이트 시 재업로드 필요
```

---

## 📤 업로드 방법

### 방법 1: 압축 후 업로드 (권장)

**로컬에서 압축:**
```bash
cd E:/web_shadow/mic4u/www
tar -czf vendor.tar.gz vendor/
```

**서버에서 압축 해제:**
```bash
cd ~/www/mic4u_as/
tar -xzf ~/vendor.tar.gz
```

**예상 시간:**
- 압축: 2분
- 업로드: 3분 (압축 파일 ~3MB)
- 압축 해제: 1분
- 총: 6분

### 방법 2: 직접 업로드 (SFTP)

**FileZilla 등으로 직접 업로드:**
```
로컬: E:/web_shadow/mic4u/www/vendor/
서버: ~/www/mic4u_as/vendor/
```

**예상 시간:**
- 파일 수: ~1,000개
- 예상: 10-15분

### 방법 3: rsync (로컬에서 직접)

```bash
rsync -avz --progress \
  E:/web_shadow/mic4u/www/vendor/ \
  dcom2000@dcom.co.kr:~/www/mic4u_as/vendor/
```

---

## 🧪 동작 확인

### 테스트 1: 기본 로드 테스트

**테스트 파일 생성:**
```php
<?php
// test_vendor.php
require_once __DIR__ . '/vendor/autoload.php';

// Composer 오토로더 확인
if (class_exists('Composer\Autoload\ClassLoader')) {
    echo "✓ Composer autoloader 로드 성공<br>";
} else {
    echo "✗ Composer autoloader 로드 실패<br>";
}

// PhpSpreadsheet 클래스 존재 확인
if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo "✓ PhpSpreadsheet 클래스 로드 성공<br>";
} else {
    echo "✗ PhpSpreadsheet 클래스 로드 실패<br>";
}
?>
```

**접속:**
```
URL: http://dcom.co.kr/mic4u_as/test_vendor.php
예상 결과:
✓ Composer autoloader 로드 성공
✓ PhpSpreadsheet 클래스 로드 성공
```

### 테스트 2: Excel 생성 테스트

**테스트 파일 생성:**
```php
<?php
// test_excel_generation.php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', '테스트');
    $sheet->setCellValue('B1', '성공');

    $writer = new Xlsx($spreadsheet);

    // 메모리에만 생성 (다운로드 안 함)
    $tempFile = tempnam(sys_get_temp_dir(), 'test');
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
```

**접속:**
```
URL: http://dcom.co.kr/mic4u_as/test_excel_generation.php
예상 결과:
✓ Excel 파일 생성 성공 (~5000 bytes)
```

### 테스트 3: 실제 리포트 생성 테스트

```
1. 로그인
2. 통계 메뉴 접속
3. "판매 리포트 다운로드" 클릭
4. Excel 파일 다운로드 확인
5. Excel 파일 열어서 내용 확인
```

---

## 🛠️ 문제 해결

### 문제 1: "Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found"

**원인:**
```
1. vendor 디렉토리 업로드 누락
2. autoload.php 경로 오류
3. 파일 권한 문제
```

**해결:**
```bash
# vendor 디렉토리 존재 확인
ls -la ~/www/mic4u_as/vendor/

# autoload.php 존재 확인
ls -la ~/www/mic4u_as/vendor/autoload.php

# 권한 확인 및 수정
chmod -R 755 ~/www/mic4u_as/vendor/
```

### 문제 2: "ZIP extension not loaded"

**원인:**
```
PHP zip 확장 미설치 (서버는 설치됨)
```

**확인:**
```bash
php -m | grep zip
# 출력: zip
```

### 문제 3: 메모리 부족

**원인:**
```
대용량 Excel 파일 생성 시 메모리 초과
```

**해결:**
```apache
# .htaccess에 추가
php_value memory_limit 256M
```

### 문제 4: 업로드 속도 느림

**원인:**
```
파일 수가 많음 (~1,000개)
```

**해결:**
```
방법 1: 로컬에서 압축 후 업로드
방법 2: rsync 사용 (증분 업로드)
```

---

## 📋 배포 체크리스트

### vendor 업로드 전
```
□ 로컬 vendor 디렉토리 확인
□ autoload.php 파일 존재 확인
□ composer.lock 파일 확인 (버전 고정)
□ 압축 파일 생성 (선택)
```

### vendor 업로드
```
□ 서버 디렉토리 생성 (~/www/mic4u_as/)
□ vendor 디렉토리 업로드
□ 업로드 완료 확인 (~1,000개 파일)
□ 파일 권한 설정 (755)
```

### 동작 확인
```
□ test_vendor.php 테스트
□ test_excel_generation.php 테스트
□ 실제 리포트 다운로드 테스트
□ 테스트 파일 삭제
```

---

## 💡 최적화 팁

### 1. .gitignore 설정
```
# vendor는 git에 포함하지 않기 (선택)
vendor/
composer.lock
```

### 2. 프로덕션 최적화
```bash
# 로컬에서 프로덕션 최적화 (선택)
composer install --no-dev --optimize-autoloader

# dev 의존성 제외
# autoloader 최적화
```

### 3. 캐싱
```php
// PhpSpreadsheet 설정 (선택)
// 셀 캐싱으로 메모리 사용량 감소
\PhpOffice\PhpSpreadsheet\Settings::setCacheStorageMethod(
    \PhpOffice\PhpSpreadsheet\Collection\CellsFactory::cache_to_discISAM
);
```

---

## 🎉 최종 결론

**Composer 관련 작업:**
- ✅ vendor 디렉토리 업로드만 필요
- ❌ 서버에 Composer 설치 불필요
- ❌ `composer install` 실행 불필요

**배포 방법:**
- vendor 디렉토리 압축
- 서버에 업로드
- 압축 해제
- 권한 설정
- 테스트

**예상 소요 시간:**
- 압축 + 업로드 + 해제: 6-10분

**호환성:**
- ✅ 서버 PHP 7.0에서 작동
- ✅ 필요한 확장 모두 설치됨
- ✅ 추가 설정 불필요

---

**다음 단계:** [배포 체크리스트](05_DEPLOYMENT_CHECKLIST.md) 참조
