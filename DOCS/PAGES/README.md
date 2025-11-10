# PAGES 문서

mic4u AS 시스템의 현재 구현된 페이지들의 구조, 역할, 기능을 설명합니다.

**마지막 업데이트**: 2025-11-06

---

## 📚 문서 목록

### 1. ORDERS_SYSTEM_STRUCTURE.md
**자재 판매 시스템 (Orders System) 구조**

**포함 내용**:
- 시스템 흐름도 (신규 주문 → 목록 조회 → 수정 → 상태 업데이트 → 영수증)
- 5개 핵심 페이지 상세 설명
- API 액션별 요청/응답 구조
- 상태 전이도
- 페이지 요청/응답 흐름
- SQL 쿼리 예제

**관련 페이지**:
- order_handler.php - 신규 주문 등록
- orders.php - 주문 목록 및 관리 (탭 기반)
- order_edit.php - 주문 수정
- order_payment.php - 주문 상태 업데이트
- receipt.php - 영수증 출력

---

## 🎯 주요 페이지 구조

### AS 시스템 페이지 (as/ 폴더)

#### 1. as_requests.php
**역할**: AS 신청 목록 및 관리

**탭 구조**:
- Tab1: AS 요청 (s13_as_level < 2)
- Tab2: AS 진행 (s13_as_level IN 2, 3, 4)
- Tab3: AS 완료 (s13_as_level = 5)

**기능**:
- 탭 전환
- 검색 (업체명, 상태 등)
- 페이징 (10개씩)
- 수정/삭제 버튼
- 입고 품목 상세 보기

**데이터 구조**:
- 주요 테이블: step13_as, step14_as_item
- 관계 JOIN: step15_as_model, step16_as_poor, step11_member

---

#### 2. as_request_handler.php
**역할**: AS 신청 생성/수정/삭제 처리 (AJAX API)

**지원 액션**:
- search_member - 업체명 검색
- add_member - 신규 업체 등록
- get_parts - 자재 검색
- save_as_request - AS 신청 저장
- delete_as_request - AS 신청 삭제
- add_as_item - AS 항목 추가
- delete_as_item - AS 항목 삭제

**AJAX 응답 형식**:
- JSON: {success: true/false, data: {...}, message: "..."}

---

#### 3. as_request_view.php
**역할**: AS 신청 상세 조회

**표시 정보**:
- AS 신청 기본 정보
- 포함된 항목 목록
- 처리 담당자 정보
- 비용 및 세금 정보
- 배송 정보

---

#### 4. dashboard.php
**역할**: AS 시스템 대시보드

**표시 내용**:
- 주요 통계 (입고, 처리 중, 완료)
- 최근 신청 목록
- 통계 차트
- 빠른 메뉴

---

### 자재 관리 페이지 (as/parts.php 관련)

#### 1. parts.php
**역할**: 자재 관리 시스템 (탭 기반)

**탭 구조**:
- Tab1: AS 자재 관리 (검색, 목록, AJAX 삭제)
- Tab2: 자재 카테고리 관리 (DESC 순서, AJAX 삭제)
- Tab3-5: 스켈레톤 구조 (확장 예정)

**기능**:
- 자재 검색 및 목록 표시
- 자재 추가 (parts_add.php)
- 자재 수정 (parts_edit.php)
- 자재 삭제 (AJAX)
- 카테고리 관리

**데이터**:
- 주요 테이블: step1_parts, step5_category
- 필드: 자재명, 카테고리, AS센터공급가, 대리점가, 일반판매가, 특별공급가

---

#### 2. parts_add.php
**역할**: 새 자재 등록 폼

**입력 필드**:
- 자재명
- 카테고리 (드롭다운)
- AS센터 공급가
- 대리점 (개별, 수리)
- 일반판매 (개별, 수리)
- 특별공급가

---

#### 3. parts_edit.php
**역할**: 자재 정보 수정 폼

**특징**:
- 기존 데이터 미리 로드
- 카테고리 드롭다운 동적 생성

---

### 제품 관리 페이지 (as/products.php 관련)

#### 1. products.php
**역할**: 제품/모델 관리 (탭 기반)

**탭 구조**:
- Tab1: 모델 관리
- Tab2: 불량증상 타입 관리
- Tab3: AS결과 타입 관리

**기능**:
- 목록 표시
- 검색
- 추가 (폼 페이지로 이동)
- 수정 (폼 페이지로 이동)
- 삭제 (AJAX)

**스타일**:
- 테이블 컬럼 경계선 추가 (border-right)
- 마지막 컬럼 경계선 제거
- 자동완성 지원
- blockUI 로딩 표시

---

#### 2. product_add.php
**역할**: 새 제품/모델 등록 폼

---

#### 3. product_edit.php
**역할**: 제품/모델 정보 수정 폼

---

### 고객 관리 페이지 (as/members.php 관련)

#### 1. members.php
**역할**: 고객/거래처 관리

**기능**:
- 회원 목록 표시
- 검색
- 추가
- 수정
- 삭제 (AJAX)

**표시 정보**:
- 회원명/업체명
- 연락처
- 분류 (개인/업체/딜러)
- 사업자번호 (선택)

---

#### 2. member_add.php
**역할**: 새 회원 등록 폼

**입력 필드**:
- 회원명/업체명
- 담당자
- 연락처 (여러 개)
- 분류
- 주소
- 사업자번호 (선택)

---

#### 3. member_edit.php
**역할**: 회원 정보 수정 폼

---

### 판매 관리 페이지 (as/orders.php 관련)

#### 1. orders.php
**역할**: 자재 판매 주문 관리

**탭 구조**:
- Tab1: 판매 요청 (s20_as_level='1')
- Tab2: 판매 완료 (s20_as_level='2')

**기능**:
- 탭 전환
- 검색 (상태, 매출액, 입금 여부)
- 페이징 (10개씩)
- 수정 (order_edit.php)
- 삭제 (order_handler.php)
- 상태 업데이트 (order_payment.php)
- 영수증 조회 (receipt.php 새창)

---

#### 2. order_handler.php
**역할**: 신규 판매 주문 등록 (AJAX API)

**지원 액션**:
- search_member - 업체 검색
- add_member - 신규 업체 등록
- get_parts - 자재 검색 (회원 구분별 가격)
- save_order - 주문 저장

---

#### 3. order_edit.php
**역할**: 기존 판매 주문 수정

**기능**:
- 기존 자재 목록 표시
- 자재 추가 (중복 체크)
- 수량 변경
- 자재 삭제

**AJAX 액션**:
- get_parts - 자재 검색
- add_part - 자재 추가
- update_quantity - 수량 변경
- delete_cart_item - 자재 삭제

---

#### 4. order_payment.php
**역할**: 판매 주문 상태 업데이트

**지원 액션**:
- complete - 판매 완료 (접수번호 생성)
- confirm - 입금 확인
- cancel - 판매 완료 취소

**접수번호 생성 로직**:
- s20_as_in_no: NO + YYMMDD + - + 3자리 순번
- s20_as_in_no2: YYMMDD + 3자리 순번

---

#### 5. receipt.php
**역할**: 판매 영수증 출력

**특징**:
- 새창 팝업
- 주문 정보 + 자재 목록 표시
- 인쇄 기능
- 닫기 버튼

---

## 🔄 페이지 흐름도

### AS 요청 프로세스
```
as_request_handler.php (search/add)
  ↓
as_requests.php (list/search)
  ├─ [수정] → as_request_view.php → as_request_handler.php (update)
  └─ [삭제] → as_request_handler.php (delete)
```

### 판매 주문 프로세스
```
order_handler.php (create)
  ↓
orders.php (list/search)
  ├─ [수정] → order_edit.php → order_handler.php (update items)
  ├─ [삭제] → order_handler.php (delete)
  ├─ [상태변경] → order_payment.php (update status)
  └─ [영수증] → receipt.php (view)
```

---

## 💡 공통 패턴

### 탭 기반 인터페이스
- as_requests.php (3탭)
- parts.php (5탭)
- products.php (3탭)
- orders.php (2탭)

**특징**:
- jQuery 탭 전환
- 탭별 검색/필터링
- 페이징 (일부)

### AJAX API 패턴
- order_handler.php
- as_request_handler.php
- 기타 삭제/추가 함수들

**응답 형식**:
```json
{
  "success": true/false,
  "data": {...},
  "message": "..."
}
```

### 폼 페이지 패턴
- parts_add.php / parts_edit.php
- products_add.php / products_edit.php
- members_add.php / members_edit.php
- order_edit.php

**특징**:
- 기존 데이터 미리 로드 (edit)
- 드롭다운 동적 생성
- 저장 시 list 페이지로 리다이렉트

---

## 🎨 UI 스타일 일관성

### 테이블 스타일
- 헤더: 보라색 그래디언트 배경
- 행: 흰색 / #f9f9ff 교대로
- 액션 버튼: edit(파란색), delete(빨간색), view(초록색)
- 컬럼 경계선: border-right (마지막 제외)

### 폼 스타일
- 라벨 + 입력 필드
- 필수 필드: 빨간색 *
- 버튼: 파란색 (save), 회색 (cancel)

### 검색/필터
- 텍스트 입력
- 드롭다운 선택
- 조건 추가 버튼

---

## 📝 다음 개발 계획

### 확장 예정
1. parts.php의 Tab3-5 구현
2. 불량증상/AS결과 관리 UI 개선
3. 통계 및 리포트 기능
4. SMS/이메일 알림 기능

### 개선 예정
1. 검색 기능 고도화 (다중 조건)
2. 벌크 작업 (일괄 삭제, 일괄 상태변경)
3. 엑셀 다운로드
4. 모바일 반응형 디자인

---

**마지막 수정**: 2025-11-06
