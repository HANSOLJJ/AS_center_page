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
