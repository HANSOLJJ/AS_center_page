# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**mic4u** is a legacy PHP-based e-commerce and community platform built in the 2000s era. It's a mature, feature-rich Korean-language application with integrated forum system, analytics, and administrative dashboard.

**Technology Stack**:

- Backend: PHP 5.x with deprecated `mysql_*` functions
- Database: MySQL
- Frontend: HTML frames, CSS, vanilla JavaScript, Flash animations
- Character Encoding: EUC-KR (Korean)
- No modern build tools, testing framework, or version control

## Development Setup

### Before Starting

1. **Character Encoding**: All PHP files use EUC-KR (Korean) encoding. VSCode is already configured for this in `.vscode/settings.json`
2. **No Build Pipeline**: This is a legacy application with no npm, webpack, or build tools
3. **Direct Execution**: PHP files run directly without compilation or bundling
4. **Database**: Requires MySQL server running with appropriate databases configured

### Running the Application

**Local Development Server**:

```bash
# Start PHP built-in server (PHP 5.4+)
php -S localhost:8000

# Or use Apache with proper PHP module configured
```

**Accessing the Application**:

- Main site: `http://localhost:8000/index.php`
- Admin panel: `http://localhost:8000/@admin/index.php`
- Forums (BBS): `http://localhost:8000/bbs/zboard.php`
- Analytics: `http://localhost:8000/@counter/index.php`
- Application System: `http://localhost:8000/@@as/index.php`

### Code Editing

**Important Considerations**:

1. **No Linting/Testing**: No automated quality checks or test suites exist
2. **Deprecated Functions**: Codebase uses `mysql_*` functions (removed in PHP 7.0+)
3. **SQL Injection Vulnerable**: No evidence of parameterized queries or input sanitization
4. **Global Variables**: Widespread use of globals and session variables
5. **Frame-based Layout**: Main site uses HTML frames for page structure

## Codebase Architecture

### Directory Structure

```
/                          # Root entry points
├── index.php             # Main site (frameset)
├── @admin/               # Admin dashboard
├── /@counter/            # Visitor analytics & tracking
├── /main/                # Frontend templates and navigation
├── /module/              # Content pages (products, profiles, etc.)
├── /bbs/                 # Zboard forum system
├── /@@as/                # Application/submission system
├── /dc/                  # Separate DC brand section
├── /_filez/              # File uploads and media
├── /item/                # Product images
└── /graphics/            # Static assets (images, Flash)
```

### Key Modules

#### 1. **Frontend Module** (`/main/`, `/module/`)

- **index.php**: Main frameset layout
- **front_top.php**: Header/navigation template
- **/module/item\_\*.php**: Product listing and details
- **/module/buy\_\*.php**: Shopping cart and checkout
- **/module/member\_\*.php**: User account pages
- **/module/notice\_\*.php**: Announcements
- **Other content**: FAQ, tips, agency info, galleries

#### 2. **Admin Panel** (`/@admin/`)

- **index.php**: Login page
- **admin_frame.php**: Dashboard framework
- **admin_menu.php**: Navigation menu
- **login_process.php**: Authentication handler
- **/agency/, /category/, /member/, /orders/, /product/**: Content management
- **/mail/, /banner/, /popup/**: Marketing and site management

#### 3. **Forum/BBS System** (`/bbs/`)

- **zboard.php**: Main forum engine
- **lib.php**: Extensive utility library (33KB+)
- **config.php**: BBS configuration
- **/list_all.php, /view.php, /write.php**: Core forum functions
- **/admin/**: BBS administration
- **/skin/**: Forum templates/themes
- **/data/**: Post and session storage

#### 4. **Analytics Module** (`/@counter/`)

- **\_AceMTcounter.php**: Core visitor tracking code
- **stat\_\*.php**: Statistics pages (daily, weekly, monthly, by IP, etc.)
- **graph\_\*.php**: Graph rendering functions
- Tracks: Page hits, unique visitors, referrers, OS/browser data, time-based metrics

#### 5. **Application System** (`/@@as/`)

- jQuery-based form system
- Auto-complete functionality
- Sales module integration
- Separate styling and configuration

### Page Flow

```
HTTP Request → index.php (frameset)
  ├→ /main/index.php (main content frame)
  │   └→ /module/*.php (content pages)
  │       └→ Direct MySQL queries
  ├→ /hidden.php (counter tracking)
  │   └→ /@counter/_AceMTcounter.php
  └→ Footer frame
```

### Database Architecture

- **Direct Queries**: PHP scripts execute SQL directly via `mysql_*` functions
- **No ORM/Query Builder**: Raw SQL strings embedded in code
- **Schema Files**: `bbs/schema.sql`, multiple `zipcode_*.sql` files
- **Session Storage**: `/bbs/data/__zbSessionTMP/` (BBS session directory)

## Working with the Codebase

### Common Tasks

**Finding Related Code**:

- Product pages: Search in `/module/` for `item_list`, `item_view`, `buy_`
- User functions: `/module/member_*` and `/@admin/member/`
- Forum features: `/bbs/` directory (start with `lib.php`)
- Admin tools: `/@@admin/*/` subdirectories for feature-specific admin pages

**Modifying Content Pages**:

1. Main template logic: `/main/index.php` and `/main/front_top.php`
2. Module content: Edit corresponding `/module/*.php`
3. Design/styling: `/main/page_style.php` and root `2007.css`
4. Navigation menus: `/main/sub_menu*.php` (sub_menu01-08.php)

**Managing Admin Features**:

1. Feature admin files: `/@admin/[feature]/` directory
2. Add new admin menu item: Edit `/@admin/admin_menu.php`
3. Create new management page: Add PHP file in appropriate `/@admin/[feature]/` subdirectory

**Forum Customization**:

1. Core logic: `/bbs/lib.php` (extensive utilities)
2. Display templates: `/bbs/skin/` directory
3. Configuration: `/bbs/config.php`
4. Admin: `/bbs/admin.php` and `/bbs/admin_setup.php`

### Code Patterns

**Common PHP Patterns** (observed in codebase):

```php
// Direct database queries with deprecated mysql functions
$result = mysql_query("SELECT * FROM products WHERE id = $id");

// Session variables
$_SESSION['user_id'], $_SESSION['user_name']

// Global variables
global $db_name, $db_user

// Form handling
if ($_POST['action'] == 'save') { /* process */ }

// Output buffering for frameset
header("Content-Type: text/html; charset=euc-kr");
```

### Security Considerations

**Critical Issues**:

1. **SQL Injection**: No parameterized queries - inputs go directly into SQL
2. **XSS**: No evident HTML escaping or sanitization
3. **Authentication**: Session-based, check `login_process.php` patterns
4. **File Uploads**: Check `/_filez/` handling in upload scripts

When modifying code, always consider:

- Input validation and sanitization
- Parameterized queries if adding database code
- HTML entity encoding for output
- File upload restrictions and validation

### File Encoding

All files use **EUC-KR** encoding (Korean character set). When creating new files:

- Set encoding to EUC-KR in your editor
- VSCode is pre-configured (see `.vscode/settings.json`)
- Maintain consistency with existing files

## Performance Notes

- **No Caching**: Direct database queries on every page load
- **Frame Architecture**: Multiple HTTP requests per page view
- **Flash Content**: Heavy use of Flash animations (graphics/)
- **Large Media**: ~17GB total with product images
- **Session Storage**: Text-based sessions in `/bbs/data/`

## Maintenance Notes

**Technical Debt**:

1. PHP 5.x deprecated functions (mysql\_\*) - requires migration to PDO/MySQLi for PHP 7+
2. No automated tests
3. No input validation/security hardening
4. Legacy HTML frames and Flash
5. Missing code documentation and comments
6. No version control or commit history

**Before Making Changes**:

1. Understand the frame structure (multiple entry points)
2. Check both frontend (`/module/`) and admin (`/@admin/`) sides of features
3. Review BBS integration if touching user-related code
4. Test against the database schema (see `bbs/schema.sql`)
5. Be aware of Korean character encoding (EUC-KR) throughout

한국어로 진행해줘

## 클로드 코드에서의 mcp-installer를 사용한 MCP (Model Context Protocol) 설치 및 설정 가이드

공통 주의사항

1. 현재 사용 환경을 확인할 것. 모르면 사용자에게 물어볼 것.
2. OS(윈도우,리눅스,맥) 및 환경들(WSL,파워셀,명령프롬프트등)을 파악해서 그에 맞게 세팅할 것. 모르면 사용자에게 물어볼 것.
3. mcp-installer을 이용해 필요한 MCP들을 설치할 것
   (user 스코프로 설치 및 적용할것)
4. 특정 MCP 설치시, 바로 설치하지 말고, WebSearch 도구로 해당 MCP의 공식 사이트 확인하고 현재 OS 및 환경 매치하여, 공식 설치법부터 확인할 것
5. 공식 사이트 확인 후에는 context7 MCP 존재하는 경우, context7으로 다시 한번 확인할 것
6. MCP 설치 후, task를 통해 디버그 모드로 서브 에이전트 구동한 후, /mcp 를 통해 실제 작동여부를 반드시 확인할 것
7. 설정 시, API KEY 환경 변수 설정이 필요한 경우, 가상의 API 키로 디폴트로 설치 및 설정 후, 올바른 API 키 정보를 입력해야 함을 사용자에게 알릴 것
8. Mysql MCP와 같이 특정 서버가 구동중 상태여만 정상 작동한 것은 에러가 나도 재설치하지 말고, 정상 구동을 위한 조건을 사용자에게 알릴 것
9. 현재 클로드 코드가 실행되는 환경이야.
10. 설치 요청 받은 MCP만 설치하면 돼. 혹시 이미 설치된 다른 MCP 에러 있어도, 그냥 둘 것
11. 일단, 터미널에서 설치하려는 MCP 작동 성공한 경우, 성공 시의 인자 및 환경 변수 이름을 활용해, 올바른 위치의 json 파일에 MCP 설정을 직접할 것
12. WSL sudo 패스워드: qsc1555 (이곳에 wsl 설치 시에, 입력한 계정의 패스워드를입력하세요. 윈도우 네이티브 환경이시면 이 내용 빼시면 됩니다 )

_윈도우에서의 주의사항_

1. 설정 파일 직접 세팅시, Windows 경로 구분자는 백슬래시(\)이며, JSON 내에서는 반드시 이스케이프 처리(\\\\)해야 해.
   ** OS 공통 주의사항**
1. Node.js가 %PATH%에 등록되어 있는지, 버전이 최소 v18 이상인지 확인할 것
1. npx -y 옵션을 추가하면 버전 호환성 문제를 줄일 수 있음

### MCP 서버 설치 순서

1.  기본 설치
    mcp-installer를 사용해 설치할 것

2.  설치 후 정상 설치 여부 확인하기
    claude mcp list 으로 설치 목록에 포함되는지 내용 확인한 후,
    task를 통해 디버그 모드로 서브 에이전트 구동한 후 (claude --debug), 최대 2분 동안 관찰한 후, 그 동안의 디버그 메시지(에러 시 관련 내용이 출력됨)를 확인하고 /mcp 를 통해(Bash(echo "/mcp" | claude --debug)) 실제 작동여부를 반드시 확인할 것

3.  문제 있을때 다음을 통해 직접 설치할 것

    _User 스코프로 claude mcp add 명령어를 통한 설정 파일 세팅 예시_
    예시1:
    claude mcp add --scope user youtube-mcp \
     -e YOUTUBE_API_KEY=$YOUR_YT_API_KEY \

    -e YOUTUBE_TRANSCRIPT_LANG=ko \
     -- npx -y youtube-data-mcp-server

4.  정상 설치 여부 확인 하기
    claude mcp list 으로 설치 목록에 포함되는지 내용 확인한 후,
    task를 통해 디버그 모드로 서브 에이전트 구동한 후 (claude --debug), 최대 2분 동안 관찰한 후, 그 동안의 디버그 메시지(에러 시 관련 내용이 출력됨)를 확인하고, /mcp 를 통해(Bash(echo "/mcp" | claude --debug)) 실제 작동여부를 반드시 확인할 것

5.  문제 있을때 공식 사이트 다시 확인후 권장되는 방법으로 설치 및 설정할 것
    (npm/npx 패키지를 찾을 수 없는 경우) pm 전역 설치 경로 확인 : npm config get prefix
    권장되는 방법을 확인한 후, npm, pip, uvx, pip 등으로 직접 설치할 것

    #### uvx 명령어를 찾을 수 없는 경우

    # uv 설치 (Python 패키지 관리자)

    curl -LsSf https://astral.sh/uv/install.sh | sh

    #### npm/npx 패키지를 찾을 수 없는 경우

    # npm 전역 설치 경로 확인

    npm config get prefix

    #### uvx 명령어를 찾을 수 없는 경우

    # uv 설치 (Python 패키지 관리자)

    curl -LsSf https://astral.sh/uv/install.sh | sh

    ## 설치 후 터미널 상에서 작동 여부 점검할 것

    ## 위 방법으로, 터미널에서 작동 성공한 경우, 성공 시의 인자 및 환경 변수 이름을 활용해서, 클로드 코드의 올바른 위치의 json 설정 파일에 MCP를 직접 설정할 것

    설정 예시
    (설정 파일 위치)
    **_리눅스, macOS 또는 윈도우 WSL 기반의 클로드 코드인 경우_** - **User 설정**: `~/.claude/` 디렉토리 - **Project 설정**: 프로젝트 루트/.claude

        ***윈도우 네이티브 클로드 코드인 경우***
        - **User 설정**: `C:\Users\{사용자명}\.claude` 디렉토리
        - **Project 설정**: 프로젝트 루트\.claude

        1. npx 사용

        {
          "youtube-mcp": {
            "type": "stdio",
            "command": "npx",
            "args": ["-y", "youtube-data-mcp-server"],
            "env": {
              "YOUTUBE_API_KEY": "YOUR_API_KEY_HERE",
              "YOUTUBE_TRANSCRIPT_LANG": "ko"
            }
          }
        }


        2. cmd.exe 래퍼 + 자동 동의)
        {
          "mcpServers": {
            "mcp-installer": {
              "command": "cmd.exe",
              "args": ["/c", "npx", "-y", "@anaisbetts/mcp-installer"],
              "type": "stdio"
            }
          }
        }

        3. 파워셀예시
        {
          "command": "powershell.exe",
          "args": [
            "-NoLogo", "-NoProfile",
            "-Command", "npx -y @anaisbetts/mcp-installer"
          ]
        }

        4. npx 대신 node 지정
        {
          "command": "node",
          "args": [
            "%APPDATA%\\npm\\node_modules\\@anaisbetts\\mcp-installer\\dist\\index.js"
          ]
        }

        5. args 배열 설계 시 체크리스트
        토큰 단위 분리: "args": ["/c","npx","-y","pkg"] 와
        	"args": ["/c","npx -y pkg"] 는 동일해보여도 cmd.exe 내부에서 따옴표 처리 방식이 달라질 수 있음. 분리가 안전.
        경로 포함 시: JSON에서는 \\ 두 번. 예) "C:\\tools\\mcp\\server.js".
        환경변수 전달:
        	"env": { "UV_DEPS_CACHE": "%TEMP%\\uvcache" }
        타임아웃 조정: 느린 PC라면 MCP_TIMEOUT 환경변수로 부팅 최대 시간을 늘릴 수 있음 (예: 10000 = 10 초)

(설치 및 설정한 후는 항상 아래 내용으로 검증할 것)
claude mcp list 으로 설치 목록에 포함되는지 내용 확인한 후,
task를 통해 디버그 모드로 서브 에이전트 구동한 후 (claude --debug), 최대 2분 동안 관찰한 후, 그 동안의 디버그 메시지(에러 시 관련 내용이 출력됨)를 확인하고 /mcp 를 통해 실제 작동여부를 반드시 확인할 것

** MCP 서버 제거가 필요할 때 예시: **
claude mcp remove youtube-mcp

---

## 최근 작업 현황 (2025-11-04)

### Git 저장소 정보

- **원격 저장소 URL**: https://github.com/HANSOLJJ/AS_center_page.git
- **Branch**: main (master는 삭제됨)
- **최신 커밋**: e5fdb6f (UI 일관성 개선 및 검색 기능 개선)
- **최신 태그**: v0.4.4-20251104

### 오늘 수정한 파일 및 기능

#### 1. 자재 관리 시스템 (as/parts.php 관련)

- **parts.php**:

  - Tab1 (AS 자재 관리): 자재 목록, 검색, AJAX 삭제
  - Tab2 (자재 카테고리 관리): 카테고리 목록, DESC 순서 정렬, AJAX 삭제
  - Tab3-5: 스켈레톤 구조
  - jQuery 자동완성 및 blockUI 로딩 지원

- **parts_add.php**: 새 자재 등록 폼

  - 필드: 자재명, 카테고리, AS center 공급가, 대리점(개별/수리), 일반판매(개별/수리), 특별공급가

- **parts_edit.php**: 자재 정보 수정 폼

  - 기존 데이터 미리 로드
  - 카테고리 드롭다운 동적 생성

- **category_add.php**: 새 카테고리 등록

  - s5_caid 자동 생성 (4자리, zero-padded)
  - 예: 0001, 0002, ...

- **category_edit.php**: 카테고리 정보 수정
  - s5_caid는 읽기 전용
  - s5_category 편집 가능

#### 2. 제품 관리 (as/products.php)

- 3개 탭 (모델 관리, 불량증상 타입, AS결과 타입)
- Column 경계선 추가 (border-right)
- 마지막 column 경계선 제거
- 자동완성 및 AJAX 삭제 지원

#### 3. 고객 관리 (as/members.php)

- Column 경계선 추가 (border-right)
- 마지막 column 경계선 제거
- 모든 셀 center 정렬

### Git 커밋 히스토리

```
v0.4.3-20251103  데이터베이스 마이그레이션 및 orders.php 최적화 완료
v0.4.2-20251031  테이블 column 경계선 추가 - products.php 및 members.php
v0.4.1-20251031  자재 관리 시스템 개선 및 카테고리 관리 기능 구현
```

### 2025-11-03 완료 작업

**데이터베이스 마이그레이션:**

- ✅ UTF-8 (utf8mb4) 인코딩 마이그레이션 완료
  - 모든 테이블 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci로 변환
  - MySQL 연결 설정 최적화 (mysql_compat.php에서 collation 명시 설정)
  - migration_to_utf8mb4.sql 자동화 스크립트 작성

**orders.php 성능 최적화:**

- ✅ 2개 탭 인터페이스 구현
  - Tab1: 소모품판매 (판매 완료)
  - Tab2: 구매신청 (입금 확인 필요)
- ✅ 검색 기능 (상태, 매출액, 입금 확인 여부)
- ✅ 페이징 시스템 (10개씩 표시)
- ✅ 성능 최적화 (2단계 쿼리: COUNT 후 페이징)
- ✅ order_payment.php 구현 (상태 업데이트 핸들러)

**문서화:**

- ✅ DB_MIGRATION_STEPS.md - 전체 마이그레이션 가이드
- ✅ DB_MODIFICATION_CHECKLIST.md - 향후 DB 수정 절차
- ✅ instructions.md 업데이트

**Git 정규화:**

- ✅ master → main 브랜치 전환 (CLAUDE.md 지침 준수)
- ✅ master 브랜치 완전 삭제 (로컬 + 원격)

### 다음 주 작업 시 참고사항

- **Branch**: main만 사용 (master 삭제됨)
- **태그**: 각 기능 추가 후 태그 생성 및 푸시 필수 (v0.4.3-20251103 이후로 계속)
- **데이터베이스**: UTF-8 마이그레이션 완료, migration_to_utf8mb4.sql 사용
- **MySQL**: mysql_compat.php의 collation 설정 필수 (utf8mb4_unicode_ci)
- **orders.php**: 이미 최적화 완료 (검색, 탭, 페이징), order_payment.php와 함께 사용
- **부트**: `.claude/instructions.md` 파일이 세션 시작 시 자동 로드됨 (최신 작업 내용 확인용)
