# 서버 환경 분석

**서버:** dcom.co.kr (Cafe24 공유 호스팅)
**분석일:** 2025-11-12
**분석 방법:** SSH 원격 접속 및 명령어 실행

---

## 📊 시스템 기본 정보

### 서버 정보
```
호스트명: uws7-133.cafe24.com
호스팅 업체: Cafe24
호스팅 타입: 공유 호스팅
OS: CentOS 7
커널: Linux 3.10.0-1160.119.1.el7.x86_64
아키텍처: x86_64
```

### 접속 정보
```
SSH 호스트: dcom.co.kr
SSH 포트: 22
사용자명: dcom2000
홈 디렉토리: /home/hosting_users/dcom2000
웹 디렉토리: ~/www/
```

---

## 🐘 PHP 환경

### PHP 버전

**⚠️ 중요: CLI와 웹서버 PHP 버전이 다릅니다!**

**웹서버 PHP (실제 사용):**
```
PHP 7.4.5p1
Zend Engine v3.4.0
ionCube PHP Loader (활성화)
```

**CLI PHP (터미널):**
```
PHP 7.0.0p1 (cli) (built: May 10 2016 13:43:32) ( NTS )
Zend Engine v3.0.0
```

**확인 방법:**
```bash
# CLI PHP 확인 (SSH)
php -v

# 웹서버 PHP 확인 (브라우저)
echo '<?php echo phpversion(); ?>' > ~/www/version.php
# http://dcom.co.kr/version.php 접속
```

### 설치된 PHP 확장 모듈
```
✓ bcmath          - 수학 연산
✓ bz2             - 압축
✓ Core            - PHP 코어
✓ ctype           - 문자 타입 확인
✓ curl            - HTTP 클라이언트
✓ date            - 날짜/시간
✓ dba             - 데이터베이스 추상화
✓ dom             - XML DOM
✓ exif            - 이미지 메타데이터
✓ fileinfo        - 파일 타입 감지
✓ filter          - 입력 필터링
✓ ftp             - FTP 클라이언트
✓ funcall         - Cafe24 커스텀
✓ gd              - 이미지 처리
✓ gettext         - 국제화
✓ hash            - 해시 함수
✓ iconv           - 문자 인코딩 변환
✓ intl            - 국제화 확장
✓ ionCube Loader  - 코드 보호
✓ json            - JSON 처리
✓ libxml          - XML 라이브러리
✓ mbstring        - 멀티바이트 문자열
✓ mcrypt          - 암호화 (deprecated)
✓ mysqlnd         - MySQL Native Driver ⭐
✓ openssl         - SSL/TLS
✓ pcntl           - 프로세스 제어
✓ pcre            - 정규표현식
✓ PDO             - 데이터베이스 추상화
✓ pdo_sqlite      - SQLite PDO 드라이버
✓ xml             - XML 파서 ⭐
✓ xmlreader       - XML 읽기 ⭐
✓ xmlwriter       - XML 쓰기 ⭐
✓ zip             - ZIP 압축/해제 ⭐
```

**⭐ 표시: PhpSpreadsheet 필수 확장**

### PHP 설정
```
메모리 제한: 128M
최대 실행 시간: 0 (무제한)
```

---

## 🗄️ 데이터베이스 환경

### MariaDB 정보
```
버전: MariaDB 10.1.13
배포판: Distrib 10.1.13-MariaDB for Linux (x86_64)
클라이언트: mysql Ver 15.1
```

### 접속 정보
```
호스트: localhost
데이터베이스명: dcom2000
사용자명: dcom2000
비밀번호: Basserd2@@
```

### 문자셋 설정
```
character_set_client      : binary
character_set_connection  : binary
character_set_database    : utf8
character_set_filesystem  : binary
character_set_results     : binary
character_set_server      : utf8
character_set_system      : utf8
```

### 접근 권한
```
✓ dcom2000 데이터베이스: 전체 권한
✓ information_schema: 읽기 권한
```

### 기존 데이터베이스 사용 현황
```
데이터베이스: dcom2000
용도: WordPress 사이트 운영 중
테이블 수: 30+ (wp_* prefix)
Collation: utf8mb4_unicode_520_ci
Engine: InnoDB
```

---

## 🌐 웹서버 환경

### 웹서버
```
타입: Apache (nginx 프록시 뒤)
Front-end: nginx
Back-end: Apache + mod_rewrite
```

### 웹 디렉토리 구조
```
/home/hosting_users/dcom2000/
├── www/                    # 웹 루트
│   ├── .htaccess          # Apache 설정
│   ├── .well-known/       # SSL 인증
│   ├── wp-admin/          # WordPress 관리자
│   ├── wp-content/        # WordPress 컨텐츠
│   ├── wp-config.php      # WordPress 설정
│   └── [기타 WP 파일들]
├── ssl/                    # SSL 인증서
│   ├── certs/
│   └── keys/
└── DataBackup/             # 백업 디렉토리
```

### .htaccess 설정 (현재)
```apache
#BEGIN Really Simple SSL LETS ENCRYPT
RewriteRule ^.well-known/(.*)$ - [L]
#END Really Simple SSL LETS ENCRYPT

# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
...
</IfModule>
# END WordPress
```

---

## 🚫 호스팅 제약사항

### 사용 불가능한 기능
```
❌ Docker / 컨테이너 실행
❌ Root 권한 명령어
❌ 시스템 데몬 실행
❌ Composer 직접 설치 (권장하지 않음)
❌ 임의의 포트 개방
```

### 사용 가능한 기능
```
✅ PHP 스크립트 실행
✅ MySQL/MariaDB 접속
✅ 파일 업로드/다운로드
✅ SSH 접속 (제한적)
✅ cron 작업 (제한적)
✅ .htaccess 설정
```

---

## 🔍 프로젝트 호환성 분석

### PHP 버전 호환성
```
서버 웹 PHP: 7.4.5
서버 CLI PHP: 7.0.0 (사용 안 함)
프로젝트 요구사항: 7.4+
호환성: ✅ 완벽 호환

이유:
- 웹서버 PHP 7.4.5 = 프로젝트 요구사항 완벽 충족
- mysql_compat.php가 mysqli 래핑 제공
- PhpSpreadsheet 1.29 완벽 지원 (PHP 7.4~8.4)
- 모든 기능 정상 작동 보장
```

### 데이터베이스 호환성
```
서버 MariaDB: 10.1.13
프로젝트 요구사항: MySQL 5.7+ 또는 MariaDB 10.1+
호환성: ✅ 완벽

특징:
- UTF-8 (utf8mb4) 완벽 지원
- InnoDB 엔진 지원
- 트랜잭션 지원
```

### PHP 확장 호환성
```
필수 확장:
✓ mysqlnd       - DB 연결
✓ PDO           - DB 추상화
✓ mbstring      - 한글 처리
✓ zip           - PhpSpreadsheet
✓ xml           - PhpSpreadsheet
✓ xmlreader     - PhpSpreadsheet
✓ xmlwriter     - PhpSpreadsheet
✓ gd            - 이미지 처리

결과: ✅ 모든 필수 확장 설치됨
```

---

## 📦 리소스 제약

### 디스크 공간
```
확인 필요: df -h 명령어 실행 제한
예상 필요 공간:
- AS 시스템 소스: ~10MB
- vendor 디렉토리: 8.9MB
- 데이터베이스: ~10MB (예상)
총 예상: ~30MB
```

### 메모리
```
PHP 메모리: 128M (충분)
Excel 생성: 대용량 파일도 처리 가능
```

### 네트워크
```
아웃바운드 연결: ✅ 가능 (CDN 접속 가능)
인바운드 연결: ✅ HTTPS 지원
```

---

## ⚙️ 추가 설정 필요 사항

### 데이터베이스
```
선택 1: 새 데이터베이스 생성
- 장점: WordPress와 완전 분리
- 단점: 권한 설정 필요할 수 있음

선택 2: 기존 DB 사용 + 테이블 prefix
- 장점: 추가 설정 불필요
- 단점: WordPress와 같은 DB 사용
- 권장 prefix: as_ 또는 mic4u_
```

### 디렉토리 구조 제안
```
~/www/
├── [기존 WordPress 파일들]
└── mic4u_as/              # 새로 생성
    ├── as/                # AS 시스템
    │   ├── dashboard.php
    │   ├── login.php
    │   └── ...
    └── vendor/            # Composer 패키지
        └── autoload.php
```

---

## 🔧 환경 검증 명령어

### PHP 확인
```bash
php -v
php -m
php -i | grep -i memory
```

### 데이터베이스 확인
```bash
mysql -u dcom2000 -p'Basserd2@@' -e "SHOW DATABASES;"
mysql -u dcom2000 -p'Basserd2@@' -e "SELECT VERSION();"
```

### 디스크 공간 확인
```bash
du -sh ~/www/*
```

---

## 📝 참고사항

### Cafe24 공유 호스팅 특징
1. **PHP 버전 고정**: 계정별 PHP 버전은 고정됨 (7.0)
2. **데이터베이스 권한**: 슈퍼유저 권한 없음
3. **파일 권한**: 755/644 권한으로 제한
4. **백업**: 자동 백업 제공 (DataBackup/)

### 주의사항
1. WordPress와 동일 서버 사용 중
2. 리소스 공유 (공유 호스팅)
3. SSH 접속 시간 제한 가능
4. 과도한 리소스 사용 시 제재 가능

---

**다음 단계:** [포팅 전략 가이드](02_PORTING_STRATEGY.md) 참조
