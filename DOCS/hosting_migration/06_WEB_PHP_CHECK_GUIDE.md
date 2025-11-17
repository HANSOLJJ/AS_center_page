# 웹서버 PHP 버전 확인 가이드

**중요도:** ⭐⭐⭐⭐⭐
**작성일:** 2025-11-12

---

## ⚠️ 왜 중요한가?

공유 호스팅 환경에서는 **CLI PHP**와 **웹서버 PHP** 버전이 다를 수 있습니다!

```
CLI PHP (터미널):     PHP 7.0.0  ← SSH로 php -v 실행
웹서버 PHP (실제):    PHP 7.4.5  ← 브라우저로 접속 시 사용
```

**프로젝트는 웹서버 PHP를 사용하므로 웹서버 PHP 버전을 확인해야 합니다!**

---

## 🔍 확인 방법

### 방법 1: phpversion() 함수 (빠른 확인)

**1단계: 테스트 파일 생성**
```bash
# SSH 접속 후
echo '<?php echo phpversion(); ?>' > ~/www/version.php
```

**2단계: 브라우저로 확인**
```
URL: http://dcom.co.kr/version.php
또는: https://dcom.co.kr/version.php

출력 예시: 7.4.5p1
```

**3단계: 테스트 파일 삭제**
```bash
rm ~/www/version.php
```

---

### 방법 2: phpinfo() 함수 (상세 확인)

**1단계: 테스트 파일 생성**
```bash
# SSH 접속 후
cat > ~/www/phpinfo.php << 'EOF'
<?php
phpinfo();
?>
EOF
```

**2단계: 브라우저로 확인**
```
URL: http://dcom.co.kr/phpinfo.php

확인 내용:
- PHP Version: 7.4.5
- System: Linux 정보
- Server API: Apache 2.0 Handler (또는 FPM/FastCGI)
- Configuration File: php.ini 위치
- Extension: 설치된 모든 PHP 확장 목록
- 환경변수, 설정값 등
```

**3단계: 테스트 파일 삭제 (보안상 중요!)**
```bash
rm ~/www/phpinfo.php
```

⚠️ **경고:** phpinfo()는 서버의 상세 정보를 노출하므로 확인 후 반드시 삭제하세요!

---

### 방법 3: 상세 버전 정보 확인

**테스트 파일:**
```bash
cat > ~/www/php_detail.php << 'EOF'
<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "PHP_VERSION: " . PHP_VERSION . "<br>";
echo "PHP_MAJOR_VERSION: " . PHP_MAJOR_VERSION . "<br>";
echo "PHP_MINOR_VERSION: " . PHP_MINOR_VERSION . "<br>";
echo "PHP_RELEASE_VERSION: " . PHP_RELEASE_VERSION . "<br>";
echo "Zend Version: " . zend_version() . "<br>";
?>
EOF
```

**출력 예시:**
```
PHP Version: 7.4.5p1
PHP_VERSION: 7.4.5
PHP_MAJOR_VERSION: 7
PHP_MINOR_VERSION: 4
PHP_RELEASE_VERSION: 5
Zend Version: 3.4.0
```

**삭제:**
```bash
rm ~/www/php_detail.php
```

---

### 방법 4: PHP 확장 모듈 확인

**특정 확장 설치 여부 확인:**
```bash
cat > ~/www/check_extensions.php << 'EOF'
<?php
$required_extensions = [
    'zip', 'xml', 'xmlreader', 'xmlwriter',
    'gd', 'mbstring', 'mysqli', 'pdo'
];

echo "<h3>PHP 확장 모듈 확인</h3>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    $color = extension_loaded($ext) ? 'green' : 'red';
    echo "<span style='color: $color;'>$status $ext</span><br>";
}

echo "<hr>";
echo "<h3>설치된 모든 확장:</h3>";
echo implode(', ', get_loaded_extensions());
?>
EOF
```

**브라우저 접속:**
```
URL: http://dcom.co.kr/check_extensions.php
```

**삭제:**
```bash
rm ~/www/check_extensions.php
```

---

## 🔄 CLI vs 웹서버 PHP 비교

### CLI PHP 확인 (SSH)
```bash
ssh dcom2000@dcom.co.kr

# 버전 확인
php -v
# 출력: PHP 7.0.0p1 (cli)

# 확장 모듈 확인
php -m

# php.ini 위치
php --ini
```

### 웹서버 PHP 확인 (브라우저)
```bash
# 테스트 파일 생성
echo '<?php phpinfo(); ?>' > ~/www/test.php

# 브라우저에서 http://dcom.co.kr/test.php 접속
# 출력: PHP 7.4.5
```

### 왜 다른가?

**공유 호스팅의 특징:**
```
1. CLI PHP: 서버 기본 시스템 PHP (오래된 버전)
   - 터미널 명령어 실행용
   - 시스템 관리용
   - 업그레이드 어려움

2. 웹서버 PHP: 호스팅 업체가 제공하는 최신 PHP
   - Apache/Nginx와 연동
   - 웹 애플리케이션용
   - 호스팅 제어판에서 버전 변경 가능
```

---

## 📊 Cafe24 호스팅의 경우

### PHP 버전 변경 방법

Cafe24는 제어판에서 PHP 버전을 변경할 수 있습니다:

**경로:**
```
Cafe24 로그인
→ 나의 서비스 관리
→ 호스팅 관리
→ PHP 버전 변경
```

**선택 가능한 버전 (2024년 기준):**
```
- PHP 8.2
- PHP 8.1
- PHP 8.0
- PHP 7.4 ← 현재
- PHP 7.3
- PHP 7.2
- PHP 7.1
- PHP 7.0
- PHP 5.6
```

---

## ✅ 프로젝트 확인 체크리스트

### 배포 전 필수 확인

```bash
# 1. 웹서버 PHP 버전 확인
echo '<?php echo phpversion(); ?>' > ~/www/version.php
# 브라우저 접속: http://dcom.co.kr/version.php
# 예상: 7.4.5 이상

# 2. 필수 확장 모듈 확인
cat > ~/www/check_ext.php << 'EOF'
<?php
$ext = ['zip', 'xml', 'xmlreader', 'xmlwriter', 'gd', 'mbstring'];
foreach ($ext as $e) {
    echo extension_loaded($e) ? "✓ $e\n" : "✗ $e (없음)\n";
}
?>
EOF
# 브라우저 접속: http://dcom.co.kr/check_ext.php

# 3. 테스트 파일 정리
rm ~/www/version.php ~/www/check_ext.php
```

### PhpSpreadsheet 호환성 확인

```bash
cat > ~/www/check_phpspreadsheet.php << 'EOF'
<?php
// PHP 버전 확인
$php_version = phpversion();
$required_version = '7.4.0';

echo "<h3>PhpSpreadsheet 호환성 확인</h3>";
echo "현재 PHP 버전: $php_version<br>";
echo "필요 PHP 버전: >= $required_version<br>";

if (version_compare($php_version, $required_version, '>=')) {
    echo "<span style='color: green; font-weight: bold;'>✓ 호환 가능</span><br>";
} else {
    echo "<span style='color: red; font-weight: bold;'>✗ 버전 업그레이드 필요</span><br>";
}

echo "<hr>";
echo "<h3>필수 확장 모듈:</h3>";
$required = ['zip', 'xml', 'xmlreader', 'xmlwriter', 'gd', 'mbstring', 'iconv'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    $color = $loaded ? 'green' : 'red';
    $status = $loaded ? '✓' : '✗';
    echo "<span style='color: $color;'>$status $ext</span><br>";
}
?>
EOF

# 브라우저 접속: http://dcom.co.kr/check_phpspreadsheet.php
```

---

## 🛠️ 문제 해결

### 문제 1: 테스트 파일이 다운로드됨

**원인:**
- 웹서버가 PHP를 처리하지 못함
- .php 확장자가 등록되지 않음

**해결:**
```apache
# .htaccess에 추가
AddType application/x-httpd-php .php
```

### 문제 2: "500 Internal Server Error"

**원인:**
- PHP 문법 오류
- 파일 권한 문제

**해결:**
```bash
# 파일 권한 확인
ls -la ~/www/version.php

# 권한 수정
chmod 644 ~/www/version.php
```

### 문제 3: 빈 페이지 표시

**원인:**
- PHP 에러 표시가 꺼져 있음

**해결:**
```bash
cat > ~/www/test_error.php << 'EOF'
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo phpversion();
?>
EOF
```

---

## 🔐 보안 권장사항

### 테스트 후 반드시 삭제

```bash
# 모든 테스트 파일 삭제
rm ~/www/version.php
rm ~/www/phpinfo.php
rm ~/www/php_detail.php
rm ~/www/check_*.php
rm ~/www/test*.php
```

### .htaccess로 접근 차단

```apache
# ~/www/.htaccess
<FilesMatch "^(phpinfo|version|test_).*\.php$">
    Require all denied
</FilesMatch>
```

### 임시 디렉토리 사용

```bash
# 별도 디렉토리에서 테스트
mkdir ~/www/temp_test
echo '<?php phpinfo(); ?>' > ~/www/temp_test/info.php

# 확인 후 디렉토리 전체 삭제
rm -rf ~/www/temp_test
```

---

## 📝 요약

### 핵심 포인트

1. **CLI PHP ≠ 웹서버 PHP**
   - CLI: 7.0.0 (터미널)
   - 웹: 7.4.5 (실제 사용)

2. **확인 방법**
   - 빠른 확인: `echo '<?php echo phpversion(); ?>' > version.php`
   - 상세 확인: `phpinfo()`

3. **보안**
   - 테스트 후 반드시 파일 삭제
   - phpinfo() 노출 주의

4. **프로젝트 호환성**
   - PhpSpreadsheet: PHP 7.4+ 필요
   - 웹서버 PHP 7.4.5 = 완벽 호환 ✅

---

**다음 단계:**
- [서버 환경 분석](01_SERVER_ENVIRONMENT.md) 다시 확인
- [포팅 전략 가이드](02_PORTING_STRATEGY.md) 진행

---

**관련 명령어 참고:**
```bash
# CLI PHP 정보
php -v                  # 버전
php -m                  # 확장 모듈
php -i                  # 전체 정보
php --ini               # php.ini 위치

# 웹서버 PHP 정보
# → 반드시 브라우저로 확인!
```
