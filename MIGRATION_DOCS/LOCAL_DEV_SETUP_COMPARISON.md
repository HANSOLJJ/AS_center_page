# 로컬 개발 환경 구성 방식 비교

**작성일**: 2025-10-23  
**목적**: WSL2 직접 설치 vs Docker 중 최적의 방식 선택

---

## 📊 방식 1: WSL2에 직접 설치

### 설정 구조
```
Windows (호스트)
    ↓
WSL2 Ubuntu (리눅스 환경)
    ├─ PHP 7.4 (직접 설치)
    ├─ MariaDB (직접 설치)
    └─ Apache/Nginx (직접 설치)
```

### ✅ 장점

1. **간단한 설정**
   - 한 번의 apt install로 끝남
   - 복잡한 Docker 설정 없음
   - 빠른 시작 (30분 내 완성)

2. **리소스 효율적**
   - Docker 오버헤드 없음
   - WSL2 메모리 사용량 적음
   - CPU 사용률 낮음

3. **직관적인 파일 접근**
   ```
   Windows: E:\web_shadow\mic4u\www
   WSL2: /mnt/e/web_shadow/mic4u/www
   ↓ (같은 파일)
   ```
   - VSCode에서 바로 편집 가능
   - 파일 시스템 일관성 유지

4. **명령어 실행이 간단**
   ```bash
   # 직접 WSL에서
   php -S localhost:8000
   mysql -u root
   ```

5. **프로덕션과 동일한 환경**
   - 호스팅의 리눅스 환경과 동일
   - 테스트와 실제 환경 차이 최소화

### ❌ 단점

1. **환경 오염**
   - WSL2에 직접 설치되어 관리 어려움
   - 다른 프로젝트와 충돌 가능
   - 삭제 시 수동 정리 필요

2. **버전 관리 어려움**
   - 시스템 전역 PHP 버전 (7.4로 고정)
   - 다른 프로젝트에서 8.0 필요하면 문제
   - 버전 전환 복잡

3. **상태 저장 필요**
   - WSL 리셋 시 모든 설정 손실
   - 초기 설정 스크립트 필요

4. **성능 저하 (장기 사용 시)**
   - WSL2 업데이트 시 영향
   - 시스템 자원 경합

---

## 🐳 방식 2: Docker Compose로 구성

### 설정 구조
```
Windows (호스트)
    ↓
Docker Desktop (컨테이너 관리)
    ├─ PHP 7.4 컨테이너
    ├─ MariaDB 10.x 컨테이너
    └─ Nginx/Apache 컨테이너
```

### ✅ 장점

1. **격리된 환경 (Isolation)**
   - 다른 프로젝트와 완전히 독립
   - 환경 오염 없음
   - 언제든 reset 가능 (docker-compose down -v)

2. **멀티 버전 지원**
   ```yaml
   # 같은 docker-compose.yml에서
   PHP: 7.4, 8.0, 8.1 선택 가능
   MariaDB: 10.3, 10.5, 10.6 선택 가능
   ```

3. **재현 가능한 환경**
   ```
   docker-compose.yml만 있으면
   다른 팀원과 100% 동일한 환경 구성 가능
   ```

4. **프로덕션 배포와 동일**
   - 호스팅도 Docker 사용 가능
   - 개발 = 프로덕션 환경 동일

5. **상태 관리 용이**
   ```bash
   docker-compose up    # 시작
   docker-compose down  # 종료 (모든 상태 제거)
   ```

### ❌ 단점

1. **초기 설정이 복잡**
   - docker-compose.yml 작성 필요
   - 이미지 다운로드 시간 (처음 10-15분)
   - Docker 기본 이해 필요

2. **리소스 사용량 많음**
   ```
   WSL2 직접: ~200MB 메모리
   Docker: ~500-800MB 메모리 (여러 컨테이너)
   ```

3. **파일 마운트 성능**
   - Windows ↔ Docker 파일 접근 느림
   - `/mnt/e/...` 경로는 더 느림
   - 대량 파일 작업 시 지연

4. **포트 관리**
   ```
   3306 (MySQL) 포트 충돌 가능
   8000 (PHP) 포트 충돌 가능
   Docker 설정에서 명시적으로 관리 필요
   ```

5. **러닝 커브**
   - Docker 개념 학습 필요
   - 문제 발생 시 디버깅 어려움

---

## 🎯 상황별 추천

### ✅ WSL2 직접 설치 추천하는 경우

```
✓ 빠르게 시작하고 싶을 때
✓ 단일 프로젝트만 진행할 때
✓ Docker 경험이 없을 때
✓ 로컬에서만 테스트하고 배포는 수동으로 할 때
✓ 리소스가 제한적일 때 (메모리 4GB 미만)

→ mic4u AS 프로젝트의 경우: 추천 ⭐⭐⭐⭐
  이유: 단일 프로젝트, 빠른 시작 필요, 명확한 환경 요구
```

### 🐳 Docker Compose 추천하는 경우

```
✓ 여러 프로젝트를 동시에 진행할 때
✓ 팀 협업이 필요할 때
✓ 향후 마이크로서비스 고려할 때
✓ CI/CD 파이프라인 구축할 때
✓ 프로덕션 배포도 Docker 기반일 때
✓ 환경 버전 다양성이 필요할 때

→ mic4u 같은 레거시 마이그레이션: 덜 추천 ⭐⭐
  이유: 단일 버전만 필요, 초기 설정 복잡도 높음, 파일 성능
```

---

## 🏆 최종 추천: **WSL2 직접 설치**

### 이유

1. **mic4u 프로젝트의 특성**
   - 단일 버전 (PHP 7.4, MariaDB 10.x)으로 고정
   - 버전 전환 불필요
   - 다른 프로젝트와 충돌 없음

2. **빠른 진행 필요**
   - 마이그레이션 기한 12월 31일
   - Docker 학습 시간 낭비 가능
   - 즉시 코드 리팩토링 시작 필요

3. **파일 접근 효율**
   - VSCode에서 바로 편집 가능
   - 파일 퍼포먼스 우수
   - 빌드/테스트 빠름

4. **트러블슈팅 용이**
   - 문제 발생 시 원인 파악 쉬움
   - Docker 오버헤드 없음
   - 직접 명령어로 디버깅 가능

---

## 📋 WSL2 직접 설치 Step-by-Step

### Step 1: WSL2 Ubuntu 실행
```bash
wsl -d Ubuntu
```

### Step 2: 시스템 업데이트
```bash
sudo apt update && sudo apt upgrade -y
```

### Step 3: PHP 7.4 설치
```bash
sudo apt install -y php7.4 php7.4-cli php7.4-pdo php7.4-pdo-mysql php7.4-curl php7.4-mbstring php7.4-json
```

### Step 4: MariaDB 설치
```bash
sudo apt install -y mariadb-server mariadb-client
```

### Step 5: 서비스 시작
```bash
sudo service mysql start
```

### Step 6: 테스트
```bash
php --version
mysql --version
```

**전체 소요 시간: 30분**

---

## 🔧 설치 후 구성

### PHP 설정
```bash
# php.ini 위치 확인
php -r "phpinfo();" | grep "php.ini"

# 한글 설정 추가
# /etc/php/7.4/cli/php.ini
default_charset = UTF-8
```

### MariaDB 설정
```bash
# my.cnf 수정
sudo nano /etc/mysql/my.cnf

# 추가 내용:
[mysqld]
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
```

---

## 📝 최종 체크리스트

**WSL2 직접 설치를 진행하면**:

- [ ] PHP 7.4 설치 완료
- [ ] MariaDB 설치 완료
- [ ] 한글 인코딩 설정 완료
- [ ] 테스트 서버 실행 확인
- [ ] VSCode에서 WSL 연결 확인
- [ ] 프로젝트 파일 접근 확인

---

## 결론

**mic4u AS 마이그레이션 프로젝트에는 WSL2 직접 설치가 최적입니다.**

- 빠른 시작 ✅
- 단순한 관리 ✅
- 리소스 효율적 ✅
- 프로덕션과 동일한 환경 ✅

**다음 단계**: WSL2 Ubuntu에 PHP 7.4 + MariaDB 설치 시작
