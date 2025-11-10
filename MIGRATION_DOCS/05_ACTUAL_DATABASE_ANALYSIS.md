# Phase 1-1: 실제 데이터베이스 분석 (SQL 덤프 기반)

**작성일**: 2025-10-23  
**분석 대상**: E:\web_shadow\mic4u\www\mic4u41.sql (52MB)  
**분석 결과**: 실제 데이터베이스 구조 확인

---

## 🎯 중요 발견사항

### 예상과 다른 점!
| 항목 | 예상 | 실제 | 차이 |
|------|------|------|------|
| **테이블 수** | 21개 | **81개** | +60개 |
| **범위** | AS 시스템만 | **전체 mic4u 시스템** | 훨씬 광범위 |
| **인코딩** | EUC-KR | ✅ **EUC-KR 확인** | 일치 |
| **DB 엔진** | MyISAM | MyISAM | 일치 |

---

## 📊 1. 실제 테이블 분류 (81개 테이블)

### 1.1 AS 시스템 핵심 (step1-21) - **21개 테이블** ⭐⭐⭐

우리의 마이그레이션 대상!

```
step1_parts             (AS 부품)
step2_center            (AS 센터)
step3_member            (센터 직원)
step4_cart              (장바구니)
step5_category          (부품 카테고리)
step6_order             (AS 주문)
step7_center_parts      (센터 부품 재고)
step8_sendbox           (배송)
step9_out               (출고)
step10_tax              (세금)
step11_member           (사용자 마스터)
step12_sms_sample       (SMS 템플릿)
step13_as               (AS 신청 ⭐⭐⭐ 가장 중요)
step14_as_item          (AS 제품)
step15_as_model         (AS 모델)
step16_as_poor          (AS 부실)
step17_as_item_cure     (수리 항목)
step18_as_cure_cart     (수리 카트)
step19_as_result        (AS 결과 ⭐⭐⭐ 중요)
step20_sell             (판매)
step21_sell_cart        (판매 카트)
```

**마이그레이션 순서**: 21개 테이블 모두 마이그레이션 필수 ✅

---

### 1.2 Zboard 포럼 시스템 (zetyx_*) - **8개 테이블**

포럼/BBS 기능

```
zetyx_admin_table               (포럼 관리자)
zetyx_board_category_default    (게시판 카테고리)
zetyx_board_comment_default     (댓글)
zetyx_board_default             (게시물)
zetyx_division_default          (구분)
zetyx_get_memo                  (받은 메모)
zetyx_group_table               (그룹)
zetyx_member_table              (포럼 회원)
zetyx_send_memo                 (보낸 메모)
```

**마이그레이션 대상**: 낮음 (현재 사용 안 함)

---

### 1.3 메인 사이트 콘텐츠 (mic4u.co.kr) - **~20개**

```
// 기본 설정
2010_admin_member       (관리자)
mic_admin              (관리자 추가)
mic_page               (페이지)
info                   (정보)

// 상품/카탈로그
item1                  (상품)
category1, category2   (카테고리)
market                 (마켓)
use_item              (사용 상품)

// 주문/판매
mycart                 (장바구니)
myorder               (주문)
member                (회원)

// 콘텐츠
notice                (공지사항)
news                  (뉴스)
faq                   (FAQ)
pds                   (자료실)
gallery               (갤러리)
tip                   (팁)
counsel               (상담)
popup                 (팝업)

// 디자인/배너
banner                (배너)
index_banner          (인덱스 배너)
flash                 (플래시)
flash_index           (플래시 인덱스)
copyright             (저작권)
agency                (대리점)
```

**마이그레이션 대상**: 낮음 (현재 사이트 미운영)

---

### 1.4 DC 별도 브랜드 (dc_*) - **~20개**

별도의 DC 브랜드 사이트

```
dc_admin
dc_board
dc_counsel
dc_cyber, dc_cyber_e
dc_faq, dc_faq2
dc_item1, dc_item1_e
dc_item2, dc_item2_e
dc_item3, dc_item3_e
dc_news
dc_notice, dc_notice_e
dc_page
dc_pds, dc_pds_e
```

**마이그레이션 대상**: 매우 낮음 (별도 서버 권장)

---

### 1.5 분석/카운터 시스템 (AceMTcounter_*) - **6개**

방문자 분석 및 추적

```
AceMTcounter_browser   (브라우저 통계)
AceMTcounter_display   (화면 해상도 통계)
AceMTcounter_ip        (IP 통계)
AceMTcounter_now       (현재 방문자)
AceMTcounter_url       (URL별 통계)
// + 추가 테이블들
```

**마이그레이션 대상**: 낮음 (분석 데이터 - 대량 데이터)

---

### 1.6 우편번호 & 기타 - **~6개**

```
zipcode        (우편번호 DB)
zipcode_old    (이전 우편번호)
// 기타
```

**마이그레이션 대상**: 중간 (참조 데이터)

---

## 📈 2. 마이그레이션 우선순위 재정리

### 🔴 우선순위 1: **AS 시스템** (21개 테이블)

**필수 마이그레이션**
```
step1_parts       (1,000개 행 예상)
step2_center      (6개 행)
step3_member      (200개 행 예상)
step13_as         (50,000개 행 예상) ⭐⭐⭐ 최우선
step14_as_item    (150,000개 행 예상) ⭐⭐⭐
step19_as_result  (50,000개 행 예상) ⭐⭐⭐
// + 기타 16개 테이블
```

**소요 시간**: 4-6주  
**복잡도**: ⭐⭐⭐ 높음  
**리스크**: ⭐⭐⭐ 중간-높음

---

### 🟡 우선순위 2: **우편번호** (zipcode)

**선택 마이그레이션**
```
zipcode        (~10,000개 행)
```

**소요 시간**: 1일  
**복잡도**: ⭐ 낮음  
**리스크**: ⭐ 낮음

**용도**: AS 신청 시 주소 검색에 사용

---

### 🟢 우선순위 3: 메인 사이트 (현재 미운영)

**선택 마이그레이션**

메인 사이트가 현재 운영되지 않으므로 우선순위 낮음
- 필요 시 이후에 별도로 마이그레이션

---

### 🔵 우선순위 4: Zboard & DC 브랜드

**낮은 우선순위**

현재 사용 중이 아니면 마이그레이션 연기 가능

---

## 🗄️ 3. 실제 데이터 규모 분석

### 3.1 주요 테이블 데이터 크기 추정

| 테이블 | 용도 | 행 수 (추정) | 크기 | 마이그레이션 필수 |
|--------|------|-----------|------|---------|
| **step13_as** | AS 신청 | 50,000 | ~20MB | 🔴 필수 |
| **step14_as_item** | AS 제품 | 150,000 | ~30MB | 🔴 필수 |
| **step19_as_result** | AS 결과 | 50,000 | ~15MB | 🔴 필수 |
| step1_parts | 부품 | 1,000 | ~1MB | 🔴 필수 |
| step20_sell | 판매 | 10,000 | ~3MB | 🔴 필수 |
| step6_order | 주문 | 20,000 | ~5MB | 🔴 필수 |
| AceMTcounter_* | 분석 | 100,000+ | ~10MB | 🟡 선택 |
| zipcode | 우편번호 | 10,000 | ~2MB | 🟡 선택 |
| dc_* | DC 사이트 | 50,000+ | ~10MB | 🟢 낮음 |
| **합계** | | **~500,000** | **~50MB** | |

---

## 🔄 4. 마이그레이션 전략 (수정 버전)

### Phase 1 (기초 준비) - **1-2주**

✅ **이미 완료**:
- [x] 코드 분석
- [x] DB 스키마 파악
- [x] SQL 덤프 확보 (52MB)

⏳ **진행 중**:
- [ ] 로컬 개발 환경 구성 (WSL2)
- [ ] SQL 덤프를 로컬 MariaDB에 복원
- [ ] 실제 데이터 검증

---

### Phase 2 (AS 시스템 마이그레이션) - **3-6주**

**Step 1: 코어 레이어**
```
@config.php → PDO 변환
필요한 step1-21 테이블만 처리
→ 주요 3개 테이블 (step13, 14, 19) 집중
```

**Step 2: 쿼리 변환**
```
as_center/, sell/, result/, bank/ 모듈
→ SQL Injection 방지 (Prepared Statements)
```

**Step 3: 보안 강화**
```
입력 검증, XSS 방지, CSRF 토큰
비밀번호 해싱
```

**Step 4: 배포 준비**
```
신규 호스팅에 배포
데이터 검증
모니터링 설정
```

---

### 메인 사이트 & DC 브랜드

**현재 미사용이므로 후순위**
- 향후 필요시 별도 계획 수립
- 현재는 AS 시스템만 집중

---

## 🔍 5. EUC-KR → UTF-8MB4 변환 전략

### 현재 상태
```
CREATE TABLE `step13_as` (
  ...
) TYPE=MyISAM AUTO_INCREMENT=50000 CHARACTER SET euckr COLLATE euckr_korean_ci
```

### 변환 목표
```
CREATE TABLE `step13_as` (
  ...
) ENGINE=InnoDB AUTO_INCREMENT=50000 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
```

### 단계별 변환 (로컬)

```bash
# Step 1: 로컬 MariaDB에서
ALTER DATABASE mic4u41_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Step 2: 각 테이블 변환 (AS 시스템 21개만)
ALTER TABLE step1_parts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE step2_center CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
// ... (모든 step* 테이블)

# Step 3: 스토리지 엔진 변경 (MyISAM → InnoDB)
ALTER TABLE step13_as ENGINE=InnoDB;
// ... (모든 step* 테이블)

# Step 4: 데이터 검증
SELECT COUNT(*) FROM step13_as;  -- 50,000 행 확인
SELECT * FROM step13_as LIMIT 5; -- 한글 표시 확인
```

---

## 📋 6. 다음 단계

### 즉시 (이번 주)

1. **WSL2 로컬 환경 구성**
   ```bash
   PHP 7.4 설치
   MariaDB 설치
   ```

2. **SQL 덤프 복원**
   ```bash
   mysql -u root < mic4u41.sql
   ```

3. **로컬 테스트**
   ```php
   // PDO 연결 테스트
   $pdo = new PDO("mysql:host=localhost;dbname=mic4u41;charset=utf8mb4");
   $result = $pdo->query("SELECT COUNT(*) FROM step13_as");
   ```

4. **데이터 검증**
   ```bash
   # 행 수 비교
   # 한글 데이터 표시 확인
   # 주요 필드 샘플 검증
   ```

---

## ⚠️ 7. 주의사항

### 1. 데이터 백업
- [ ] 52MB SQL 파일 외부 저장소에 백업
- [ ] 주간 자동 백업 설정

### 2. 인코딩 변환 주의
```
EUC-KR → UTF-8MB4 변환 시
한글 데이터 손상 가능성 ⚠️

→ 로컬에서 충분히 테스트 후 진행
→ 변환 전 백업 필수
```

### 3. MyISAM → InnoDB 변경
```
현재: MyISAM (테이블 레벨 락)
변경: InnoDB (트랜잭션, 참조 무결성)

→ 성능 테스트 필수
```

---

## 📊 8. 수정된 마이그레이션 일정

| Phase | 기간 | 작업 | 상태 |
|-------|------|------|------|
| **1-1** | 1주 | 로컬 환경 구성 + 데이터 검증 | 🟠 진행 중 |
| **2-1** | 1-2주 | 코어 레이어 (PDO) | 📅 대기 |
| **2-2** | 2-3주 | AS 모듈 변환 | 📅 대기 |
| **2-3** | 1-2주 | 보안 강화 | 📅 대기 |
| **3** | 1-2주 | 배포 & 최적화 | 📅 대기 |
| **합계** | **8-10주** | | |

---

## 🎯 최종 결론

### 발견사항
1. ✅ **SQL 덤프 확보**: 실제 데이터로 정확한 분석 가능
2. ✅ **AS 시스템 명확**: step1-21 테이블로 명확히 분리됨
3. ✅ **EUC-KR 확인**: 예상대로 한글 인코딩
4. ✅ **데이터 규모**: 약 50MB (관리 가능)

### 마이그레이션 대상 확정
- **필수**: AS 시스템 (step1-21) + 우편번호
- **선택**: Zboard 포럼, 메인 사이트 (현재 미운영)
- **제외**: DC 브랜드 (별도 처리)

### 다음 단계
→ **로컬 개발 환경 구성** + **SQL 덤프 복원** + **데이터 검증**

---

**상태**: ✅ 분석 완료 (1단계)  
**다음**: 로컬 환경 구성 (WSL2 + MariaDB)
