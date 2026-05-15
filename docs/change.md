# DB 변경 이력

## 2026-02-24: MyISAM → InnoDB 엔진 변환

### 원인
- `dcom.co.kr/as` 로그인 시 dashboard.php에서 무한 대기(타임아웃) 발생
- `step13_as` 테이블에 대한 대형 SELECT 쿼리가 `Copying to tmp table` 상태로 56분간 실행
- **MyISAM의 테이블 레벨 락**으로 인해 후속 쿼리(COUNT, UPDATE 등)가 전부 대기 상태로 누적
- 로그인 성공 → dashboard.php → `SELECT COUNT(*) FROM step13_as` → 락 대기 → 타임아웃

### 변경 내용
- AS 시스템의 `step*` 테이블 12개를 MyISAM → **InnoDB**로 변환
- PHP 소스코드 변경 없음 (코드 호환성 검증 완료)

### 대상 테이블

| 테이블 | 행 수 | 변환 전 크기 | 설명 |
|--------|--------|-------------|------|
| step11_member | 886 | 0.08MB | 고객 |
| step13_as | 33,073 | 9.02MB | AS 작업 |
| step14_as_item | 81,251 | 6.38MB | AS 아이템 |
| step15_as_model | 187 | 0.01MB | AS 모델 |
| step16_as_poor | 8 | 0.00MB | 불량 유형 |
| step18_as_cure_cart | 132,146 | 11.25MB | AS 수리 카트 |
| step19_as_result | 7 | 0.00MB | AS 결과 |
| step1_parts | 404 | 0.03MB | 자재 |
| step20_sell | 14,904 | 2.76MB | 자재 판매 |
| step21_sell_cart | 28,039 | 2.34MB | 판매 카트 |
| step2_center | 4 | 0.00MB | AS 센터 |
| step5_category | 18 | 0.00MB | 카테고리 |

### 실행 SQL
```sql
ALTER TABLE step11_member ENGINE=InnoDB;
ALTER TABLE step13_as ENGINE=InnoDB;
ALTER TABLE step14_as_item ENGINE=InnoDB;
ALTER TABLE step15_as_model ENGINE=InnoDB;
ALTER TABLE step16_as_poor ENGINE=InnoDB;
ALTER TABLE step18_as_cure_cart ENGINE=InnoDB;
ALTER TABLE step19_as_result ENGINE=InnoDB;
ALTER TABLE step1_parts ENGINE=InnoDB;
ALTER TABLE step20_sell ENGINE=InnoDB;
ALTER TABLE step21_sell_cart ENGINE=InnoDB;
ALTER TABLE step2_center ENGINE=InnoDB;
ALTER TABLE step5_category ENGINE=InnoDB;
```

### 백업
- 변환 전 백업 파일: `/home/hosting_users/dcom2000/backup_before_innodb_20260224.sql` (35MB)

### InnoDB 변환 장단점

**장점**
- 행(Row) 레벨 락 → 테이블 락 문제 근본 해결
- 크래시 복구 지원 (MyISAM은 서버 다운 시 테이블 손상 가능)
- 동시 접속 성능 향상
- 트랜잭션(COMMIT/ROLLBACK) 지원

**단점**
- 디스크 용량 약 1.5~2배 증가 (32MB → ~50MB, 미미)
- WHERE 없는 COUNT(*) 약간 느림 (해당 패턴 미사용)

---

## 2026-03-12: 리포트 내보내기 금월 기간 버그 수정

### 증상

- statistics.php 개요 탭의 금월 기간: **전월 26일 ~ 당월 25일** (회계 마감 기준)
- export_as_report.php, export_sales_report.php의 `range=month` 기간: **당월 1일 ~ 오늘** (달력 기준)
- 화면에서는 1/26~2/25 (30건) 표시되지만, 리포트 다운로드 시 2/1~오늘 데이터만 출력되는 불일치

### 수정 사항

- `export_as_report.php` - `range=month` 기간을 회계 마감 기준(전월 26일~당월 25일)으로 수정
- `export_sales_report.php` - 동일 수정
- `export_as_report.php` - 파일명 `AS처리 리포트` → `AS처리_리포트_` (언더스코어 통일)

### 대상 파일

- `as/stat/export_as_report.php`
- `as/stat/export_sales_report.php`

### 배포 방법

- SSH MCP 서버(`ssh-mcp-server`)를 통해 dcom.co.kr 서버에 직접 업로드
- 서버 경로: `/home/hosting_users/dcom2000/www/as/stat/`

---

## 2026-05-15: PHP 7.x → 8.2 업그레이드 대응 및 s18_signdate 옛 손상 데이터 발견

### 배경

호스팅의 PHP 가 7.x 에서 8.2 로 업그레이드된 직후 다음 두 동작에서 빈 응답이 와서 `SyntaxError: Failed to execute 'json' on 'Response': Unexpected end of JSON input` 발생.

- AS 작업 → **AS 요청 등록** (POST `as_request_handler.php?action=save_as_request`)
- AS 작업 → **AS 수리 자재 등록** (POST `as_repair_handler.php?action=save_repair_step`)

핸들러 상단에 임시 디버그 로깅을 깔아서 잡은 fatal 두 가지.

```
[request] mysqli_sql_exception: Unknown column 's13_product' in 'field list'
[repair]  mysqli_sql_exception: Data truncated for column 's18_signdate' at row 1
```

### 근본 원인

**PHP 8.1+ 부터 `mysqli` 기본 에러 모드가 silent → `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`(예외 throw) 로 바뀜**. 옛 코드는 `@mysql_query(...)` + 결과 false 체크 패턴으로 silent 동작을 가정해 짜여 있어, `@`로도 예외를 막지 못해 핸들러 전체가 죽음 → 빈 응답.

추가 분석으로 두 가지 옛 결함이 silent fail 에 가려져 있었던 것이 확인됨.

| 위치 | 결함 | 비고 |
|---|---|---|
| `as_request_handler.php` 의 `UPDATE step13_as SET s13_product = ...` (두 곳) | `step13_as.s13_product` 컬럼이 처음부터 존재하지 않음 | 운영 DB 와 5월 15일 13:14 백업 dump 둘 다에서 컬럼 부재 확인. 코드 어디에서도 SELECT 안 함 |
| `as_repair_handler.php` 의 `$signdate = date('Y-m-d H:i:s')` | `s18_signdate` 가 `int(10) unsigned` 인데 datetime 문자열을 박음 | 옛 환경에서는 숫자 prefix 4자리만 저장 + warning 으로 통과됐음. PHP 8.1+ strict 에서는 truncation 이 fatal |

### 코드 변경

1. `as/mysql_compat.php` — `mysqli_report(MYSQLI_REPORT_OFF);` 추가 (옛 silent 동작 복원, 다른 잠재 silent-fail 의존 패턴도 fatal 안 나게 보호)
2. `as/as_task/as_request_handler.php` — `s13_product` UPDATE 라인 두 곳(`save_as_request`, `update_as_request` 액션 각각) 통째 제거
3. `as/as_task/as_repair_handler.php` — `$signdate = date('Y-m-d H:i:s')` → `$signdate = time()`

### 발견된 옛 손상 데이터 (s18_signdate)

위 fix 적용 후 신규 자재 등록 row 는 정상 unix timestamp 로 저장되는 것을 확인 (예: `1778834757` → `2026-05-15 17:45:57`). 그런데 DB 점검 중 **685행이 `2025`/`2026` 같은 4자리 값으로 들어있는 것**을 추가 발견.

| 항목 | 값 |
|---|---|
| 손상된 행 수 | 685 (`step18_as_cure_cart` 전체 132,401 행 중) |
| s18_accid 범위 | 137577 ~ 138319 |
| 해당 자재가 속한 AS 등록 시각 (`s13_as_in_date`) 범위 | 2025-11-18 13:55 ~ 2026-05-14 18:39 |
| 시작 시점 | 2025-11-18 (`as_repair_handler.php` 의 `$signdate = date(...)` 코드가 그 즈음부터 운영된 듯). PHP 8.2 업그레이드 이전 |
| 손상 값 출처 | datetime 문자열 `'2026-05-14 18:39:02'` → MySQL 이 `int unsigned` 로 캐스팅하면서 숫자 prefix `2026` 만 저장 |

### 보정하지 않기로 결정한 이유

월/일/시/분/초 정보는 **영구히 손실**되어 정확한 자재 등록 시각을 어디서도 복원 불가. `step13_as.s13_as_in_date` 로 채우는 방안도 검토했지만, 이는 *자재 등록 시각* 이 아니라 *그 AS 의 등록 시각* 으로 추측치이며, 실제 자재 등록은 며칠 후 일어났을 가능성이 더 큼. 추측 값을 박아두면 향후 누군가 이 컬럼을 신뢰할 위험.

영향 평가 결과 **`s18_signdate` 는 코드 어디에서도 SELECT/조회되지 않음**. 따라서 손상된 값을 그대로 두어도 화면/리포트/통계 모두 영향 0.

| 검증 항목 | 결과 |
|---|---|
| 판매 리포트 (`export_sales_report.php`, `export_monthly_report.php`) | `step18` 미사용. step20/21 만 참조 |
| AS 리포트 (`export_as_report.php`) | step18 JOIN 하지만 시점 기준은 `s13_as_out_date`. s18_signdate 미사용 |
| 통계 (`statistics.php` TOP10 교체자재 / TOP3 수리자재) | 날짜 필터 기준 `s13_as_out_date`. s18_signdate 미사용 |
| 영수증 (`as_receipt.php`) | SELECT 컬럼에 s18_signdate 미포함 |
| AS 작업 화면 (`as_requests.php`, `as_repair.php` 등) | s18_signdate 미참조 |
| FK 제약 | `step18_as_cure_cart` 에 FK 0건. 다른 테이블이 참조하지도 않음 |
| view/trigger/routine | dcom2000 DB 전체에 0개 |

### 미보정 손상 데이터 식별 쿼리

향후 자재 등록 시각이 필요해질 때 손상 범위를 다시 식별하려면.

```sql
SELECT s18_accid, s18_asid, s18_signdate
FROM step18_as_cure_cart
WHERE s18_signdate < 1000000000;
-- 결과: 685행, s18_accid 137577~138319
```

### 대상 파일

- `as/mysql_compat.php`
- `as/as_task/as_request_handler.php`
- `as/as_task/as_repair_handler.php`

### 배포 방법

- SSH MCP 서버(`ssh-mcp-server`)로 dcom.co.kr 서버 `/home/hosting_users/dcom2000/www/as/` 하위에 업로드
- 임시 디버그 로그(`debug_php82.log`) 제거 완료

### 검증

- AS 요청 등록 / AS 수리 자재 등록 두 동작 모두 정상 응답 확인
- 신규 INSERT row 의 `s18_signdate` 가 10자리 unix timestamp 정수로 저장됨 확인 (예: 138321 행 → `1778834757` = 2026-05-15 17:45:57)
- 핸들러 상단의 임시 디버그 코드 모두 원복
