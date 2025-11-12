# Phase 1: 호스팅 환경 준비 & 구성 메모

**작성일**: 2025-10-23  
**목표**: 신규 호스팅 서버 (MariaDB 10.x)에서 AS 시스템 실행 가능하도록 준비  
**현재 상황**:
- ✅ 신규 호스팅 준비 완료
- ✅ 호스팅 연결 정보 보유
- ⏳ 세부 환경 설정 필요

---

## 📋 1. 호스팅 환경 요구사항 체크리스트

### 1.1 필수 사항

#### PHP 버전 요구사항
```
현재: PHP 5.x (deprecated)
목표: PHP 7.4 이상
추천: PHP 8.1 LTS
```

- [ ] **PHP 버전 확인**
  ```bash
  # 호스팅 제어판에서 확인 또는
  # 호스트에 문의하여 설치된 PHP 버전 확인
  ```
  
  **확인 방법**:
  - cPanel → PHP Settings
  - Plesk → Tools & Settings → PHP Settings
  - 또는 호스팅 제공자 문서 확인

- [ ] **필수 PHP 확장**
  ```
  ✓ php-pdo          (PDO 드라이버)
  ✓ php-pdo-mysql    (MySQL 드라이버)
  ✓ php-curl         (cURL - SMS/외부 API 연동용)
  ✓ php-mbstring     (한글 문자 처리)
  ✓ php-gd           (이미지 처리 - 선택사항)
  ✓ php-json         (JSON 처리)
  ```

  **확인 방법**:
  ```php
  // 호스팅의 웹 루트에 test.php 생성
  <?php phpinfo(); ?>
  
  // 브라우저에서 확인
  // http://yourdomain.com/test.php
  ```

#### MySQL/MariaDB 요구사항

- [ ] **MariaDB 버전 확인**
  ```
  현재: MySQL (버전 미확인)
  목표: MariaDB 10.3 이상
  추천: MariaDB 10.5 LTS
  ```

  **확인 방법**:
  - phpMyAdmin → 왼쪽 하단 "데이터베이스 서버"
  - SSH: `mysql --version`

- [ ] **필수 설정**
  ```ini
  [mysql]
  max_allowed_packet = 256M
  
  [mysqld]
  character_set_server = utf8mb4
  collation_server = utf8mb4_unicode_ci
  default-storage-engine = InnoDB
  ```

- [ ] **접근 권한 확인**
  ```
  호스트: (호스팅 제공자 확인)
  사용자: mic4u41 또는 username_mic4u41
  비밀번호: (보안상 따로 관리)
  데이터베이스: mic4u41 또는 username_mic4u41
  ```

---

### 1.2 권장 사항

#### HTTPS/SSL 인증서

- [ ] **SSL 인증서 설치**
  ```
  □ Let's Encrypt (무료)
  □ Comodo (유료)
  □ DigiCert (유료)
  ```

  **확인 방법**:
  - 브라우저 주소창 🔒 표시 확인
  - `https://yourdomain.com` 접근 가능 확인

- [ ] **HTTPS 리다이렉트 설정** (.htaccess)
  ```apache
  <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteCond %{HTTPS} off
      RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  </IfModule>
  ```

#### 메일 설정

- [ ] **SMTP 설정 확인**
  ```
  호스트: mail.yourdomain.com
  포트: 587 (TLS) 또는 465 (SSL)
  사용자: (호스팅 제공자 확인)
  비밀번호: (보안상 따로 관리)
  ```

  **용도**: AS 신청 확인 메일, 비밀번호 재설정 등

---

### 1.3 파일 시스템 권한

- [ ] **웹 루트 경로 확인**
  ```
  일반적: /public_html
  또는: /www
  또는: /httpdocs
  ```

- [ ] **쓰기 가능한 디렉토리 생성**
  ```
  /public_html/@@as/as/LOGS/            (에러 로그)
  /public_html/@@as/as/.env             (환경 설정)
  /public_html/_UPLOAD_FILEZ/           (파일 업로드)
  /public_html/_filez/                  (파일 저장소)
  
  권한: 755 (디렉토리), 644 (파일)
  ```

  **설정 방법** (FTP/SFTP):
  ```bash
  # FTP 클라이언트에서
  chmod 755 @@as/as/LOGS/
  chmod 755 _UPLOAD_FILEZ/
  chmod 755 _filez/
  ```

#### 파일 업로드 제한

- [ ] **최대 업로드 크기 확인**
  ```
  php.ini 설정:
  upload_max_filesize = 64M
  post_max_size = 64M
  ```

  **확인 방법**:
  - cPanel → MultiPHP INI Editor
  - 또는 phpinfo()에서 확인

---

## 🔧 2. 호스팅 세부 설정 항목

### 2.1 데이터베이스 설정

#### 현재 호스팅에서 신규 데이터베이스 생성

**Option A: cPanel 사용 (권장)**
```
1. cPanel 로그인 → MySQL Databases
2. "새 데이터베이스 생성" → mic4u41_new
3. Character Set: utf8mb4 선택
4. 새 사용자 생성: mic4u41_new_user
5. 사용자에 모든 권한 부여
```

**Option B: phpMyAdmin 사용**
```
1. phpMyAdmin 로그인
2. "데이터베이스" 탭 → "새 데이터베이스 생성"
3. 데이터베이스명: mic4u41_new
4. 데이터 정렬: utf8mb4_unicode_ci
5. "생성" 클릭
```

**Option C: SSH (명령줄)**
```bash
mysql -u root -p
CREATE DATABASE mic4u41_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON mic4u41_new.* TO 'mic4u41'@'localhost' IDENTIFIED BY 'password';
FLUSH PRIVILEGES;
EXIT;
```

#### 완료 기준
- [ ] 데이터베이스 생성됨
- [ ] 사용자 계정 생성됨
- [ ] 권한 설정 완료
- [ ] 테스트 접근 성공

---

### 2.2 PHP 설정 최적화

#### php.ini 설정 값 조정

**필수 설정**:
```ini
[PHP]
; 오류 표시 (개발 중만 ON, 프로덕션에서는 OFF)
display_errors = Off
log_errors = On
error_log = /home/username/public_html/logs/php_error.log

; 세션 설정
session.save_path = /home/username/public_html/sessions
session.gc_maxlifetime = 3600
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Strict

; 파일 업로드
file_uploads = On
upload_tmp_dir = /tmp
upload_max_filesize = 64M
post_max_size = 64M

; 성능
memory_limit = 256M
max_execution_time = 300
default_charset = UTF-8
```

**설정 방법**:
- **cPanel**: MultiPHP INI Editor
- **Plesk**: Tools & Settings → PHP Settings
- **Manual**: SSH에서 php.ini 직접 편집

#### 확인 방법
```php
<?php
// test_config.php
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
?>
```

---

### 2.3 디렉토리 구조 생성

#### FTP를 통한 디렉토리 생성

```
/public_html/
├── @@as/
│   └── as/
│       ├── LOGS/                 ← 생성 (775 권한)
│       ├── SESSIONS/             ← 생성 (775 권한)
│       ├── .env                  ← 신규 생성 (환경 설정)
│       ├── config/               ← 신규 생성
│       │   ├── Database.php
│       │   ├── Logger.php
│       │   └── Config.php
│       └── ... (기존 파일들)
│
├── _UPLOAD_FILEZ/               ← 생성 (775 권한)
├── _filez/                      ← 기존 (775 권한 확인)
│
└── logs/                        ← 생성 (775 권한)
    ├── php_error.log
    ├── app_error.log
    └── access.log
```

**권한 설정**:
```bash
# SSH에서 실행
cd /home/username/public_html

chmod 755 @@as/as/LOGS/
chmod 755 @@as/as/SESSIONS/
chmod 755 _UPLOAD_FILEZ/
chmod 755 logs/

# 파일 권한
chmod 644 @@as/as/.env
chmod 644 @@as/as/config/*.php
```

---

### 2.4 환경 변수 파일 (.env) 설정

#### .env 파일 생성

```
# /public_html/@@as/as/.env

# 데이터베이스 설정
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mic4u41_new
DB_USER=mic4u41
DB_PASS=your_secure_password_here
DB_CHARSET=utf8mb4

# 애플리케이션 설정
APP_ENV=production
DEBUG=false
APP_URL=https://yourdomain.com/@@as/as/

# 로깅
LOG_PATH=/home/username/public_html/logs/app_error.log
LOG_LEVEL=error

# 세션
SESSION_PATH=/home/username/public_html/sessions/
SESSION_LIFETIME=3600

# 메일 설정
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM=noreply@yourdomain.com
```

#### 보안 주의사항
```
⚠️ .env 파일은 절대 Git에 커밋하지 마세요!
⚠️ .env 파일 권한을 600으로 설정하세요:
   chmod 600 .env
⚠️ .env 파일을 웹에서 접근 불가능하도록 .htaccess 설정:
```

**.htaccess 설정**:
```apache
# @@as/as/.htaccess
<Files .env>
    Order allow,deny
    Deny from all
</Files>

# 다른 설정 파일도 보호
<FilesMatch "\.(php|env|config)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 🚀 3. 마이그레이션 전 최종 체크리스트

### Phase 1 완료 기준

#### 호스팅 환경
- [ ] PHP 7.4+ 설치 확인
- [ ] PDO 및 필수 확장 설치 확인
- [ ] MariaDB 10.x 확인
- [ ] UTF-8MB4 지원 확인
- [ ] HTTPS/SSL 인증서 설치
- [ ] 메일 설정 확인

#### 데이터베이스
- [ ] 신규 데이터베이스 생성 (mic4u41_new)
- [ ] 사용자 계정 생성
- [ ] 권한 설정 완료
- [ ] Character set을 utf8mb4로 설정
- [ ] 테스트 접근 가능 확인

#### 파일 시스템
- [ ] 필수 디렉토리 생성
- [ ] 권한 설정 완료 (755/775)
- [ ] .env 파일 생성 (600 권한)
- [ ] .htaccess 보안 설정
- [ ] 로그 디렉토리 쓰기 가능 확인

#### 코드 및 설정
- [ ] PDO 기반 설정 파일 준비
- [ ] 환경 변수 (.env) 작성
- [ ] .htaccess 보안 규칙 작성
- [ ] 에러 로깅 구성 완료

#### 테스트
- [ ] phpinfo() 확인 페이지 접근 가능
- [ ] 데이터베이스 연결 테스트 성공
- [ ] 파일 업로드 테스트 성공

---

## 📞 4. 호스팅 제공자 연락처 정보

```
호스팅 제공자: [입력 필요]
고객 이메일: [입력 필요]
고객 패스워드: [입력 필요 - 보안상 따로 관리]
FTP/SFTP 정보:
  호스트: [입력 필요]
  사용자명: [입력 필요]
  비밀번호: [입력 필요]

데이터베이스 정보:
  호스트: [입력 필요]
  사용자명: [입력 필요]
  비밀번호: [입력 필요]
  데이터베이스: [입력 필요]

지원팀 연락처:
  전화: [입력 필요]
  이메일: [입력 필요]
  웹사이트: [입력 필요]

SLA 정보:
  가동시간 보장: [입력 필요]
  지원 시간: [입력 필요]
  응답 시간: [입력 필요]
```

---

## 🔍 5. 호스팅 제한 사항 & 주의사항

### 5.1 일반적인 제약 조건

#### 메모리 제한
```
PHP Memory Limit: 일반적 256MB
큰 파일 처리 시 부족할 수 있음
→ php.ini에서 512MB로 증설 가능 (요청 필요할 수 있음)
```

#### 실행 시간 제한
```
max_execution_time: 일반적 300초
대량 데이터 처리 시 타임아웃 가능
→ 배치 작업은 Cron Job으로 처리
```

#### 동시 연결 수
```
MySQL 동시 연결: 일반적 10-20개
많은 사용자 동시 접속 시 연결 풀링 필요
```

### 5.2 주의사항

#### 파일 권한
```
⚠️ 웹 서버가 읽고 쓸 수 있도록 권한 설정
⚠️ 소유권: www-data 또는 apache 사용자
⚠️ 권한 과다 부여 방지 (777 금지)
```

#### 데이터베이스
```
⚠️ 자동 백업 정책 확인 (주간/월간)
⚠️ 백업 복구 방법 확인
⚠️ 저장소 용량 제한 (대개 무제한)
```

#### 대역폭
```
⚠️ 대역폭 제한이 있을 수 있음
⚠️ 파일 다운로드가 많은 경우 제한 발동
⚠️ CDN 사용 검토 (선택사항)
```

---

## 📝 6. 마이그레이션 전 확인 체크리스트 (최종)

```
□ 호스팅 환경 요구사항 충족 확인
  □ PHP 7.4+
  □ MariaDB 10.x
  □ SSL/HTTPS
  □ SMTP 메일

□ 데이터베이스 준비
  □ 신규 DB 생성
  □ 사용자 생성
  □ 권한 설정
  □ UTF-8MB4 설정

□ 파일 시스템 준비
  □ 디렉토리 생성
  □ 권한 설정
  □ .env 파일 작성
  □ .htaccess 보안

□ 코드 준비
  □ PDO 설정 클래스
  □ Database.php
  □ Logger.php
  □ Config.php

□ 로컬 테스트 완료
  □ DB 연결 테스트
  □ 기본 기능 테스트
  □ 보안 취약점 수정

□ 백업 완료
  □ 현재 DB 덤프
  □ 현재 파일 백업
  □ 외부 저장소에 저장

□ 의사소통
  □ 호스팅 제공자 연락처 정리
  □ 비상 연락망 정리
  □ 롤백 계획 수립
```

---

## 🎯 다음 단계

**Phase 1 완료 후**:
1. 로컬 개발 환경에서 코드 리팩토링 (Phase 2-1)
2. PDO 기반 설정 파일 테스트
3. 신규 호스팅에 테스트 배포
4. 통합 테스트 실행
5. Phase 2-2 진행 (Prepared Statements)

**예상 일정**: 2025년 11월 말까지 Phase 1-2 완료

---

## 📚 참고 자료

- PHP 공식 문서: https://www.php.net/docs.php
- PDO 튜토리얼: https://www.php.net/manual/en/pdo.prepared-statements.php
- MariaDB 문서: https://mariadb.com/docs/
- OWASP 보안 가이드: https://owasp.org/

---

**상태**: ✅ Phase 1 기초 준비 완료  
**다음 검토**: 2025-10-30 (1주일 후)
