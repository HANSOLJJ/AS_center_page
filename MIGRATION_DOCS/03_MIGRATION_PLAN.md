# Phase 1: 마이그레이션 실행 계획서

**작성일**: 2025-10-23  
**목표**: mic4u AS 시스템을 현대화된 PHP/MariaDB 기반으로 이전  
**예상 기간**: 8-12주  
**팀 구성**: 1-2명의 개발자

---

## 📅 1. 전체 일정 (Timeline)

```
Phase 1: 기초 준비 (1-2주)
├─ Week 1
│  ├─ [완료] 코드 구조 분석
│  ├─ [완료] 보안 취약점 식별
│  ├─ [진행] 데이터베이스 백업
│  ├─ [진행] 신규 호스팅 환경 구성
│  └─ [진행] 로컬 개발 환경 세팅
│
└─ Week 2
   ├─ 데이터 마이그레이션 테스트
   ├─ 호스팅 보안 설정
   └─ PHP 개발 환경 준비

Phase 2: PHP 코드 현대화 (3-6주)
├─ Phase 2-1: 코어 레이어 (1-2주)
│  ├─ @config.php PDO 변환
│  ├─ DB 연결 풀링 설정
│  └─ 환경 변수 파일 생성
│
├─ Phase 2-2: 데이터 접근 계층 (1-2주)
│  ├─ mysql_query() → prepared statements 변환
│  ├─ 모든 쿼리 파라미터화
│  └─ 단위 테스트 작성
│
├─ Phase 2-3: 입력/출력 보안 (1주)
│  ├─ 입력 검증 강화
│  ├─ XSS 방지 (htmlspecialchars)
│  └─ 폼 검증 추가
│
└─ Phase 2-4: 비밀번호 해싱 (1주)
   ├─ 기존 비밀번호 마이그레이션
   ├─ password_hash() 적용
   └─ 로그인 로직 수정

Phase 3: 보안 강화 (1-2주)
├─ CSRF 토큰 구현
├─ 세션 보안 설정
├─ 에러 로깅 구축
└─ HTTPS/SSL 적용

Phase 4: 배포 & 최적화 (1-2주)
├─ 성능 테스트
├─ 통합 테스트
├─ 사용자 인수 테스트 (UAT)
├─ 신규 호스팅으로 배포
└─ 모니터링 설정
```

---

## 📋 2. Phase 1 상세 계획 (현재 진행 중)

### Week 1: 분석 & 환경 준비

#### Day 1-2: 코드 & DB 분석 ✅ 완료
- [x] @@as/as 디렉토리 구조 분석
- [x] 469개 파일 분류 및 문서화
- [x] 21개 DB 테이블 맵핑
- [x] 보안 취약점 식별

**산출물**:
- `01_DATABASE_SCHEMA.md` (완료)
- `02_CODE_INVENTORY.md` (완료)

#### Day 3-4: 데이터베이스 백업
**목표**: 현재 시스템의 완벽한 백업 생성

**작업 항목**:
```bash
# 1. MySQL 덤프 (명령줄에서)
mysqldump -h [호스트] -u mic4u41 -p'digidigi' mic4u41 > backup_20251023_full.sql
# 또는 phpMyAdmin에서 수동 백업

# 2. 파일 백업
# @@as/ 전체 디렉토리 압축
# /_filez/ 업로드 파일 압축
```

**완료 기준**:
- [ ] SQL 덤프 파일 생성 (500MB 이상)
- [ ] 파일 백업 압축 (용량 확인)
- [ ] 백업 파일 검증 (몇 줄 샘플 확인)
- [ ] 외부 드라이브/클라우드에 저장

#### Day 5-7: 신규 호스팅 환경 준비

**요구사항 확인**:
```
[ ] 호스팅 제공자 확인
[ ] FTP/SSH 접근 정보 획득
[ ] 데이터베이스 접근 정보 획득
[ ] PHP 버전 확인 (7.4 이상)
[ ] MySQL/MariaDB 버전 확인 (5.7 이상)
[ ] SSL 인증서 설치 여부
[ ] 메일 설정 확인
```

**마이그레이션을 위한 사전 작업**:
```
[ ] 신규 데이터베이스 생성: mic4u41_new
[ ] 호스팅 제한 사항 문서화
    - 메모리 제한
    - 파일 업로드 제한
    - 동시 연결 수 제한
[ ] .env 파일 배치 위치 확인
[ ] 로그 디렉토리 생성
```

---

### Week 2: 환경 최종 준비

#### Day 8-10: 로컬 개발 환경 구성

**로컬 개발 서버 설정**:
```bash
# 환경: Windows + WSL2 (또는 Docker)
# PHP 7.4+ 설치
sudo apt update && sudo apt install php7.4 php7.4-mysql php7.4-pdo php7.4-curl

# MariaDB 설치
sudo apt install mariadb-server

# 테스트 서버 실행
cd /mnt/e/web_shadow/mic4u/www
php -S localhost:8000
```

**로컬 데이터베이스 생성**:
```sql
-- MariaDB에서
CREATE DATABASE mic4u41_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 기존 데이터 임포트
mysql -u root mic4u41_dev < backup_20251023_full.sql
```

**완료 기준**:
- [ ] http://localhost:8000/@@as/as/index.php 접근 가능
- [ ] 로그인 페이지 정상 표시
- [ ] DB 연결 성공
- [ ] 에러 로그 확인 가능

#### Day 11-14: 데이터 마이그레이션 테스트

**목표**: EUC-KR → UTF-8MB4 인코딩 변환 테스트

**작업**:
```sql
-- 1. 로컬 테스트 DB에서 인코딩 변환
ALTER DATABASE mic4u41_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. 모든 테이블 변환
ALTER TABLE step1_parts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step2_center CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ... (모든 테이블)

-- 3. 데이터 검증
SELECT COUNT(*) FROM step13_as;  -- 기존과 행 수 비교
SELECT * FROM step13_as LIMIT 5; -- 데이터 표시 확인
```

**완료 기준**:
- [ ] 한글 데이터 정상 표시
- [ ] 행 수 변경 없음
- [ ] 주요 필드 샘플 데이터 검증

---

## 📋 3. Phase 2 상세 계획 (예정)

### Phase 2-1: 코어 레이어 업그레이드

**목표**: 모든 페이지가 공통으로 사용하는 @config.php를 PDO 기반으로 변환

#### 작업 항목 (1-2주)

1. **PDO 설정 파일 작성** (신규)
```php
// config/Database.php
<?php
class Database {
    private $pdo;
    
    public function __construct() {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_NAME') ?: 'mic4u41'
        );
        
        $this->pdo = new PDO(
            $dsn,
            getenv('DB_USER') ?: 'mic4u41',
            getenv('DB_PASS') ?: 'digidigi',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}
?>
```

2. **환경 변수 파일 작성** (.env)
```
DB_HOST=localhost
DB_NAME=mic4u41
DB_USER=mic4u41
DB_PASS=digidigi
DB_PORT=3306

APP_ENV=production
DEBUG=false
```

3. **기존 @config.php 점진적 변경**
   - 신규 Database 클래스 import
   - 하위 호환성 유지
   - mysql_* 함수 제거

#### 테스트 기준:
- [ ] 모든 페이지 DB 연결 성공
- [ ] 에러 로그 없음
- [ ] 성능 저하 없음

---

### Phase 2-2: Prepared Statements 적용

**목표**: SQL Injection 취약점 제거

**범위**: 469개 파일 중 약 200개 파일에서 쿼리 변환

**예시 변환**:

```php
// Before (매우 위험)
$s1_name = $_POST['s1_name'];
$query = "INSERT INTO step1_parts (s1_name) VALUES ('$s1_name')";
mysql_query($query);

// After (안전)
$stmt = $pdo->prepare("INSERT INTO step1_parts (s1_name) VALUES (?)");
$stmt->execute([$_POST['s1_name']]);
```

**우선순위**:
1. 🔴 높음: *_process.php 파일들 (INSERT/UPDATE/DELETE)
2. 🟠 중간: list.php, view.php (SELECT)
3. 🟡 낮음: read-only 페이지

---

### Phase 2-3: 입력 검증 & XSS 방지

**목표**: 사용자 입력 보안화

**작업**:
1. FormValidator 클래스 작성
2. htmlspecialchars() 모든 출력에 적용
3. 폼 검증 추가 (클라이언트 + 서버)

---

### Phase 2-4: 비밀번호 해싱

**현재**: MySQL PASSWORD() 함수
**목표**: PHP password_hash() (bcrypt)

**마이그레이션 단계**:
```
Step 1: 신규 사용자는 password_hash() 사용
Step 2: 로그인 시 두 방식 모두 지원 (업그레이드)
Step 3: 3개월 후 기존 PASSWORD() 해시 모두 변환
```

---

## 🔒 4. Phase 3: 보안 강화 (1-2주)

### 4.1 CSRF 토큰

**모든 POST 폼에 추가**:
```php
<?php
// 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- 폼에 포함 -->
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- 필드들 -->
</form>

<?php
// 처리 시 검증
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF 토큰이 유효하지 않습니다.");
}
?>
```

### 4.2 세션 보안

```php
// @session_inc.php
session_set_cookie_params([
    'lifetime' => 3600,      // 1시간
    'path' => '/',
    'domain' => 'mic4u.co.kr',
    'secure' => true,        // HTTPS only
    'httponly' => true,      // JS 접근 차단
    'samesite' => 'Strict'   // CSRF 방지
]);

session_start();

// 세션 타임아웃
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > 3600)) {
    session_destroy();
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();
```

### 4.3 에러 로깅

```php
// config/Logger.php
<?php
class Logger {
    public static function error($msg, $context = []) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'message' => $msg,
            'context' => $context,
            'user_id' => $_SESSION['member_id'] ?? 'anonymous',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        error_log(json_encode($log), 3, __DIR__ . '/../logs/error.log');
    }
}
?>
```

---

## 🚀 5. Phase 4: 배포 & 최적화 (1-2주)

### 5.1 성능 테스트

```bash
# 로컬에서 성능 테스트
apache2ctl start
ab -n 1000 -c 10 http://localhost:8000/@@as/as/index.php

# 목표: 
# - 응답 시간 < 500ms
# - 에러율 0%
```

### 5.2 통합 테스트

**테스트 시나리오**:
1. 로그인 → AS 신청 생성 → 결과 조회
2. 부품 등록 → 부품 목록 조회 → 수정 → 삭제
3. 여러 센터 로그인 → 센터별 데이터 분리 확인
4. 보고서 생성 → 엑셀 다운로드

### 5.3 배포 단계

```
Step 1: 카나리 배포 (10% 트래픽)
   - 신규 호스팅에 배포
   - 10% 사용자만 신규 서버로 라우팅
   - 2-3일 모니터링

Step 2: 단계적 배포 (50%)
   - 50% 사용자 이전
   - 모니터링 (1주)

Step 3: 완전 전환 (100%)
   - 모든 사용자 신규 서버로 이전
   - 기존 서버는 읽기 전용으로 유지 (백업용)
```

---

## ✅ 6. 완료 기준 (Definition of Done)

### Phase 1 완료
- [x] 코드 구조 문서화
- [x] 보안 취약점 목록화
- [ ] 데이터베이스 백업 생성
- [ ] 로컬 개발 환경 구성 완료
- [ ] 신규 호스팅 환경 준비 완료

### Phase 2 완료
- [ ] 모든 mysql_* 함수 제거
- [ ] 모든 쿼리를 Prepared Statements로 변환
- [ ] 입력 검증 추가 (주요 페이지)
- [ ] XSS 방지 코드 적용 (주요 페이지)
- [ ] 비밀번호 해싱 구현
- [ ] 통합 테스트 통과

### Phase 3 완료
- [ ] CSRF 토큰 구현 (모든 폼)
- [ ] 세션 보안 옵션 설정
- [ ] 에러 로깅 구축
- [ ] HTTPS/SSL 적용
- [ ] 보안 감시 체계 구축

### Phase 4 완료
- [ ] 성능 테스트 통과
- [ ] UAT 통과
- [ ] 신규 호스팅에 배포
- [ ] 모니터링 설정 완료
- [ ] 긴급 연락처 문서화

---

## 👥 7. 역할 & 책임

| 역할 | 담당자 | 책임 |
|------|--------|------|
| **개발 리더** | TBD | 전체 마이그레이션 진행, 의사결정 |
| **백엔드 개발자** | TBD | PHP 코드 리팩토링, DB 마이그레이션 |
| **테스터** | TBD | 테스트 시나리오 작성, 테스트 실행 |
| **운영 담당자** | TBD | 호스팅 환경 관리, 배포 지원 |
| **프로젝트 매니저** | TBD | 일정 관리, 이해관계자 소통 |

---

## 📞 8. 의사결정 포인트

| 주제 | 옵션 | 추천 | 이유 |
|------|------|------|------|
| **PHP 버전** | 7.4 vs 8.0+ | 7.4 | 호스팅 호환성, 학습곡선 |
| **프레임워크** | Laravel vs Symfony | 유지 | 현재 구조 유지, 점진적 개선 |
| **UI 개선** | 필수 vs 선택 | 선택 | Phase 4 이후 고려 |
| **API 서버** | 필수 vs 선택 | 선택 | 향후 모바일 앱 필요 시 추가 |

---

## 📌 9. 위험 요소 & 대응

| 위험 | 확률 | 영향 | 대응 |
|------|------|------|------|
| 데이터 손실 | 낮음 | 매우 높음 | 주간 백업, 2개 복사본 유지 |
| 성능 저하 | 중간 | 높음 | 인덱스 최적화, 캐싱 추가 |
| 호환성 문제 | 중간 | 중간 | 철저한 테스트, 롤백 계획 |
| 일정 지연 | 높음 | 중간 | 2주 버퍼 포함 |
| 보안 침입 | 낮음 | 매우 높음 | HTTPS, WAF, 침입 탐지 |

---

## 📚 10. 참고 문서

- `01_DATABASE_SCHEMA.md` - DB 테이블 정의
- `02_CODE_INVENTORY.md` - 코드 파일 분석
- `03_MIGRATION_PLAN.md` - 이 문서
- `04_HOSTING_SETUP.md` - 호스팅 환경 준비 (예정)
- `05_PHASЕ_2_IMPLEMENTATION.md` - Phase 2 상세 가이드 (예정)

---

## 🎯 최종 목표

**2025년 12월 31일까지**:
- ✅ Phase 1-4 완료
- ✅ 모든 기능 신규 호스팅에서 정상 작동
- ✅ 보안 감시 체계 구축
- ✅ 팀 교육 완료
- ✅ 24시간 모니터링 시작

---

**다음 단계**: Phase 1-2 (데이터베이스 백업 및 신규 호스팅 환경 구성)
