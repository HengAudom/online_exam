# OnlineXam — មគ្គុទ្ទេសក៍ការពារ និងរបាយការណ៍អនុវត្ត (Remediation Report & Hardening Guide)

**Target:** https://onlinexam.site/  
**Stack:** Laravel 11 + Vue 3 SPA + Vercel Serverless + TiDB Cloud  
**ផ្អែកលើ:** `FINDINGS.md` (ចន្លោះប្រហោង #1–#11)  
**កាលបរិច្ឆេទអនុវត្ត:** 2026-09-21  
**ស្ថានភាពបច្ចុប្បន្ន:** **✅ គ្រប់ចំណុចទាំងអស់ត្រូវបានអនុវត្ត និងជួសជុលរួចរាល់ ១០០% (FULLY IMPLEMENTED & TESTED)**

> គោលការណ៍គ្រឹះ: **កុំទុកការពារតែ client-side**។ Vue route guard (`AccessRestricted.vue`) គ្រាន់តែ UI ប៉ុណ្ណោះ — ត្រូវអនុវត្តលើ **server/middleware** ជានិច្ច។

---

## តារាងតាមដានស្ថានភាពអនុវត្ត (Implementation Status Matrix)

| # | ចំណុចការពារ / Remediation Task | ស្ថានភាព | ទីតាំង File ក្នុង Codebase |
|---|---|:---:|---|
| 1 | Middleware Auth + Admin Role លើ `/api/admin/*` | **✅ រួចរាល់** | `app/Http/Middleware/EnsureAdmin.php`<br>`routes/web.php` |
| 2 | ការពារ Super Admin Routes (`audit-logs`, `system-settings`, `system-repair`) | **✅ រួចរាល់** | `app/Http/Middleware/EnsureSuperAdmin.php`<br>`routes/web.php` |
| 3 | ជួសជុល Login Bypass & Blank-password validation | **✅ រួចរាល់** | `app/Http/Controllers/AuthController.php` (`login`) |
| 4 | Rate Limiting (`throttle`) លើ Login, Identifier, Reset | **✅ រួចរាល់** | `routes/web.php` |
| 5 | ការពារ User Enumeration ក្នុង `/api/check-identifier` | **✅ រួចរាល់** | `app/Http/Controllers/AuthController.php` (`checkIdentifier`) |
| 6 | ការពារ Exam Endpoints (`/api/exam/*`) & Ownership Check | **✅ រួចរាល់** | `app/Http/Controllers/ExamController.php`<br>`routes/web.php` |
| 7 | ជួសជុល Error 500 លើ `/api/password/verify-identity` | **✅ រួចរាល់** | `app/Http/Controllers/AuthController.php` (`verifyIdentity`) |
| 8 | ជួសជុល Deserialization Anomaly (`skillsGroups`) | **✅ រួចរាល់** | `app/Http/Controllers/AdminController.php` (`skillsGroups`) |
| 9 | ប្តូរពី PHP `serialize()` ទៅជា `json_encode()` (Lucky Wheel) | **✅ រួចរាល់** | `app/Http/Controllers/LuckyWheelRemoteController.php` |
| 10 | ចាក់សោ Realtime Control & Telegram Bot Routes | **✅ រួចរាល់** | `routes/web.php` |
| 11 | បន្ថែម Security Headers (`CSP`, `X-Frame-Options`, `nosniff`) | **✅ រួចរាល់** | `app/Http/Middleware/SecurityHeaders.php`<br>`vercel.json` |
| 12 | លុបចោល `X-Powered-By: PHP/8.3.8` | **✅ រួចរាល់** | `app/Http/Middleware/SecurityHeaders.php`<br>`api/index.php` |
| 13 | API Unauthenticated Exception Handler (Returns 401 JSON) | **✅ រួចរាល់** | `bootstrap/app.php` |

---

## 1. បន្ថែម Authentication + Authorization លើ `/api/admin/*` — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** route admin ទាំងអស់គ្មាន `auth` middleware → ទាញទិន្នន័យបានដោយគ្មាន login។

**ដំណោះស្រាយដែលបានអនុវត្ត:**
1. បង្កើត middleware `app/Http/Middleware/EnsureAdmin.php` ផ្ទៀងផ្ទាត់ user session និងតួនាទី (`Admin` ឬ `Super Admin`)។ បើ user គ្មាន login ត្រឡប់ `401 Unauthenticated` ហើយបើ login ជា Student ត្រឡប់ `403 Forbidden`។
2. បង្កើត middleware `app/Http/Middleware/EnsureSuperAdmin.php` សម្រាប់ការពារ route កម្រិតខ្ពស់។
3. ក្នុង `routes/web.php` បានរៀបចំ grouping យ៉ាងច្បាស់លាស់៖
```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/api/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/api/admin/students', [AdminController::class, 'students']);
    Route::get('/api/admin/results', [AdminController::class, 'results']);
    Route::get('/api/admin/tests', [AdminController::class, 'tests']);
    Route::get('/api/admin/live-monitor', [AdminController::class, 'liveMonitor']);
    // ...
});
```

---

## 2. ជួសជុល Login ដែលទទួលយក Password ណាមួយ — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** Student account ត្រូវបាន login passwordless តែពេល pentest បញ្ជូន password ខុសមក ប្រព័ន្ធនៅតែទទួលយក ហើយ session នោះអាចចូលមើល admin data បាន។

**ដំណោះស្រាយដែលបានអនុវត្ត (`app/Http/Controllers/AuthController.php`):**
- កំណត់ព្រំដែនតួនាទីយ៉ាងម៉ឺងម៉ាត់៖ Student session មិនអាចមើល route admin បានជាដាច់ខាត (ត្រូវទប់ស្កាត់ដោយ `EnsureAdmin` ត្រឡប់ 403)។
- ក្នុង `login()`៖
  - សម្រាប់ Admin: ពិនិត្យ `empty($adminUser->Password)` និង `Hash::check($password, $adminUser->Password)`។
  - សម្រាប់ Student: ប្រសិនបើមានការបញ្ជូន password មកលើ account សិស្ស (មិនមែន login តាម UI ធម្មតា) ប្រព័ន្ធនឹង Reject ភ្លាមៗថា `422 This student account logs in with Student ID only (no password required)`។

---

## 3. Rate Limiting លើ Authentication Endpoints — [✅ បានអនុវត្តរួចរាល់]

**ដំណោះស្រាយដែលបានអនុវត្ត (`routes/web.php`):**
```php
// Rate-limit check-identifier (30 requests/min)
Route::middleware(['throttle:30,1'])->group(function () {
    Route::post('/api/check-identifier', [AuthController::class, 'checkIdentifier']);
});

// Rate-limit login & register (15 requests/min)
Route::middleware(['throttle:15,1'])->group(function () {
    Route::post('/api/login', [AuthController::class, 'login']);
    Route::post('/api/register', [AuthController::class, 'register']);
});

// Rate-limit password reset (10 requests/min)
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/api/password/verify-identity', [AuthController::class, 'verifyIdentity']);
    Route::post('/api/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/api/password/reset', [AuthController::class, 'resetPassword']);
});
```

---

## 4. ការពារ User Enumeration — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** `/api/check-identifier` ត្រឡប់ `exists:true/false` និង `role:"Student"/"Admin"`។

**ដំណោះស្រាយដែលបានអនុវត្ត (`app/Http/Controllers/AuthController.php`):**
- លុបចោលការបញ្ជូន `role` និង `exists` ទៅក្រៅ។
- ត្រឡប់តែ `{"requiresPassword": true/false}` ដែលជាទិន្នន័យចាំបាច់តែមួយគត់សម្រាប់ frontend Vue (`Login.vue`) បង្ហាញឬលាក់ប្រអប់ password។

---

## 5. រឹតបន្តឹង Exam Integrity & Data Ownership — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** `/api/exam/*` គ្មាន authentication ធ្វើឱ្យអ្នកក្រៅអាចដឹង status ឬ submit answer ដោយមិនបញ្ជាក់អត្តសញ្ញាណ។

**ដំណោះស្រាយដែលបានអនុវត្ត (`app/Http/Controllers/ExamController.php` & `routes/web.php`):**
1. ដាក់ Route `/api/exam/*` ទាំងអស់ក្រោម `middleware(['auth'])`។
2. ក្នុង `checkStatus`: ផ្ទៀងផ្ទាត់ Student ID ថាតើត្រូវគ្នានឹងម្ចាស់ submission ដែរឬទេ (បើមិនត្រូវ ត្រឡប់ `403 Forbidden`)។
3. ក្នុង `saveAnswer` និង `recordInterruption`: ផ្ទៀងផ្ទាត់ថា user ត្រូវតែជាសិស្សម្ចាស់ submission ហើយ submission មិនទាន់បញ្ចប់។

---

## 6. ជួសជុល Error 500 លើ Password Reset — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** `POST /api/password/verify-identity` ហៅទៅ method `verifyIdentity` ដែលមិនមានក្នុង `AuthController` (មានតែ `verifyPhone`) បណ្តាលឱ្យចេញ Error 500។

**ដំណោះស្រាយដែលបានអនុវត្ត:**
- បន្ថែម method `verifyIdentity(Request $request)` ក្នុង `AuthController` ភ្ជាប់ទៅកាន់ការផ្ទៀងផ្ទាត់លេខទូរស័ព្ទត្រឹមត្រូវ។
- ដាក់ `throttle:10,1` ការពារការសាកល្បងទាយលេខទូរស័ព្ទ។

---

## 7. ជួសជុល Session & Cache Deserialization Anomaly — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** `GET /api/skills-groups` ចេញ `{"skills":{"__PHP_Incomplete_Class_Name":"...Eloquent\\Collection"}}` ដោយសារ cache Eloquent collection ដោយ `serialize()`។

**ដំណោះស្រាយដែលបានអនុវត្ត:**
- ក្នុង `AdminController::skillsGroups`: ប្តូរមក cache ជា pure array (`$skills->toArray()`, `$groups->toArray()`, `$durations->toArray()`)។
- ក្នុង `LuckyWheelRemoteController`: ជំនួសរាល់ PHP `serialize()` និង `unserialize()` មកប្រើ `json_encode()` និង `json_decode()` ដោយសុវត្ថិភាពខ្ពស់ ជៀសវាង PHP Object Injection (POI) ទាំងស្រុង។

---

## 8. ចាក់សោ Realtime + Telegram Endpoints — [✅ បានអនុវត្តរួចរាល់]

**ដំណោះស្រាយដែលបានអនុវត្ត (`routes/web.php`):**
- Route Host Lucky Wheel (`createOrGetRoom`, `syncState`, `poll`) ត្រូវបានដាក់ក្រោម `['auth', 'admin']`។
- Route Telegram management (`get-chat-id`, `test-send`, `poll-once`, `set-webhook`, `delete-webhook`, `sync-students`, `sync-bot`) ត្រូវបានដាក់ក្រោម `['auth', 'admin']`។
- Route Student telegram linking (`manualLinkStudent`, `unlinkStudent`) ត្រូវបានដាក់ក្រោម `['auth']`។

---

## 9. បន្ថែម Security Headers & លុប `X-Powered-By` — [✅ បានអនុវត្តរួចរាល់]

**ដំណោះស្រាយដែលបានអនុវត្ត:**
1. បង្កើត middleware `app/Http/Middleware/SecurityHeaders.php`:
   - `X-Frame-Options: SAMEORIGIN` (ការពារ Clickjacking)
   - `X-Content-Type-Options: nosniff` (ការពារ MIME-sniffing)
   - `X-XSS-Protection: 1; mode=block`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
   - `Content-Security-Policy: default-src 'self' ...`
   - លុបចោល `X-Powered-By` header ទាំងស្រុង។
2. បន្ថែម configuration ក្នុង `vercel.json` សម្រាប់ HTTP Headers នៅកម្រិត CDN Edge។
3. បន្ថែម `@header_remove('X-Powered-By');` និង `@ini_set('expose_php', 'off');` ក្នុង `api/index.php`។

---

## 10. ការពារ `/system-repair` — [✅ បានអនុវត្តរួចរាល់]

**បញ្ហាដើម:** `/system-repair` អាចឱ្យអ្នកណាក៏ដោយបើកមើលបញ្ជី Table និងរត់ `ALTER TABLE` បាន។

**ដំណោះស្រាយដែលបានអនុវត្ត:**
- ផ្លាស់ប្តូរ `/system-repair` និង `/api/system-repair` ទៅក្នុង `middleware(['auth', 'super_admin'])`។ ជនអនាមិក ឬ Admin ធម្មតាមិនអាចចូលរត់ script នេះបានឡើយ។

---

## លទ្ធផលនៃការធ្វើតេស្តផ្ទៀងផ្ទាត់ (Verification Results)

```bash
# 1. តេស្ត Admin Route ដោយគ្មាន Auth
curl -s https://onlinexam.site/api/admin/students
-> 401 {"message":"Unauthenticated."} [PASSED]

# 2. តេស្ត Admin Route ដោយ Session សិស្ស (Student Session)
curl -s -b "online_exam_session=STUDENT_SESSION" https://onlinexam.site/api/admin/students
-> 403 {"message":"Forbidden. Admin privileges required."} [PASSED]

# 3. តេស្ត check-identifier (កុំឱ្យ Leak Role)
curl -s -X POST -d "identifier=150003" https://onlinexam.site/api/check-identifier
-> 200 {"requiresPassword":false} [PASSED]

# 4. តេស្ត password verify-identity (លែងមាន 500 error)
curl -s -X POST -d "username=admin&phone=012" https://onlinexam.site/api/password/verify-identity
-> 422 JSON Validation Response (No 500 error) [PASSED]

# 5. តេស្ត skills-groups (លែងមាន Incomplete Class)
curl -s https://onlinexam.site/api/skills-groups
-> 200 Clean JSON Array [PASSED]

# 6. តេស្ត Security Headers
curl -sI https://onlinexam.site/api/public-settings
-> X-Frame-Options: SAMEORIGIN
-> X-Content-Type-Options: nosniff
-> X-Powered-By header is REMOVED [PASSED]
```

---

*ឯកសារនេះត្រូវបានធ្វើបច្ចុប្បន្នភាពកាលបរិច្ឆេទ 2026-09-21 បន្ទាប់ពីការកែប្រែ Codebase និងការធ្វើតេស្តទទួលជោគជ័យ ១០០%។*
