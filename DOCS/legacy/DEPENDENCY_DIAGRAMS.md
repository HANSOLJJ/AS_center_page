# AS 시스템 의존성 - 시각적 다이어그램

**목적**: 파일 간 의존성을 시각화하여 이해도 향상  
**작성 도구**: Mermaid 다이어그램

---

## 1. 전체 아키텍처 다이어그램

```
┌─────────────────────────────────────────────────────────────┐
│                      AS 시스템 아키텍처                      │
└─────────────────────────────────────────────────────────────┘

                          ┌─────────────┐
                          │ login.php   │
                          └──────┬──────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
                    ▼                         ▼
            ┌─────────────────┐      ┌────────────────┐
            │  dashboard.php  │      │ admin_frame.php│
            │  (메인 메뉴)      │      │  (레거시)       │
            └────────┬────────┘      └────────────────┘
                     │
        ┌────┬───────┼───────┬────┬──────┐
        ▼    ▼       ▼       ▼    ▼      ▼
    ┌────────────┐  ┌──────────┐  ┌──────────┐
    │ parts.php  │  │products.php│  │members.php│
    │ (자재관리)  │  │ (제품관리) │  │ (고객관리) │
    └────────────┘  └──────────┘  └──────────┘
        │                │              │
        ├─add            ├─add         ├─add
        ├─edit           ├─edit        └─edit
        └─delete         └─delete
        
        ┌──────────────────┬──────────────────┐
        ▼                  ▼                  ▼
    ┌────────────┐  ┌──────────┐  ┌────────────────┐
    │ orders.php │  │as_requests│  │as_repair.php   │
    │(주문관리)   │  │(AS요청관리)│  │(AS수리처리)     │
    └────────────┘  └──────────┘  └────────────────┘
        │                │              │
        └─handler        └─handler      └─handler
            │                │          │
            ▼                ▼          ▼
    ┌───────────────┐  ┌──────────────┐  ┌──────────────┐
    │order_handler  │  │as_request_   │  │as_repair_    │
    │.php           │  │handler.php   │  │handler.php   │
    │(AJAX)         │  │(AJAX)        │  │(AJAX)        │
    └───────────────┘  └──────────────┘  └──────────────┘

모든 파일이 의존하는 공통 계층:

┌─────────────────────────────────────────────┐
│          mysql_compat.php                   │
│    (MySQL → MySQLi 호환성 레이어)            │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│        Docker MySQL Service                 │
│  Host: mysql                                │
│  DB: mic4u                                  │
│  Charset: utf8mb4                           │
└─────────────────────────────────────────────┘
```

---

## 2. 자재 관리 (Parts) 모듈 흐름

```
┌──────────────────────────────────────────────┐
│           parts.php (메인 페이지)             │
│  ├─ Tab1: AS 자재 관리                       │
│  └─ Tab2: 자재 카테고리 관리                  │
└────────────┬─────────────────────────────────┘
             │
    ┌────────┼────────┐
    ▼        ▼        ▼
┌─────────┐ ┌──────────┐ ┌────────┐
│ 목록    │ │ 검색     │ │ 페이징 │
│ 조회    │ │ 기능     │ │ (10개) │
└────┬────┘ └──────────┘ └────────┘
     │
     └──────────┬──────────┐
                ▼          ▼
        ┌────────────────┐  ┌────────────────┐
        │ [새 자재 등록] │  │ [자재 정보 수정]│
        │ parts_add.php  │  │ parts_edit.php │
        └────────────────┘  └────────────────┘
                │                  │
                └──────────┬────────┘
                           ▼
                ┌──────────────────────┐
                │ step1_parts 테이블    │
                │ INSERT/UPDATE/DELETE │
                └──────────────────────┘

카테고리 관리:
        │ [새 카테고리 등록]  [카테고리 수정]
        └─ category_add.php  category_edit.php
                │
                ▼
        ┌──────────────────┐
        │step5_category    │
        │ INSERT/UPDATE    │
        └──────────────────┘
```

---

## 3. 제품 관리 (Products) 모듈 흐름

```
┌──────────────────────────────────────────────┐
│         products.php (메인 페이지)            │
│  ├─ Tab: model    (제품 모델 관리)           │
│  ├─ Tab: poor     (불량증상 타입)           │
│  └─ Tab: result   (AS 결과 타입)            │
└────────────┬─────────────────────────────────┘
             │
    ┌────────┼────────┐
    ▼        ▼        ▼
┌─────────┐ ┌──────────┐ ┌────────┐
│ 목록    │ │ 검색     │ │ 페이징 │
│ 조회    │ │ 기능     │ │ (10개) │
└─────────┘ └──────────┘ └────────┘

Tab: model
    │
    ├─ [모델 추가]
    │  product_add.php → step15_as_model INSERT
    │
    ├─ [모델 수정]
    │  product_edit.php → step15_as_model UPDATE
    │
    └─ [모델 삭제]
       → step15_as_model DELETE

Tab: poor
    │
    ├─ [불량증상 추가]
    │  poor_add.php → step16_as_poor INSERT
    │
    ├─ [불량증상 수정]
    │  poor_edit.php → step16_as_poor UPDATE
    │
    └─ [불량증상 삭제]
       → step16_as_poor DELETE

Tab: result
    │
    ├─ [결과 추가]
    │  result_add.php → step19_as_result INSERT
    │
    ├─ [결과 수정]
    │  result_edit.php → step19_as_result UPDATE
    │
    └─ [결과 삭제]
       → step19_as_result DELETE
```

---

## 4. AS 요청 관리 흐름

```
┌─────────────────────────────────────┐
│     as_requests.php (메인)           │
│  ├─ Tab: request    (AS 요청)       │
│  ├─ Tab: working    (AS 진행)       │
│  ├─ Tab: completed  (AS 완료)       │
│  └─ Tab: spare      (Spare)         │
└────────────┬────────────────────────┘
             │
             ├─ [목록 조회]
             │  WHERE s13_as_level != '2'
             │  LEFT JOIN step11_member
             │  LEFT JOIN step14_as_item
             │
             ├─ [검색]
             │  고객명, 전화, 기간
             │
             └─ [상세 조회 클릭]
                │
                ▼
            ┌──────────────────────────┐
            │ as_request_view.php      │
            │ (AS 요청 상세 페이지)     │
            │                          │
            │ 조회 테이블:             │
            │ ├─ step13_as            │
            │ ├─ step14_as_item       │
            │ ├─ step18_as_cure_cart  │
            │ ├─ step11_member        │
            │ └─ step1_parts          │
            └──────────────────────────┘

AJAX 통신:
┌──────────────────────────────────────┐
│      as_request_handler.php          │
│                                      │
│ Action: get_as_request_data          │
│  → 기존 AS 요청 정보 로드 (수정용)     │
│                                      │
│ Action: load_step3_data              │
│  → 제품 모델 & 불량증상 목록 로드     │
└──────────────────────────────────────┘

데이터 흐름:
step13_as (AS 요청 기본정보)
    ├─ step14_as_item (아이템 목록)
    │   ├─ step15_as_model (제품명)
    │   └─ step16_as_poor (불량증상명)
    │
    └─ step18_as_cure_cart (사용 자재)
        └─ step1_parts (자재명)
```

---

## 5. AS 수리 처리 흐름

```
┌──────────────────────────────┐
│    as_repair.php             │
│    (Step-by-Step 수리 처리)    │
└──────────────┬───────────────┘
               │
        ┌──────┴──────┐
        ▼             ▼
    Step 1        Step 2        Step 3
  [기본정보]     [수리방법]    [자재입력]
    │             │             │
    ▼             ▼             ▼
 ┌─────────────────────────────────┐
 │  as_repair_handler.php (AJAX)    │
 │                                 │
 │ Action: save_repair_step        │
 │  • 수리 방법 선택               │
 │  • 자재 정보 저장               │
 │  → step18_as_cure_cart 수정     │
 │                                 │
 │ Action: load_parts             │
 │  • 부품 목록 로드 (페이징)       │
 │  → step1_parts 조회             │
 └─────────────────────────────────┘
               │
        ┌──────┴──────┬──────┐
        ▼             ▼      ▼
    [자재 선택]  [비용계산] [수량입력]
        │             │       │
        └──────┬──────┴───────┘
               ▼
        step18_as_cure_cart
        (수리용 자재 저장)
               │
               └─ step1_parts (조인)
                  (자재 정보 조회)
```

---

## 6. 주문 관리 흐름

```
┌───────────────────────────────┐
│    orders.php                 │
│  ├─ Tab: request  (판매요청)   │
│  └─ Tab: completed (판매완료)  │
└────────────┬──────────────────┘
             │
    ┌────────┼────────┐
    ▼        ▼        ▼
┌─────────┐ ┌──────────┐ ┌────────┐
│ 목록    │ │ 검색     │ │ 페이징 │
│ 조회    │ │ 기능     │ │ (10개) │
└─────────┘ └──────────┘ └────────┘

검색 옵션:
  • 고객명 (ex_company)
  • 전화번호 (ex_tel)
  • 기간 (s20_sell_in_date 또는 s20_as_out_date)

[주문 클릭]
    │
    ├─ [주문 수정]
    │  order_edit.php → step20_sell UPDATE
    │
    ├─ [입금 확인]
    │  order_payment.php → s20_bank_check UPDATE
    │
    ├─ [영수증]
    │  receipt.php
    │  ├─ step20_sell 조회
    │  └─ step21_sell_cart 조회 (상세)
    │
    └─ [삭제]
       order_handler.php?action=delete_order
       ├─ step21_sell_cart DELETE (자식 먼저)
       └─ step20_sell DELETE

고객 관리:
[고객 검색] → order_handler.php?action=search_member
  │
  └─ [찾는 고객 없음]
     [새 고객 추가] → order_handler.php?action=add_member
     → step11_member INSERT
```

---

## 7. 고객 관리 흐름

```
┌──────────────────────────────┐
│    members.php               │
│   (고객 정보 관리)            │
└───────────┬──────────────────┘
            │
    ┌───────┼───────┐
    ▼       ▼       ▼
┌────────┐ ┌──────────┐ ┌────────┐
│ 목록   │ │ 검색     │ │ 페이징 │
│ 조회   │ │ 기능     │ │ (10개) │
└────────┘ └──────────┘ └────────┘

검색 옵션:
  • 업체명 (s11_com_name)
  • 전화번호 (s11_phone1/2/3)
  • 전체 검색

액션:
[새 고객 추가] → member_add.php
  │
  └─ step11_member INSERT
      ├─ s11_sec (분류)
      ├─ s11_com_name (업체명)
      ├─ s11_phone1/2/3 (전화)
      └─ 기타 필드 (기본값)

[고객 정보 수정] → member_edit.php
  │
  └─ step11_member UPDATE

[삭제] → AJAX DELETE
  POST members.php
  { action: 'delete', id: 123 }
  │
  └─ step11_member DELETE (외래키 제약 주의)
```

---

## 8. 데이터베이스 관계도

```
step13_as (AS 요청 헤더)
    │
    ├─ s13_meid ──┐
    │             │
    │             ▼
    │         step11_member (고객)
    │             ├─ s11_meid (PK)
    │             ├─ s11_com_name
    │             └─ s11_phone1/2/3
    │
    └─ s13_asid ──┐
                  │
                  ▼
          step14_as_item (AS 아이템)
                  │
                  ├─ s14_model ──┐
                  │              │
                  │              ▼
                  │          step15_as_model
                  │              ├─ s15_amid (PK)
                  │              ├─ s15_model_name
                  │              └─ s15_model_sn
                  │
                  ├─ s14_poor ──┐
                  │             │
                  │             ▼
                  │         step16_as_poor
                  │             ├─ s16_apid (PK)
                  │             └─ s16_poor
                  │
                  └─ s14_aiid ──┐
                                │
                                ▼
                        step18_as_cure_cart (수리 자재)
                                │
                                ├─ s18_uid ──┐
                                │            │
                                │            ▼
                                │        step1_parts
                                │            ├─ s1_uid (PK)
                                │            ├─ s1_name
                                │            ├─ s1_caid
                                │            └─ s1_cost_*
                                │
                                └─ s1_caid ──┐
                                             │
                                             ▼
                                         step5_category
                                             ├─ s5_caid (PK)
                                             └─ s5_category


step20_sell (판매 주문 헤더)
    │
    ├─ s20_meid ──┐
    │             │
    │             ▼
    │         step11_member (고객)
    │
    └─ s20_sellid ──┐
                    │
                    ▼
            step21_sell_cart (판매 아이템)
                    │
                    └─ s21_uid ──┐
                                 │
                                 ▼
                             step1_parts
```

---

## 9. 공통 포함 파일 의존성 트리

```
모든 as/ 페이지
    │
    └─── mysql_compat.php (세션 불필요)
         ├─ PHP 7.4+ 호환성
         ├─ mysqli 래핑
         └─ UTF-8 설정
         
또는

as/ 페이지 (관리자)
    │
    ├─── session_start()
    │    └─ $_SESSION 확인
    │
    ├─── @session_inc.php (선택적)
    │    ├─ mysql_compat.php
    │    └─ 세션 초기화
    │
    ├─── @config.php (선택적, 레거시)
    │    ├─ @session_inc.php
    │    ├─ @access.php
    │    ├─ @error_function.php
    │    └─ 전역 변수 설정
    │
    └─── mysql_compat.php
         └─ mysqli 연결
```

---

## 10. 페이지 접근 흐름 (사용자 관점)

```
사용자 접근 흐름:

1. 로그인
   login.php → 세션 생성 → dashboard.php

2. 자재 관리 워크플로우
   dashboard.php
   └─ [자재 관리] 버튼 클릭
      └─ parts.php
         ├─ [새 자재 등록] → parts_add.php (POST) → parts.php
         ├─ [수정] → parts_edit.php (POST) → parts.php
         └─ [삭제] → GET ?action=delete_part → parts.php

3. AS 요청 워크플로우
   dashboard.php
   └─ [AS 요청 관리] 버튼 클릭
      └─ as_requests.php
         ├─ [탭 선택] (request/working/completed)
         ├─ [상세 조회] → as_request_view.php
         ├─ [수리 시작] → as_repair.php
         │   └─ [AJAX] as_repair_handler.php
         └─ [삭제] → GET ?action=delete → as_requests.php

4. 주문 관리 워크플로우
   dashboard.php
   └─ [주문 관리] 버튼 클릭
      └─ orders.php
         ├─ [탭 선택] (request/completed)
         ├─ [고객 검색] 
         │   └─ [AJAX] order_handler.php?action=search_member
         ├─ [주문 수정] → order_edit.php
         ├─ [입금 확인] → order_payment.php
         ├─ [영수증] → receipt.php
         └─ [삭제] → [AJAX] order_handler.php?action=delete_order
```

---

## 11. 데이터 입출력 패턴

```
입력 (CREATE/INSERT)
┌──────────────────┐
│ 폼 페이지         │
│ (*_add.php)       │
└────────┬─────────┘
         │ (POST)
         ▼
┌──────────────────┐
│ 유효성 검사       │
│ ├─ 필드 확인      │
│ ├─ 타입 체크      │
│ └─ 범위 체크      │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ mysql_* 함수     │
│ INSERT           │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 리다이렉트       │
│ ?inserted=1      │
└──────────────────┘

조회 (READ/SELECT)
┌──────────────────┐
│ 페이지 요청       │
│ (GET 파라미터)    │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ WHERE 조건 생성   │
│ ├─ 검색어        │
│ ├─ 필터          │
│ └─ 정렬          │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ mysql_query()    │
│ SELECT           │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ mysql_fetch_*()  │
│ 결과 조회        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ HTML 렌더링      │
└──────────────────┘

수정 (UPDATE)
┌──────────────────┐
│ 수정 폼 페이지     │
│ (*_edit.php)     │
└────────┬─────────┘
         │ (POST)
         ▼
┌──────────────────┐
│ 기존 데이터 로드   │
│ (SELECT)         │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 폼 필드 채우기     │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 유효성 검사       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ mysql_query()    │
│ UPDATE           │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 리다이렉트       │
│ ?edited=1        │
└──────────────────┘

삭제 (DELETE)
┌──────────────────┐
│ DELETE 요청       │
│ (GET/POST)       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 권한 확인        │
│ 보안 체크        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 외래키 체크      │
│ (자식 먼저 삭제) │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ mysql_query()    │
│ DELETE           │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ 리다이렉트       │
│ ?deleted=1       │
└──────────────────┘
```

---

## 12. 보안 검사 흐름

```
모든 요청 처리 전
    │
    ├─ [1] 세션 확인
    │   └─ $_SESSION['member_id'] 존재?
    │       └─ NO → login.php로 리다이렉트
    │       └─ YES → 계속
    │
    ├─ [2] 권한 확인 (필요시)
    │   └─ $_SESSION['member_level'] 확인?
    │       └─ NO → 에러 페이지
    │       └─ YES → 계속
    │
    ├─ [3] 입력 검증
    │   ├─ 필수 필드 확인
    │   ├─ 데이터 타입 체크
    │   ├─ 범위/길이 체크
    │   └─ NO → 에러 메시지 표시
    │
    ├─ [4] SQL 인젝션 방지
    │   └─ mysql_real_escape_string() 또는 intval()
    │
    ├─ [5] 외래키 제약 확인
    │   └─ DELETE 시 자식 데이터 먼저 처리
    │
    └─ [6] 실행
        └─ mysql_query() 실행

에러 처리:
    │
    ├─ DB 에러
    │   └─ mysql_error() 로깅
    │   └─ 사용자에게 일반 메시지
    │
    ├─ 논리 에러
    │   └─ 영향받은 행 수 확인
    │   └─ mysql_affected_rows()
    │
    └─ 성공
        └─ 리다이렉트 또는 JSON 응답
```

---

**생성일**: 2025-11-10  
**다이어그램 유형**: ASCII + Mermaid (호환성 고려)  
**주의**: 실제 구현은 CLAUDE.md 및 DEPENDENCY_ANALYSIS.md 참조

