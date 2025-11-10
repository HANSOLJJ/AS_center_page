# AS 시스템 의존성 분석 - 최종 요약

**생성일**: 2025-11-10  
**분석 범위**: as/ 폴더 주요 PHP 파일 (7개 모듈, 40+ 파일)  
**상태**: 완료 및 검증됨

---

## 핵심 요약

### 1. 모듈 구조 (7개)

| # | 모듈 | 메인 페이지 | 용도 | 테이블 |
|----|------|-----------|------|--------|
| 1 | 자재 관리 | parts.php | AS용 자재 관리 | step1_parts, step5_category |
| 2 | 제품 관리 | products.php | 모델, 불량증상, 결과 타입 | step15_as_model, step16_as_poor, step19_as_result |
| 3 | 고객 관리 | members.php | 고객사 정보 관리 | step11_member |
| 4 | 주문 관리 | orders.php | 판매 주문 및 입금 관리 | step20_sell, step21_sell_cart |
| 5 | AS 요청 | as_requests.php | AS 요청 상태 관리 | step13_as, step14_as_item |
| 6 | AS 수리 | as_repair.php | AS 수리 처리 | step14_as_item, step18_as_cure_cart |
| 7 | 대시보드 | dashboard.php | 메인 메뉴 및 네비게이션 | 2010_admin_member |

### 2. 데이터베이스 테이블 (21개 활용 중)

**핵심 테이블** (자주 사용):
- step1_parts (자재)
- step5_category (카테고리)
- step11_member (고객)
- step13_as (AS 요청)
- step14_as_item (AS 아이템)
- step15_as_model (제품)
- step16_as_poor (불량증상)
- step18_as_cure_cart (수리 자재)
- step20_sell (판매)
- step21_sell_cart (판매 아이템)

**참조 테이블**:
- step2_center (AS 센터)
- step19_as_result (AS 결과)
- 2010_admin_member (관리자)

### 3. 공통 의존성

**필수 포함**:
```php
require_once 'mysql_compat.php';
```

**선택적 포함**:
```php
require_once '@config.php';      // 레거시 방식
require_once '@session_inc.php'; // 세션 초기화
require_once '@access.php';      // 권한 확인
```

### 4. 보안 체크리스트

필수 항목:
- [x] 세션 확인 (`$_SESSION['member_id']`)
- [x] SQL 이스케이프 (`mysql_real_escape_string()`)
- [x] 타입 캐스팅 (intval() 등)
- [x] 외래키 제약 처리 (DELETE 시)
- [ ] CSRF 토큰 (미구현, 개선 필요)
- [ ] XSS 방지 (htmlspecialchars 미사용, 개선 필요)

### 5. 통신 패턴

**직접 호출**: parts_add.php → parts.php (리다이렉트)  
**AJAX 호출**: members.php ↔ AJAX (JSON)  
**핸들러 호출**: orders.php → order_handler.php (JSON)  

---

## 파일별 의존성 매트릭스

```
파일                    mysql_  @config  @session  @access  @error  테이블들
                       compat  .php     _inc.php  .php     _func

parts.php              O                                              s1, s5
parts_add.php          O                                              s1, s5
parts_edit.php         O                                              s1, s5
category_add/edit.php  O                                              s5

products.php           O                                              s15, s16, s19
product_add/edit.php   O                                              s15
poor_add/edit.php      O                                              s16
result_add/edit.php    O                                              s19

members.php            O                                              s11
member_add/edit.php    O                                              s11

orders.php             O                                              s20, s21, s11
order_handler.php      O                                              s11, s20, s21
order_edit.php         O                                              s20
order_payment.php      O                                              s20
receipt.php            O                                              s20, s21

as_requests.php        O                                              s13, s14, s11, s15, s16
as_request_handler.php O                                              s13, s14, s15, s16
as_request_view.php    O        O        O        O        O         s13, s14, s18, s11, s15, s16, s1

as_repair.php          O                                              s14, s18, s1
as_repair_handler.php  O                                              s14, s18, s1

dashboard.php          O                                              2010_
```

---

## 개발 시 주의사항

### 1. 페이징은 필수

**모든 목록 페이지**는 페이징 구현:
- 기본 10개씩 표시
- `LIMIT $limit OFFSET $offset` 사용
- `COUNT(*)` 로 총 개수 조회

```php
$total = 100;
$per_page = 10;
$page = max(1, intval($_GET['page']));
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total / $per_page);
```

### 2. 외래키 제약 확인 필수

**DELETE 작업 시**:
```php
// 1단계: 자식 데이터 먼저 삭제
DELETE FROM step14_as_item WHERE s14_asid = $id
// 2단계: 부모 데이터 삭제
DELETE FROM step13_as WHERE s13_asid = $id
```

### 3. 성공/실패 메시지 처리

**성공**: 리다이렉트 + GET 파라미터
```php
header('Location: parts.php?tab=tab1&inserted=1');
```

**페이지에서 확인**:
```php
if (isset($_GET['inserted'])) {
    $success_message = '저장되었습니다.';
}
```

### 4. 리다이렉트 후 항상 exit

```php
header('Location: ...');
exit;  // 필수!
```

---

## 파일 추가 시 체크리스트

### 신규 페이지 추가

- [ ] mysql_compat.php require_once
- [ ] session_start() + $_SESSION 확인
- [ ] 데이터베이스 테이블 명시
- [ ] 포함 파일 목록 문서화
- [ ] AJAX 필요 여부 결정
- [ ] 보안 검토 (SQL 인젝션, XSS)

### AJAX 핸들러 추가

- [ ] JSON 헤더 설정
- [ ] action 파라미터 처리
- [ ] 에러 처리
- [ ] 응답 형식 일관성

---

## 성능 최적화 포인트

### 현재 상태
- 페이징: ✓ (구현됨)
- 인덱싱: ? (DB 스키마 확인 필요)
- 캐싱: ✗ (미사용)
- 쿼리 최적화: ? (N+1 문제 확인 필요)

### 개선 기회
1. **데이터베이스 인덱싱**
   - s13_meid, s14_asid 등 FK 필드
   - s13_as_level, s20_as_level 상태 필드
   - 검색 필드 (com_name, model_name 등)

2. **쿼리 최적화**
   - LEFT JOIN 사용 시 필요한 필드만 SELECT
   - COUNT(*) 별도 쿼리로 분리 (이미 구현됨)

3. **메모리 캐싱** (향후)
   - Redis 도입 고려
   - 마스터 데이터 캐싱 (category, model, poor 등)

---

## 마이그레이션 로드맵

### Phase 1: 현재 상태 유지 (안정성)
- [x] UTF-8 마이그레이션 완료
- [x] mysql_compat.php 최적화
- [x] 의존성 분석 완료

### Phase 2: 보안 강화 (3-6개월)
- [ ] CSRF 토큰 추가
- [ ] XSS 방지 (htmlspecialchars)
- [ ] Prepared Statements 검토
- [ ] 권한 기반 접근 제어 (RBAC)

### Phase 3: 현대화 (6-12개월)
- [ ] PHP 최신 버전 호환성 (8.0+)
- [ ] ORM 도입 검토 (Eloquent, Doctrine)
- [ ] 마이크로서비스 아키텍처 검토

---

## 문서 구조

본 분석 결과는 4개 문서로 구성:

1. **DEPENDENCY_ANALYSIS.md** (상세 분석)
   - 각 파일의 포함 관계 상세 설명
   - 테이블별 필드 정의
   - AJAX 통신 흐름
   - 보안 고려사항

2. **DEPENDENCY_QUICK_REFERENCE.md** (빠른 참조)
   - 파일별 필수 포함
   - 테이블 필드 요약
   - 주요 쿼리 패턴
   - 보안 필드 체크

3. **DEPENDENCY_DIAGRAMS.md** (시각화)
   - ASCII 다이어그램
   - 모듈별 흐름도
   - 데이터 관계도
   - 접근 흐름

4. **DEPENDENCY_ANALYSIS_SUMMARY.md** (본 문서)
   - 핵심 요약
   - 체크리스트
   - 성능 포인트
   - 로드맵

---

## 주요 발견사항

### 긍정적 측면
1. **일관된 구조**: 모든 페이지가 동일한 패턴 따름
2. **모듈화**: 기능별로 잘 분리됨
3. **문자 인코딩**: UTF-8로 성공적 마이그레이션
4. **호환성**: mysql_compat.php로 PHP 7.4+ 지원

### 개선 필요 영역
1. **보안**: CSRF 토큰, XSS 방지 필요
2. **입력 검증**: 더 강화된 유효성 검사
3. **에러 처리**: 사용자 친화적 메시지
4. **문서화**: 코드 내 주석 부족
5. **테스트**: 자동화 테스트 부재

---

## 빠른 답변 가이드

### Q: 새로운 페이지를 만들려면?
A: DEPENDENCY_QUICK_REFERENCE.md의 "신규 페이지 추가" 체크리스트 참조

### Q: 특정 페이지가 어떤 테이블을 사용?
A: DEPENDENCY_ANALYSIS.md의 "파일별 역할" 섹션 또는 매트릭스 참조

### Q: AS 요청과 주문의 관계?
A: DEPENDENCY_DIAGRAMS.md의 "데이터베이스 관계도" 참조

### Q: 새 필드를 추가하려면?
A: 해당 테이블의 필드 정의 섹션 확인 후 FK 관계 검토

### Q: AJAX 추가 시 패턴?
A: DEPENDENCY_ANALYSIS.md의 "AJAX 및 핸들러" 섹션 또는 order_handler.php 참조

---

## 연락처 및 유지보수

**최종 검토**: Human Review Required  
**분석자**: Claude Code (AI Assistant)  
**분석 도구**: Grep, Glob, Read 기반 정적 분석  
**신뢰도**: High (모든 활성 파일 검사됨)

---

## 체크리스트 (완료 상태)

### 분석 단계
- [x] 전체 파일 구조 파악
- [x] 포함 관계 분석
- [x] 데이터베이스 테이블 맵핑
- [x] AJAX 통신 흐름 분석
- [x] 보안 이슈 식별
- [x] 성능 포인트 식별

### 문서화 단계
- [x] 상세 분석 보고서 작성
- [x] 빠른 참조 가이드 작성
- [x] 시각적 다이어그램 작성
- [x] 최종 요약 작성

### 검증 단계
- [x] 의존성 관계 교차 검증
- [x] URL 파라미터 검증
- [x] 테이블 FK 관계 검증
- [x] 문서 일관성 검증

---

## 최종 결론

**as/ 시스템은 잘 구조화된 레거시 PHP 애플리케이션**입니다.

**강점**:
- 명확한 모듈 구조 (7개)
- 일관된 개발 패턴
- 적절한 데이터 정규화

**약점**:
- 보안 강화 필요 (CSRF, XSS)
- 현대적 프레임워크 미사용
- 자동화 테스트 부재

**권장사항**:
1. 즉시: 보안 문제 해결 (CSRF 토큰, htmlspecialchars)
2. 단기: 사용자 권한 강화 (RBAC)
3. 중기: 현대적 스택 고려 (PHP 8.0+, Laravel/Symfony)
4. 장기: 마이크로서비스 아키텍처 검토

---

**분석 완료일**: 2025-11-10  
**예상 업데이트**: 분기별 (변경사항 발생 시)

