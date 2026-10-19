# SYSTEM_INVENTORY.md — Asset Management (Laravel 10)

Generated 2026-10-19 by a read-only analysis. Nothing was modified, no migrations were run, and the database was not touched.
Branch `br-mihijith`, HEAD `0d19235 "Baseline before Schema v2"`.

---

## 0. Read this first — five findings that change how the rest should be read

1. **`schema.sql` is not in the project.** It is not in the working tree, not in any git branch's history (`git log --all --name-only` shows no `.sql` file ever committed), and not anywhere under `C:\Users\mihij\{Downloads,Desktop,Documents}`, `D:\Tools\wamp64\www` or `D:\Tools` (searched to depth 3–4). The only `.sql` files are two `mysqldump` backups in `storage/app/backups/` (gitignored), both from **2026-10-13**, i.e. *before* Schema v2. Consequence: I **cannot** compute "in migrations but not in schema.sql" or "in schema.sql but not in migrations", and I cannot say which code references "will throw right now" against the v2 DB. What I have done instead (sections 3 and 5): a feature → schema-object checklist you can diff against `schema.sql` mechanically, and an exhaustive code → table/column dependency matrix. If you give me the file (or paste the `CREATE TABLE`s) the real gap list is a ten-minute job.
2. **The migrations do NOT describe the whole system.** There is **no migration that creates `assets`, `asset_types`, `asset_sub_types`, `asset_modifications` or `asset_sales`**, and the only migration that creates `asset_disposals` is guarded by `if (!Schema::hasTable(...))`. Those tables were hand-built outside Laravel; the migrations only `ALTER` them. A fresh `php artisan migrate` on an empty DB would fail at `2026_10_10_062530` (`Table 'assets' doesn't exist`). The "as built" picture for those six tables is therefore **migrations + the 2026-10-13 11:23 dump** (marked `[D]` below), not migrations alone.
3. **Two migration files are missing from the working tree** (`git status` shows them as ` D`; they still exist in `HEAD` and I read them via `git show HEAD:`):
   - `database/migrations/2026_10_08_125000_create_asset_disposals_table.php`
   - `database/migrations/2026_10_10_062530_add_asset_sub_type_id_to_assets_table.php`
   So "migration files were restored from git" is true for 17 of 19 files; these two were not restored.
4. **The pre-v2 database was entirely `MyISAM` (17 of 18 tables; only `asset_disposals` was InnoDB) with zero `CONSTRAINT`s.** Every `foreignId()->constrained()` / `->foreign()->onDelete('cascade')` in the migrations was silently ignored by MySQL. There are no real foreign keys, and `DB::transaction()` in `AssetModificationController` is a no-op on MyISAM tables. If `schema.sql` made everything InnoDB with real FKs, behaviour will differ from what the code was developed against.
5. **A DB trigger exists that is in no migration**: `trg_asset_modifications_after_insert` (raw SQL, `AFTER INSERT ON asset_modifications`) which does `UPDATE assets SET cost = NEW.new_value WHERE id = NEW.asset_id` and `UPDATE assets SET location = NEW.new_location WHERE id = NEW.asset_id`. It was in the 2026-10-13 dump. The current PHP code (combine-into-new-asset flow) writes `new_value` = the *combined* total and `asset_id` = the *parent*, so this trigger overwrites the (now merged-away) parent's cost with the combined cost. Whether schema.sql kept, dropped or changed it is unknown — see §6.

Source-tag legend used below: **[M]** column/table created by a migration file in the repo · **[M\*]** created by a migration that exists in `HEAD` but is missing from the working tree · **[D]** exists only in the 2026-10-13 11:23 dump (`storage/app/backups/asset_management_20261013_112354_before_depreciation.sql`), i.e. hand-made / out-of-band.

---

## 1. SCHEMA AS BUILT BY MIGRATIONS

### 1.0 Migration order and what each does

`Schema::defaultStringLength(191)` is set in `AppServiceProvider::boot()`, so every `string()` without a length is `varchar(191)`.

| # | File | Effect | In `migrations` table at 2026-10-13 11:23? |
|---|------|--------|---|
| 1 | `2014_10_12_000000_create_users_table` | creates `users` | yes (batch 1) |
| 2 | `2014_10_12_100000_create_password_reset_tokens_table` | creates `password_reset_tokens` | yes |
| 3 | `2019_08_19_000000_create_failed_jobs_table` | creates `failed_jobs` | yes |
| 4 | `2019_12_14_000001_create_personal_access_tokens_table` | creates `personal_access_tokens` | yes |
| 5 | `2026_10_08_121902_create_permission_tables` | creates 5 Spatie tables | yes (batch 2) |
| 6 | `2026_10_08_125000_create_asset_disposals_table` **[M\*, missing from working tree]** | creates `asset_disposals` **only if absent** | yes (batch 3) |
| 7 | `2026_10_10_062530_add_asset_sub_type_id_to_assets_table` **[M\*, missing]** | `assets.asset_sub_type_id` | yes (batch 4) |
| 8 | `2026_10_12_183000_create_activity_logs_table` | creates `activity_logs` | yes |
| 9 | `2026_10_12_200000_add_authorized_by_to_asset_disposals_table` | `asset_disposals.authorized_by` | yes |
| 10 | `2026_10_12_210000_add_combination_tracking_columns` | `assets.merged_into_asset_id`; `asset_modifications.component_asset_id/result_asset_id/reverted_at` | yes |
| 11 | `2026_10_12_220000_add_original_costs_to_asset_modifications_table` | `asset_modifications.parent_original_cost/component_original_cost` | yes |
| 12 | `2026_10_13_090000_add_comments_to_modifications_sales_and_disposals` | `comments` on 3 tables | yes |
| 13 | `2026_10_13_100000_create_asset_allocations_table` | creates `asset_allocations` | yes |
| 14 | `2026_10_13_110000_add_upgrade_downgrade_columns_to_asset_modifications` | `modification_type/removed_asset_id/removed_price` | yes |
| 15 | `2026_10_13_120000_add_origin_asset_id_to_assets_table` | `assets.origin_asset_id` | yes (id 15, last row) |
| 16 | `2026_10_13_130000_create_asset_depreciation_layers_table` | `asset_sub_types.useful_life_years` + seed + creates `asset_depreciation_layers` + **data backfill** | **not in the dump** (ran later) |
| 17 | `2026_10_16_032215_add_approval_status_to_asset_disposals_table` | `asset_disposals.approval_status` | not in dump (ran later) |
| 18 | `2026_10_16_032242_add_deleted_at_to_assets_table` | `assets.deleted_at` (softDeletes) | not in dump (ran later) |
| 19 | `2026_10_16_101549_create_brands_table` | creates `brands` | not in dump (ran later) |

Raw SQL / non-DDL inside migrations:
- Migration 16 runs `DB::table('asset_sub_types')->…->update(['useful_life_years' => …])` seeding **Electronics = 5**, **Furniture = 3** (matches `asset_types.type_name`), and a private `backfill()` that inserts one `asset_depreciation_layers` row per existing asset (`start_date = substr(assets.created_at,0,10)`, `useful_life_months = sub_type years × 12`), and for combined assets (those with children via `merged_into_asset_id`) copies the children's layers scaled by `assets.cost / Σ children layer cost`.
- No migration contains `DB::statement`, triggers, views, stored procedures or enum columns. **No `enum` anywhere** — all "enums" are `varchar` + app-level validation (`allocation_type`, `modification_type`, `approval_status`, disposal `method`).
- `down()` for migration 13/16 etc. exist but nothing relevant to rebuild order.

### 1.1 Framework tables

#### `users` [M #1]
| column | type | null | notes |
|---|---|---|---|
| id | bigint unsigned AI | NO | PK |
| name | varchar(191) | NO | |
| email | varchar(191) | NO | UNIQUE `users_email_unique` |
| email_verified_at | timestamp | YES | |
| password | varchar(191) | NO | |
| remember_token | varchar(100) | YES | |
| created_at / updated_at | timestamp | YES | |

No `role` column — roles are via Spatie. Dump: 2 rows.

#### `password_reset_tokens` [M #2]
`email` varchar(191) **PK** · `token` varchar(191) NOT NULL · `created_at` timestamp NULL.

#### `failed_jobs` [M #3]
`id` bigint unsigned PK · `uuid` varchar(191) NOT NULL **UNIQUE** · `connection` text · `queue` text · `payload` longtext · `exception` longtext (all NOT NULL) · `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP.

#### `personal_access_tokens` [M #4] (Sanctum)
`id` bigint unsigned PK · `tokenable_type` varchar(191) + `tokenable_id` bigint unsigned (`morphs` → index `personal_access_tokens_tokenable_type_tokenable_id_index`) · `name` varchar(191) · `token` varchar(64) **UNIQUE** · `abilities` text NULL · `last_used_at` timestamp NULL · `expires_at` timestamp NULL · `created_at/updated_at` NULL.

### 1.2 Spatie laravel-permission tables [M #5]
`config/permission.php`: `teams = false`, default table/column names, default cache. So **no team column**.

| table | columns | keys |
|---|---|---|
| `permissions` | id bigint unsigned AI; name varchar(125) NN; guard_name varchar(125) NN; created_at, updated_at NULL | PK id; UNIQUE (name, guard_name) |
| `roles` | id bigint unsigned AI; name varchar(125) NN; guard_name varchar(125) NN; timestamps NULL | PK id; UNIQUE (name, guard_name) |
| `model_has_permissions` | permission_id bigint unsigned NN; model_type varchar(191) NN; model_id bigint unsigned NN | PK (permission_id, model_id, model_type); INDEX `model_has_permissions_model_id_model_type_index` (model_id, model_type); **FK** permission_id → permissions.id ON DELETE CASCADE |
| `model_has_roles` | role_id bigint unsigned NN; model_type varchar(191) NN; model_id bigint unsigned NN | PK (role_id, model_id, model_type); INDEX `model_has_roles_model_id_model_type_index` (model_id, model_type); **FK** role_id → roles.id ON DELETE CASCADE |
| `role_has_permissions` | permission_id bigint unsigned NN; role_id bigint unsigned NN | PK (permission_id, role_id); **FK** permission_id → permissions.id CASCADE; **FK** role_id → roles.id CASCADE |

(FKs are declared in the migration but were **not materialised** in the MyISAM dump.)

### 1.3 Application tables defined fully by migrations

#### `activity_logs` [M #8] — activity logging
| column | type | null | notes |
|---|---|---|---|
| id | bigint unsigned AI | NO | PK |
| user_id | bigint unsigned | YES | **FK → users.id ON DELETE SET NULL** (declared; not enforced under MyISAM; index `activity_logs_user_id_foreign` exists) |
| action | varchar(191) | NO | |
| description | text | YES | |
| status | tinyint | NO | DEFAULT 1, comment `1 = Success/Completed, 0 = Failed/Pending` |
| created_at / updated_at | timestamp | YES | |

#### `asset_allocations` [M #13] — asset → branch/employee allocations (history; newest row = current)
| column | type | null | notes |
|---|---|---|---|
| id | bigint unsigned AI | NO | PK |
| asset_id | int unsigned | NO | INDEX `asset_allocations_asset_id_index`; **no FK** (comment: "assets table has no foreign keys") |
| allocation_type | varchar(20) | NO | 'branch' \| 'employee' (app-level) |
| allocated_to | varchar(150) | NO | free text branch name / employee number |
| allocated_by | varchar(150) | YES | name of acting user (or authorizing admin on modification carry-over) |
| created_at / updated_at | timestamp | YES | |

#### `asset_depreciation_layers` [M #16] — depreciation layers
| column | type | null | notes |
|---|---|---|---|
| id | bigint unsigned AI | NO | PK |
| asset_id | int unsigned | NO | INDEX `asset_depreciation_layers_asset_id_index`; no FK |
| source_asset_id | int unsigned | YES | which registered asset this slice of cost came from; **no index** |
| cost | decimal(12,2) | NO | |
| start_date | date | NO | |
| useful_life_months | int unsigned | YES | NULL = not depreciated |
| created_at / updated_at | timestamp | YES | |

#### `brands` [M #19]
`id` bigint unsigned PK · `name` varchar(191) NOT NULL **UNIQUE** · `created_at/updated_at` NULL. **Not referenced by any column in `assets`** (assets keeps `brand_name` varchar) — see §5.

### 1.4 Application tables created OUTSIDE migrations and only altered by them
Base columns are from the 2026-10-13 11:23 dump `[D]`; migration additions are tagged. If a column isn't in the dump and isn't in a migration it is not listed (the dump predates migrations 16–19).

#### `asset_types` [D only — no migration touches it]
| column | type | null | notes |
|---|---|---|---|
| id | int unsigned AI | NO | PK |
| type_name | varchar(100) | NO | UNIQUE `type_name` |
| type_code | varchar(20) | NO | UNIQUE `type_code` |

Dump data: `(1,'Electronics','1'), (2,'Furniture','2')`. (Conflicts with `AssetTypeSeeder`, §4.8.) MyISAM, no timestamps.

#### `asset_sub_types` [D] + [M #16]
| column | type | null | notes |
|---|---|---|---|
| id | int unsigned AI | NO | PK [D] |
| asset_type_id | int unsigned | NO | [D] part of UNIQUE `uq_type_subtype_name` (asset_type_id, sub_type_name); no FK |
| sub_type_name | varchar(100) | NO | [D] |
| useful_life_years | smallint unsigned | YES | **[M #16]** straight-line life; NULL = not depreciated |

Dump data had a dirty row `(1,1,'Laptop\r\n')` plus a duplicate clean `(5,1,'Laptop')` (the unique index didn't catch it because of the `\r\n`). No timestamps.

#### `assets` [D] + [M\*#7] + [M #10, #15, #18]
| column | type | null | source / notes |
|---|---|---|---|
| id | int unsigned AI | NO | PK [D] |
| asset_code | varchar(50) | NO | [D] UNIQUE `asset_code`. Format `AST-YYYY-XXXXX` |
| asset_type_id | int unsigned | NO | [D] KEY `asset_type_id`; no FK |
| asset_sub_type_id | bigint unsigned | NO | **[M\* #7]** `unsignedBigInteger(...)->after('asset_type_id')`, **no default, no index, no FK** (note: int vs bigint mismatch with `asset_sub_types.id` int unsigned) |
| brand_name | varchar(100) | YES | [D] free text brand — there is **no `brand_id`** |
| model | varchar(100) | YES | [D] |
| financial_year | varchar(9) | YES | [D] e.g. `2026/2027` |
| location | varchar(150) | YES | [D] legacy free-text; superseded by `asset_allocations` but still read/written (trigger, fallback label) |
| cost | decimal(12,2) | NO | [D] |
| merged_into_asset_id | int unsigned | YES | **[M #10]** `->after('cost')->index()` (`assets_merged_into_asset_id_index`). Set on the two originals when combined; NULL = visible |
| origin_asset_id | int unsigned | YES | **[M #15]** `->after('merged_into_asset_id')->index()` (`assets_origin_asset_id_index`). For a removed component: asset it originally came with |
| created_at | timestamp | YES | [D] DEFAULT CURRENT_TIMESTAMP |
| updated_at | timestamp | YES | [D] DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |
| deleted_at | timestamp | YES | **[M #18]** `softDeletes()`, no index |

Soft deletes: **yes** (`deleted_at`, used by `Asset` model's `SoftDeletes`). No FKs at all.

#### `asset_modifications` [D] + [M #10, #11, #12, #14]
| column | type | null | source / notes |
|---|---|---|---|
| id | int unsigned AI | NO | [D] PK |
| modification_id | varchar(50) | NO | [D] UNIQUE (human id e.g. `MOD-009`) |
| asset_id | int unsigned | NO | [D] the *parent* asset; INDEX `idx_asset_modifications_history` (asset_id, modification_date) |
| component_asset_id | int unsigned | YES | **[M #10]** asset that was added; `->after('asset_id')` |
| result_asset_id | int unsigned | YES | **[M #10]** new combined asset; INDEX `asset_modifications_result_asset_id_index` |
| modification_date | date | NO | [D] |
| removed_asset | varchar(150) | YES | [D] display text of removed part |
| added_asset | varchar(150) | YES | [D] display text of added part |
| new_value | decimal(12,2) | YES | [D] combined cost after modification (**trigger source**) |
| new_location | varchar(100) | YES | [D] (**trigger source**) |
| authorized_by | varchar(150) | YES | [D] admin name |
| reverted_at | timestamp | YES | **[M #10]** set when reverted (nothing in code sets it any more) |
| parent_original_cost | decimal(12,2) | YES | **[M #11]** original cost of parent at combine time |
| component_original_cost | decimal(12,2) | YES | **[M #11]** original cost of added component at combine time |
| comments | text | YES | **[M #12]** |
| modification_type | varchar(20) | YES | **[M #14]** 'upgrade'\|'downgrade'\|'replacement' |
| removed_asset_id | int unsigned | YES | **[M #14]** registry asset created/restored for removed part |
| removed_price | decimal(12,2) | YES | **[M #14]** |

No `created_at/updated_at` (model sets `$timestamps = false`). **Trigger (raw SQL, no migration):**
```sql
CREATE TRIGGER trg_asset_modifications_after_insert AFTER INSERT ON asset_modifications FOR EACH ROW BEGIN
  IF NEW.new_value IS NOT NULL THEN UPDATE assets SET cost = NEW.new_value WHERE id = NEW.asset_id; END IF;
  IF NEW.new_location IS NOT NULL THEN UPDATE assets SET location = NEW.new_location WHERE id = NEW.asset_id; END IF;
END
```
(DEFINER `root`@`localhost`, from the dump.)

#### `asset_sales` [D] + [M #12]
| column | type | null | notes |
|---|---|---|---|
| id | int unsigned AI | NO | PK [D] |
| sales_ID | varchar(50) | NO | [D] UNIQUE `sales_ID` (note mixed-case column name) |
| asset_id | int unsigned | NO | [D] KEY `asset_id` |
| sale_date | date | YES | [D] |
| buyer_name | varchar(150) | YES | [D] never populated by the form |
| buyer_contact | varchar(100) | YES | [D] never populated |
| price | decimal(12,2) | NO | [D] |
| authorized_BY | varchar(100) | YES | [D] mixed-case column name |
| comments | text | YES | **[M #12]** |

No timestamps. No soft deletes. **No approval status** (only disposals have one).

#### `asset_disposals` [M\*#6] + [M #9, #12, #17]
Migration #6 (`id()` → bigint unsigned, `asset_id` unsignedInteger, `timestamps()`) was skipped on the real DB because the table pre-existed (hand-made, **InnoDB**, `id` int unsigned, timestamps `DEFAULT CURRENT_TIMESTAMP`). Final shape:
| column | type | null | source / notes |
|---|---|---|---|
| id | int unsigned AI (migration would give bigint unsigned) | NO | PK |
| disposal_id | varchar(50) | NO | UNIQUE `disposal_id` (e.g. `DISP-003`) |
| asset_id | int unsigned | NO | KEY `asset_id` |
| method | varchar(100) | NO | app validates `Donated\|Scrapped\|Lost` |
| disposal_date | date | YES | |
| approval_status | varchar(191) | NO | **[M #17]** DEFAULT `'approved'`, `->after('disposal_date')`. Values used: `approved`, `pending` (Lost) |
| authorized_by | varchar(150) | YES | **[M #9]** `->after('disposal_date')`; admin name |
| created_at | timestamp | YES | DEFAULT CURRENT_TIMESTAMP (dump) |
| updated_at | timestamp | YES | DEFAULT CURRENT_TIMESTAMP ON UPDATE (dump) |
| comments | text | YES | **[M #12]** |

No soft deletes here (a rejected Lost claim **hard-deletes** the row).

#### `migrations` (Laravel's own table) — exists in dump; `id` int unsigned, `migration` varchar(191), `batch` int.

### 1.5 Seed/reference data that matters to the schema (from the 2026-10-13 dump)
- `permissions`: `register assets`, `modify assets`, `sell assets`, `dispose assets` (guard `web`) — **hand-inserted** (created 2026-10-08), not by any seeder.
- `roles`: `Admin` (id 1), `Staff` (id 2). `role_has_permissions`: Admin → all four; Staff → `register assets`, `modify assets`.
- `model_has_roles`: user 1 → Admin, user 2 → Staff. `activity_logs`: 1 test row.
- Live data at that point: 12 assets (ids 13, 14, 16, 17, 19–23, 43, 44, 63), 6 modifications, 1 sale, 1 disposal, 2 allocations.

---

## 2. SCHEMA AS DEFINED BY schema.sql

**`schema.sql` is not present anywhere** (see §0.1). Nothing to summarise. Evidence of "v2 was run": only your description and the commit message `0d19235 Baseline before Schema v2`.

---

## 3. GAP ANALYSIS

Because `schema.sql` is absent, a true two-way diff is impossible. What I can give you:

### 3.1 Feature → schema-object checklist (diff `schema.sql` against this)

Every row is something the migrations add and the code relies on. If `schema.sql` lacks the object, the feature is gone or broken (the "Breaks at" column says where).

| Feature | Schema objects (table.column) | Migration | Breaks at (if missing) |
|---|---|---|---|
| **Asset allocations** (branch/employee, with history) | table `asset_allocations` (`asset_id`, `allocation_type`, `allocated_to`, `allocated_by`, timestamps) | #13 | `AssetAllocationController@store`; `Asset::allocations()/currentAllocation()/location_label`; `AssetController@index` eager-load `allocations`; `assets/index` view; print views (`location_label`); `AssetModificationController::modify` (carries allocation to new assets) — **registry page itself 500s** |
| **Activity logging** | table `activity_logs` (`user_id`, `action`, `description`, `status`, timestamps) | #8 | `AssetController@store`, `AssetDisposalController@store`, `AssetModificationController@store` (insert fails → after the business transaction already ran for modifications) |
| **Depreciation layers** | table `asset_depreciation_layers` (all cols) + `asset_sub_types.useful_life_years` | #16 | `Asset::layers()/addInitialLayer()/accumulatedDepreciation()/netBookValue()`; `AssetController@index/store`; `DepreciationController`; `AssetModificationController::modify`; sale/disposal print views |
| **Approval status on disposals** | `asset_disposals.approval_status` (+ `authorized_by`) | #17 (+#9) | `AssetDisposalController@store/approve`; `asset-disposals/index` pending query; `AssetDisposal::$fillable` |
| **Soft deletes on assets** | `assets.deleted_at` | #18 | `Asset` uses `SoftDeletes` → **every** `Asset` query appends `deleted_at IS NULL` → every page that touches assets 500s if the column is absent; `approve()` |
| **Origin / combination tracking between assets** | `assets.merged_into_asset_id`, `assets.origin_asset_id`; `asset_modifications.component_asset_id`, `result_asset_id`, `removed_asset_id`, `reverted_at`, `modification_type`, `removed_price` | #10, #14, #15 | `Asset::scopeActive/components/mergedInto/originAsset/removalModification/descendantIds`; sale/disposal/modification controllers' `whereNull('merged_into_asset_id')`; registry view badges |
| **Original cost preservation on modifications** | `asset_modifications.parent_original_cost`, `component_original_cost` (+ `removed_price`, `new_value`) | #11, #14 | `AssetModificationController::modify` insert (`AssetModification::create`) |
| Comments on modification/sale/disposal | `comments` text on `asset_modifications`, `asset_sales`, `asset_disposals` | #12 | the three store actions, index/print views |
| Authorizer name on disposals/sales/modifications | `asset_disposals.authorized_by`, `asset_sales.authorized_BY`, `asset_modifications.authorized_by` | #9 / [D] | store actions; print views |
| Brands master list | table `brands` | #19 | only `BrandController` (unrouted) — dead feature |
| Roles/permissions | 5 Spatie tables + rows for roles `Admin`,`Staff` and permissions `register assets`,`modify assets`,`sell assets`,`dispose assets` | #5 (+ manual inserts) | `permission:` route middleware on every business route; `AdminAuthorization` |
| Sanctum tokens | `personal_access_tokens` | #4 | `/api/user` only |
| DB trigger | `trg_asset_modifications_after_insert` | none (raw, [D]) | silent behaviour (cost/location overwrite), not an error |
| Standard asset master data | `asset_types(type_name,type_code)`, `asset_sub_types(asset_type_id,sub_type_name)` | none ([D]) | registry dropdowns, sale/disposal print views |

### 3.2 Things in the migrations that are not in the *pre-v2 dump*, and vice-versa (the only comparison I can make)
- Migrations 16–19 (`asset_depreciation_layers`, `asset_sub_types.useful_life_years`, `asset_disposals.approval_status`, `assets.deleted_at`, `brands`) post-date the dump, so the dump has none of them.
- Dump has, but no migration creates: tables `assets`, `asset_types`, `asset_sub_types`, `asset_modifications`, `asset_sales`; all base columns of those; the unique index `uq_type_subtype_name`; index `idx_asset_modifications_history`; the trigger; `asset_disposals` as InnoDB with `int unsigned` id and `CURRENT_TIMESTAMP` defaults (migration #6 would create bigint id / nullable timestamps with no default); the 4 `permissions` rows.
- The migrations' FKs (`activity_logs.user_id`, Spatie pivots) → absent in the dump (MyISAM).

### 3.3 "In schema.sql but NOT in the migrations"
Cannot be determined (no schema.sql). Anything v2 contains beyond §1 — e.g. new tables, `brand_id`, `branches`, `employees`, FKs, InnoDB — is by definition "not in migrations".

### 3.4 To close this gap quickly (suggested, not run)
Run against the v2 database and paste the result, or just hand me `schema.sql`:
```sql
SELECT table_name, column_name, column_type, is_nullable FROM information_schema.columns
WHERE table_schema = 'asset_management' ORDER BY table_name, ordinal_position;
SHOW TRIGGERS;
```

---

## 4. APPLICATION CODE

### 4.1 Models (`app/Models`)

**`Asset`** — `HasFactory`, `SoftDeletes`
- `$fillable`: name*, asset_code, asset_type_id, asset_sub_type_id, brand_name, model, financial_year, location, cost, merged_into_asset_id, origin_asset_id. (*`name` has no column.) No `$casts` (so `created_at` etc. default).
- `booted()`: `creating` → if `asset_code` empty, sets `'AST-' . strtoupper(Str::random(6))` (a **different** format from `generateCode()`).
- **Asset-code generation**: `static generateCode()` → `AST-{Y}-{5 random upper}` looped until unique. The uniqueness check goes through the SoftDeletes global scope, so it ignores soft-deleted assets; a code belonging to a soft-deleted asset could be generated again and then hit the DB UNIQUE index. `AssetController@store` generates its own code inline with the same format.
- Scope `active()`: `doesntHave('sale')->doesntHave('disposal')->whereNull('merged_into_asset_id')`.
- Relationships: `components()` hasMany Asset on `merged_into_asset_id`; `mergedInto()` belongsTo Asset; `assetType()`; `subType()` → AssetSubType; **`brand()` belongsTo Brand (expects `assets.brand_id` — doesn't exist)**; `layers()` hasMany AssetDepreciationLayer ordered by `start_date`; `originAsset()`; `removalModification()` hasOne AssetModification on `removed_asset_id` latestOfMany('id'); `allocations()` hasMany latest('id'); `currentAllocation()` hasOne latestOfMany; `modifications()`; `sales()`; `disposals()`; `sale()` hasOne; `disposal()` hasOne.
- Custom methods: `addInitialLayer()` (creates the first depreciation layer at today's date with sub-type life); **`accumulatedDepreciation(?Carbon)`** → `Depreciation::accumulated(layers, asOf)`; **`netBookValue(?Carbon)`** = cost − accumulated; `descendantIds()` (recursive ids of components); accessor `getLocationLabelAttribute()` (current allocation label, else `location`).

**`AssetAllocation`** — `$fillable`: asset_id, allocation_type, allocated_to, allocated_by. Const `TYPES` = branch/employee. `asset()`; accessor `label` ("Branch: X"). Timestamps on.

**`AssetDepreciationLayer`** — `$fillable`: asset_id, source_asset_id, cost, start_date, useful_life_months. `$casts`: `start_date => date`. `asset()`.

**`AssetDisposal`** — `HasFactory`. `$fillable`: disposal_id, asset_id, method, disposal_date, authorized_by, comments, approval_status. `asset()` belongsTo Asset `->withTrashed()`. Timestamps on. No casts.

**`AssetModification`** — `HasFactory`; `$timestamps = false`. `$fillable`: modification_id, asset_id, component_asset_id, result_asset_id, modification_date, removed_asset, added_asset, new_value, new_location, authorized_by, reverted_at, parent_original_cost, component_original_cost, comments, modification_type, removed_asset_id, removed_price. `$casts`: `reverted_at => datetime`. Const `TYPES` (upgrade/downgrade/replacement). `asset()`, `componentAsset()`, `resultAsset()` (no `withTrashed`, no `removedAsset()` relation).

**`AssetSale`** — `HasFactory`; `$timestamps = false`. `$fillable`: sale_id, sales_ID, asset_id, sale_date, buyer_name, buyer_contact, price, authorized_by, authorized_BY, comments. `asset()`. Mutators/accessors aliasing the odd column names: `setSaleIdAttribute` → `sales_ID`; `getSaleIdAttribute`; `setAuthorizedByAttribute` → `authorized_BY`; `getAuthorizedByAttribute`. (Note: a fillable `authorized_BY` is set directly in the controller; the `authorized_by` mutator is only used by the print view.)

**`AssetSubType`** — `HasFactory`; `$timestamps = false`. `$fillable`: asset_type_id, sub_type_name, useful_life_years. `assetType()`.

**`AssetType`** — `HasFactory`; `$timestamps = false`. `$fillable`: type_name, type_code. `assets()`, `subTypes()`.

**`Brand`** — `HasFactory`. `$fillable`: name. No relations.

**`ActivityLog`** — `HasFactory`. `$fillable`: user_id, action, description, status. `$casts`: `status => integer`. `user()` belongsTo User. **Activity logging is write-only** — see §4.7.

**`User`** — `HasApiTokens, HasFactory, Notifiable, HasRoles`. **`$fillable = ['type_code','type_name']`** (copy/paste from AssetType — introduced in commit `70af090`); `$hidden`: password, remember_token; `$casts`: email_verified_at datetime. `activityLogs()` hasMany. See §5 A-9.

**Depreciation / code generation / activity helpers outside models** — `App\Support\Depreciation` (`monthsBetween`, `accumulated`, `slice`, `startMonth`, `financialYearOf`, `label`, `financialYearOptions`, `range`; straight-line, complete months, no residual value; config `config/depreciation.php`: `financial_year_start_month`=4 (env `FINANCIAL_YEAR_START_MONTH`), `years_back`=10, `years_forward`=1). `App\Support\AdminAuthorization::findAdminByPassword($password)` — loops `User::role('Admin')` and `Hash::check`s the typed password; returns the matched admin (catches `RoleDoesNotExist` → null).

### 4.2 Controllers and public methods (validation rules verbatim)

**`AssetController`**
- `index()` → view `assets.index`. Loads `Asset::with(['assetType','components','allocations','originAsset','removalModification.asset','layers'])->active()->orderByDesc('id')`; `AssetType` ordered by `type_name`; all `AssetSubType`; `Depreciation::financialYearOptions()`.
- `store(Request)` → rules: `asset_type_id` required, exists:asset_types,id · `asset_sub_type_id` required, exists:asset_sub_types,id · `brand_name` nullable string max:100 · `model` nullable string max:100 · `financial_year` nullable string in:{financialYearOptions} · `cost` required numeric min:0. Generates `asset_code` (`AST-YYYY-XXXXX`), `Asset::create`, `addInitialLayer()`, writes `ActivityLog` ("Asset Registered"). (No `location`, no `brand_id`.)

**`AssetAllocationController`**
- `store(Request, int $asset)` → `Asset::active()->findOrFail`; rules: `allocation_type` required, `Rule::in(branch, employee)` · `allocated_to` required string max:150. Creates allocation with `allocated_by = user name`. **No activity log.**

**`AssetModificationController`**
- `index()` → `asset-modifications.index` (modifications with `asset`,`resultAsset`, newest first + `formData()`).
- `create()` → `asset-modifications.index` (**no route points here**).
- `store(Request)` → rules: `modification_id` required string max:50 unique:asset_modifications,modification_id · `asset_id` required exists:assets,id · `component_asset_id` required exists:assets,id different:asset_id · `modification_date` required date · `new_location` nullable string max:100 · `comments` nullable string max:1000 · `removed_part` nullable string max:20 · `removed_price` nullable required_with:removed_part numeric min:0 · `removed_brand` nullable string max:100 · `removed_model` nullable string max:100 · `removed_asset_sub_type_id` nullable exists:asset_sub_types,id · `removed_origin_asset_id` nullable exists:assets,id · `admin_password` required string. Then manual checks, `AdminAuthorization`, `DB::transaction(modify)`, `ActivityLog` ("Asset Modified"). Private `formData()`, `modify()` (creates a NEW combined asset from parent+component, slices depreciation layers, restores/creates the removed component, carries allocations, marks originals `merged_into_asset_id`, undoes a previous modification by reviving the original asset when the pattern matches), `joinNames()`.

**`AssetSaleController`**
- `index()` → `asset-sales.index` (`AssetSale::with('asset')` desc; assets `whereNull('merged_into_asset_id')`).
- `store(Request)` → rules: `sale_id` required string max:50 unique:asset_sales,sales_ID · `asset_id` required `Rule::exists('assets','id')->whereNull('merged_into_asset_id')` · `price` required numeric min:0 · `comments` nullable string max:1000 · `admin_password` required string. Creates sale with `sale_date = today`, `authorized_BY = admin name`. **No activity log.** Redirects with `print_url`.
- `print(AssetSale $sale)` → `asset-sales.print`.

**`AssetDisposalController`**
- `index()` → `asset-disposals.index`.
- `store(Request)` → rules: `disposal_id` required string max:50 unique:asset_disposals,disposal_id · `asset_id` required `Rule::exists('assets','id')->whereNull('merged_into_asset_id')` · `method` required string in:Donated,Scrapped,Lost · `comments` nullable string max:1000 · `admin_password` required string. `approval_status = 'pending'` if method Lost else `'approved'`. Writes `ActivityLog` ("Asset Disposed - {method}").
- `print(AssetDisposal $disposal)` → `asset-disposals.print`.
- `approve($id)` → sets `approval_status='approved'`, then **soft-deletes the asset** (`$asset->delete()`). No password prompt, no activity log, doesn't check the disposal is actually pending/Lost.
- `reject($id)` → **hard-deletes the disposal row**. No log.

**`DepreciationController`**
- `index(Request)` → `depreciation.index`. Query param `fy` (validated against `financialYearOptions`). Builds report rows from `Asset::with(['layers','sale','disposal','assetType','subType'])->whereNull('merged_into_asset_id')`.
- `updateSettings(Request)` → rules: `useful_life_years` required array · `useful_life_years.*` nullable integer min:1 max:100 · `admin_password` required string. Updates `asset_sub_types.useful_life_years` and rewrites `asset_depreciation_layers.useful_life_months` where `source_asset_id` ∈ assets of that sub type (raw `DB::table`).

**`AssetTypeController`** (**unrouted**): `index`, `store`, `update`, `destroy`. Rules use column **`name`** (`required|string|max:100|unique:asset_types,name`) — column is `type_name`.
**`AssetSubTypeController`** (**unrouted**): `index`, `store`, `update`, `destroy`. Rules: `asset_type_id` required exists:asset_types,id · `sub_type_name` required string max:100 · `useful_life_years` nullable integer min:0. `index` orders AssetType by `name` (wrong col). Delete guard uses `assets.asset_sub_type_id`.
**`BrandController`** (**unrouted**): `index`, `store`, `update`, `destroy`. Rules: `name` required string max:100 unique:brands,name (update ignores own id). Delete guard queries `assets.brand_id` (doesn't exist).

**`ProfileController`**: `edit`, `update(ProfileUpdateRequest)` (rules `name` required string max:255; `email` required string lowercase email max:255 unique ignoring self), `destroy` (`password` required current_password, bag `userDeletion`).
**Stock Breeze `Auth\*`** (re-read only the ones that matter): `AuthenticatedSessionController` `create/store(LoginRequest)/destroy`; `RegisteredUserController` `create/store` (rules `name` required string max:255; `email` required string lowercase email max:255 unique:users; `password` required confirmed Password::defaults()); `NewPasswordController` `create/store` (token required; email required email; password required confirmed Password::defaults()); `PasswordResetLinkController` `create/store` (email required email); `PasswordController@update` (current_password required current_password; password required Password::defaults() confirmed); `ConfirmablePasswordController` `show/store`; `EmailVerificationPromptController` `__invoke`; `EmailVerificationNotificationController@store`; `VerifyEmailController` `__invoke`. `LoginRequest` rules: email required string email; password required string (throttle after 5 attempts).

### 4.3 Routes (`routes/web.php`, `routes/auth.php`, `routes/api.php`) grouped by middleware

Middleware aliases in `app/Http/Kernel.php` include Spatie `role`, `permission`, `role_or_permission` (all registered).

| Middleware | Method + URI | Name | Action |
|---|---|---|---|
| *(none)* | GET `/` | — | closure → redirect to `dashboard` |
| `auth`, `verified` | GET `/dashboard` | dashboard | closure → view `dashboard` |
| `auth` | GET `/profile` | profile.edit | ProfileController@edit |
| `auth` | PATCH `/profile` | profile.update | ProfileController@update |
| `auth` | DELETE `/profile` | profile.destroy | ProfileController@destroy |
| `auth`, `permission:register assets` | GET `/assets` | assets.index | AssetController@index |
| same | POST `/assets` | assets.store | AssetController@store |
| same | POST `/assets/{asset}/allocations` | assets.allocate | AssetAllocationController@store |
| `auth`, `permission:register assets` | GET `/depreciation` | depreciation.index | DepreciationController@index |
| same + `throttle:10,1` | POST `/depreciation/settings` | depreciation.settings | DepreciationController@updateSettings |
| `auth`, `permission:modify assets` | GET `/asset-modifications` | asset-modifications.index | AssetModificationController@index |
| same + `throttle:10,1` | POST `/asset-modifications` | asset-modifications.store | AssetModificationController@store |
| `auth`, `permission:sell assets` | GET `/asset-sales` | asset-sales.index | AssetSaleController@index |
| same + `throttle:10,1` | POST `/asset-sales` | asset-sales.store | AssetSaleController@store |
| same | GET `/asset-sales/{sale}/print` | asset-sales.print | AssetSaleController@print (route-model-bound `AssetSale`) |
| `auth`, `permission:dispose assets` | GET `/asset-disposals` | asset-disposals.index | AssetDisposalController@index |
| same + `throttle:10,1` | POST `/asset-disposals` | asset-disposals.store | AssetDisposalController@store |
| same | GET `/asset-disposals/{disposal}/print` | asset-disposals.print | AssetDisposalController@print |
| same | PATCH `/asset-disposals/{id}/approve` | asset-disposals.approve | AssetDisposalController@approve |
| same | DELETE `/asset-disposals/{id}/reject` | asset-disposals.reject | AssetDisposalController@reject |
| `guest` | GET/POST `/register` | register | RegisteredUserController@create/store |
| `guest` | GET/POST `/login` | login | AuthenticatedSessionController@create/store |
| `guest` | GET/POST `/forgot-password` | password.request / password.email | PasswordResetLinkController |
| `guest` | GET `/reset-password/{token}`, POST `/reset-password` | password.reset / password.store | NewPasswordController |
| `auth` | GET `/verify-email` | verification.notice | EmailVerificationPromptController |
| `auth`, `signed`, `throttle:6,1` | GET `/verify-email/{id}/{hash}` | verification.verify | VerifyEmailController |
| `auth`, `throttle:6,1` | POST `/email/verification-notification` | verification.send | EmailVerificationNotificationController@store |
| `auth` | GET/POST `/confirm-password` | password.confirm | ConfirmablePasswordController |
| `auth` | PUT `/password` | password.update | PasswordController@update |
| `auth` | POST `/logout` | logout | AuthenticatedSessionController@destroy |
| `api` + `auth:sanctum` | GET `/api/user` | — | closure → `$request->user()` |

Not routed at all: `AssetTypeController`, `AssetSubTypeController`, `BrandController`, `AssetModificationController@create`. `routes/channels.php` / `console.php` contain only stock stubs.
No route uses `role:` middleware. No policies (`AuthServiceProvider::$policies` empty), no `Gate::define`, no `@can/@role` in Blade.

### 4.4 Blade views → rendering controller method

| View | Rendered by |
|---|---|
| `dashboard.blade.php` | closure in `routes/web.php` (`/dashboard`) |
| `assets/index.blade.php` | `AssetController@index` (+ allocation modal/JS; posts to `assets.store`, `assets.allocate`) |
| `asset-modifications/index.blade.php` | `AssetModificationController@index` and `@create` (posts `asset-modifications.store`) |
| `asset-sales/index.blade.php` | `AssetSaleController@index` |
| `asset-sales/print.blade.php` (`@extends layouts.print`) | `AssetSaleController@print` |
| `asset-disposals/index.blade.php` | `AssetDisposalController@index` (also runs `AssetDisposal::where('approval_status','pending')` **inside Blade**) |
| `asset-disposals/print.blade.php` (`@extends layouts.print`) | `AssetDisposalController@print` |
| `depreciation/index.blade.php` | `DepreciationController@index` |
| `profile/edit.blade.php` (+ `profile/partials/update-profile-information-form`, `update-password-form`, `delete-user-form`) | `ProfileController@edit` |
| `auth/login`, `register`, `forgot-password`, `reset-password`, `confirm-password`, `verify-email` | the matching `Auth\*Controller` method |
| `layouts/app.blade.php` (via `<x-app-layout>`), `layouts/guest.blade.php` (`<x-guest-layout>`), `layouts/print.blade.php` | `App\View\Components\AppLayout/GuestLayout`; print via `@extends` |
| `components/admin-password-modal.blade.php` | `<x-admin-password-modal form="…">` in modifications, sales, disposals, depreciation views (collects `admin_password`) |
| `components/*` (application-logo, auth-session-status, danger-button, dropdown, dropdown-link, input-error, input-label, modal, nav-link, primary-button, responsive-nav-link, secondary-button, text-input) | stock Breeze components |
| **Orphans (rendered by nothing):** `welcome.blade.php` (`/` redirects), `layouts/navigation.blade.php`, `layouts/sidebar.blade.php` (the live sidebar is inline in `layouts/app.blade.php`) | — |
| **Missing views referenced by code:** `brands.index`, `asset-types.index`, `asset-subtypes.index` | referenced by the three unrouted controllers; **do not exist** |

Navigation (`layouts/app.blade.php`) links all six sections to every logged-in user (no `@can` gating), so a Staff user sees Sales/Disposal links that return 403.
JS: `resources/js/app.js` initialises DataTables (`datatables.net-dt`) on `table[data-datatable]`; Alpine started; CSS is plain `resources/css/app.css` (tailwind/postcss config deleted in commit `1b58787`).

### 4.5 Spatie laravel-permission wiring

Wired: package `spatie/laravel-permission ^6.25`; migration #5; `config/permission.php` (published, `teams=false`); `User` uses `HasRoles`; middleware aliases registered; `RoleAndPermissionSeeder` (called from `DatabaseSeeder`); `AdminAuthorization` uses `User::role('Admin')`; sidebar prints `Auth::user()->roles->pluck('name')`.

**Roles referenced in code:** `Admin` (AdminAuthorization, seeder), `Staff` (seeder only).

**Permissions referenced in routes (what actually gates access):** `register assets`, `modify assets`, `sell assets`, `dispose assets`.
**Permissions created by `RoleAndPermissionSeeder`:** `view assets`, `create assets`, `edit assets`, `delete assets`, `view modifications`, `create modifications`, `view sales`, `create sales`, `view disposals`, `create disposals`.
→ **The two sets are disjoint.** Running the seeder on a clean DB gives Admin/Staff none of the four permissions the routes require, so every business route returns 403. The pre-v2 DB only worked because the four permissions were inserted by hand (see §1.5). The seeded names are used nowhere in code.
Seeder also creates users `admin@assetmgmt.com` and `staff@assetmgmt.com` with a hard-coded default password in the repo (`RoleAndPermissionSeeder.php`), but see §5 A-9 (the `User` fillable bug means `firstOrCreate` can't actually set name/password).

### 4.6 Factories, seeders, tests
- `database/factories/UserFactory.php` only. No factories for asset models even though models use `HasFactory`.
- Seeders: `DatabaseSeeder` → `RoleAndPermissionSeeder`, `AssetTypeSeeder`. `AssetTypeSeeder` inserts `IT Equipment/IT`, `Office Furniture/FURN`, `Motor Vehicles/VEH`, `Machinery & Tools/MACH`, `Electrical Appliances/ELEC` — **not** the live data (`Electronics`/`Furniture`), and migration #16's useful-life seed looks up exactly `Electronics`/`Furniture`. There is no sub-type seeder.
- Tests: stock Breeze auth/profile tests + `ExampleTest`s only. **No tests cover any asset code.** (Registration/profile tests will fail given the `User::$fillable` bug.)

### 4.7 Activity logging coverage
Writes to `activity_logs` happen in exactly three places: `AssetController@store` ("Asset Registered"), `AssetDisposalController@store` ("Asset Disposed - {method}"), `AssetModificationController@store` ("Asset Modified"). **Not logged:** sales, allocations, disposal approve/reject, depreciation-setting changes, login/logout. **Nothing reads the table**: `ActivityLogController` and `resources/views/activity-logs/index.blade.php` were deleted in commit `07767ad` ("Deleted unwanted rows"); no route exists. Only `User::activityLogs()` / `ActivityLog::user()` remain.

### 4.8 Where the key calculations/generation live (quick map)
- Depreciation maths: `App\Support\Depreciation` (+ `Asset::accumulatedDepreciation/netBookValue`, `DepreciationController::index`, print views).
- Asset code generation: `Asset::generateCode()`, `Asset::booted()` creating-hook, inline in `AssetController@store` (three places, two formats).
- Sale / modification / disposal human IDs (`SALE-001`, `MOD-001`, `DISP-001`): **typed by the user**, not generated.
- Combination logic: `AssetModificationController::modify()`.

---

## 5. BROKEN REFERENCES

### 5A. Broken regardless of what `schema.sql` contains (verified from code + migrations + pre-v2 dump)

1. **`Asset::brand()` / `BrandController::destroy()`** reference `assets.brand_id`. No such column exists in any migration or the dump (assets stores `brand_name`). `Asset::brand()` would error if called; `BrandController@destroy` throws `Unknown column 'brand_id'`.
2. **`AssetTypeController`** uses column `name` (`orderBy('name')`, `unique:asset_types,name`, `create(['name'=>…])`). The column is `type_name` (and `type_code` is NOT NULL with no default, so `store` couldn't insert anyway; `$fillable` is `type_name,type_code`, so `name` is discarded).
3. **`AssetSubTypeController@index`** `AssetType::orderBy('name')` → `Unknown column 'name'`.
4. **Missing views**: `brands.index`, `asset-types.index`, `asset-subtypes.index` don't exist. **No routes** for those three controllers, so they are unreachable today (hence latent, not currently throwing).
5. **Permission-name mismatch** (see §4.5): routes demand `register|modify|sell|dispose assets`; the seeder creates none of them. Spatie's gate hook swallows `PermissionDoesNotExist`, so every user, Admin included, gets a 403 on a freshly seeded DB.
6. **`AssetModificationController@create`** returns a view with no route; harmless.
7. **Missing activity-log UI**: nothing displays `activity_logs` (feature half-removed).
8. **Phantom fillable entries**: `AssetSale::$fillable` lists `sale_id` and `authorized_by`, which are not columns (mutators map them to `sales_ID` / `authorized_BY`; the controller writes the real column names directly). `Asset::$fillable` lists `name` (no column). Harmless but misleading.
9. **`User::$fillable = ['type_code','type_name']`** — neither is a `users` column; `name`, `email`, `password` are **not** fillable. Effects: `RegisteredUserController@store` → `User::create([...])` inserts nothing for name/email/password → SQL error (`Field 'name' doesn't have a default value`) under strict mode; `ProfileController@update` → `fill($request->validated())` silently ignores name/email (profile "saves" but changes nothing); `User::firstOrCreate(...)` in the seeder cannot set name/email/password for new users. (Existing users in the DB predate or bypass this.)
10. **Fresh-install impossibility**: migrations #7, #10, #11, #12 (partly), #14, #15, #16, #18 `ALTER`/`hasColumn()` tables (`assets`, `asset_modifications`, `asset_sales`, `asset_sub_types`) that no migration creates; #16's seeding also reads `asset_types`. `php artisan migrate` on an empty DB fails at #7 (and #6 is missing from the working tree).
11. **`DB::transaction` ineffective** on the MyISAM tables (see §0.4) — a failure part-way through `modify()` leaves partial state (e.g. layers/assets created, modification row not).

### 5B. Complete code → schema dependency matrix (check each against `schema.sql`; any miss throws `SQLSTATE[42S02]/[42S22]` at the cited place)

Table `assets` (soft-deletes global scope applies to **all** `Asset` queries → `deleted_at` is mandatory for every page below):
`id, asset_code, asset_type_id, asset_sub_type_id, brand_name, model, financial_year, location, cost, merged_into_asset_id, origin_asset_id, created_at, updated_at, deleted_at` — read/written by `AssetController`, `AssetAllocationController`, `AssetModificationController`, `AssetSaleController` (`Rule::exists … whereNull('merged_into_asset_id')`), `AssetDisposalController`, `DepreciationController` (`whereNull('merged_into_asset_id')`, `where('asset_sub_type_id')`), all Asset relationships, print views, DB trigger, migration #16 backfill. `created_at` also drives backfill `start_date` and the registry "Registered At" column. `brand_id` is referenced but must NOT be expected to exist (§5A-1).

Table `asset_types`: `id, type_name, type_code` (registry dropdown, print views, migration #16 lookup by `type_name`). (`name` referenced wrongly by unrouted controller.)

Table `asset_sub_types`: `id, asset_type_id, sub_type_name, useful_life_years` (registry dropdown, modification form, depreciation page/settings, `Asset::addInitialLayer`, `modify()`).

Table `asset_modifications`: `id, modification_id, asset_id, component_asset_id, result_asset_id, modification_date, removed_asset, added_asset, new_value, new_location, authorized_by, reverted_at, parent_original_cost, component_original_cost, comments, modification_type, removed_asset_id, removed_price`. **No `created_at/updated_at` must exist or be required** (`$timestamps=false`). Also `Asset::removalModification()` joins on `removed_asset_id`; `modify()` queries `where('removed_asset_id', …)->orderByDesc('id')`.

Table `asset_sales`: `id, sales_ID, asset_id, sale_date, buyer_name, buyer_contact, price, authorized_BY, comments`. **No timestamp columns** (`$timestamps=false`). Column names are written `sales_ID` / `authorized_BY` in the dump (MySQL column names are case-insensitive, so case alone doesn't matter). A v2 rename to e.g. `sale_id` / `authorized_by` would break the validation rule `unique:asset_sales,sales_ID` and the explicit `sales_ID` / `authorized_BY` keys in `AssetSaleController@store`; the model's mutators only partly cover that.

Table `asset_disposals`: `id, disposal_id, asset_id, method, disposal_date, authorized_by, approval_status, comments, created_at, updated_at` (timestamps **are** used by the model).

Table `asset_allocations`: `id, asset_id, allocation_type, allocated_to, allocated_by, created_at, updated_at` (`latest('id')`/`latestOfMany()` order by `id`).

Table `asset_depreciation_layers`: `id, asset_id, source_asset_id, cost, start_date, useful_life_months, created_at, updated_at`.

Table `activity_logs`: `id, user_id, action, description, status, created_at, updated_at`.

Table `brands`: `id, name, created_at, updated_at` — only the unrouted `BrandController`.

Tables `users` (`id, name, email, email_verified_at, password, remember_token, timestamps`), `password_reset_tokens`, `personal_access_tokens`, `failed_jobs`, Spatie 5 tables with roles **`Admin`**, `Staff` and the four permission names in §4.5 (`guard_name='web'`).

Objects code assumes but cannot create: **trigger** `trg_asset_modifications_after_insert` (assumed *absent or harmless* by current PHP — see §6-1).

Predicted symptoms if v2 dropped each (so you can test against the real v2 DB quickly):
- `assets.deleted_at` missing → **every** asset page 500s (registry, sales, disposals, modifications, depreciation).
- `asset_allocations` missing → registry page + any modification 500s.
- `asset_depreciation_layers` / `asset_sub_types.useful_life_years` missing → registry, depreciation, register-asset, modification, print pages 500.
- `asset_disposals.approval_status` missing → disposal index (pending query) and store 500.
- `activity_logs` missing → registering an asset / recording a disposal / modification 500 *after* the main insert has succeeded (data saved, user sees error).
- `asset_modifications` combination/original-cost/type columns missing → modification store 500.
- Spatie tables/permissions missing → login works but all business routes 403/500, `AdminAuthorization` returns null so no sale/disposal/modification/depreciation-setting can ever be authorised.

---

## 6. OPEN QUESTIONS (decide before continuing)

1. **The trigger.** Does v2 keep `trg_asset_modifications_after_insert`? With the current PHP, the parent asset is marked merged-away *and* the trigger rewrites its `cost` to the combined total (and `location`) — that corrupts the "original cost" the code carefully saved in `parent_original_cost` and would double-count if anyone reads the parent's `assets.cost` later (e.g. depreciation on layers vs `cost`). Was this trigger meant to be retired when the combine-into-new-asset flow was introduced?
2. **Where is `schema.sql`?** Not in the repo and not in git. Was it only ever pasted into MySQL/phpMyAdmin? Is `asset_management` currently the v2 DB, and are the 4 later migrations (16–19) recorded in its `migrations` table? (The `migrations` table also decides what `php artisan migrate` will try to re-run.)
3. **Which Spatie permission set is canonical**: the hand-inserted `register/modify/sell/dispose assets` (what routes use) or the seeder's `view/create/edit/delete …`? Staff in the old DB = register + modify only; Admin = all four. Nothing in code distinguishes "view" from "create".
4. **How should the two missing migration files be handled** (`create_asset_disposals_table`, `add_asset_sub_type_id_to_assets_table`)? Restore from HEAD, or consolidate into a single base migration that actually creates `assets`, `asset_types`, `asset_sub_types`, `asset_modifications`, `asset_sales`, `asset_disposals` so the system can be built from scratch?
5. **`brands`**: table + model + controller exist, but `assets` only has free-text `brand_name`, and nothing is routed or has a view. Is v2 meant to introduce `assets.brand_id`? Same question for the unrouted `AssetTypeController`/`AssetSubTypeController` (written against a `name` column that doesn't exist).
6. **`useful_life_years` for sub-types** is edited on the Depreciation page (admin password) but `AssetSubTypeController` (unrouted) also accepts it — which UI is intended?
7. **Duplicate/dirty reference data**: sub-type `'Laptop\r\n'` (id 1) vs `'Laptop'` (id 5), and asset types coded `'1'`/`'2'` vs seeder codes `IT/FURN/…`. Which is authoritative?
8. **Disposal semantics for `Lost`**: the asset is hidden from the registry immediately (any disposal row hides it via `doesntHave('disposal')`), becomes soft-deleted on approval, and a rejection hard-deletes the disposal row (no audit trail). `approve`/`reject` need no admin password, aren't logged, and don't check the row is pending or Lost. Approved-lost assets vanish from the depreciation report (soft-delete global scope) even though depreciation should stop at the disposal date, and `AssetSale::asset()` (unlike `AssetDisposal::asset()`) has no `withTrashed()`. Is that intended?
9. **Sale/disposal pickers aren't `active()`**: both `index()` and the `Rule::exists` only exclude merged-away assets, so an already-sold or already-disposed asset can be sold/disposed again (the modification flow uses `active()`).
10. **`reverted_at` / "revert" feature**: the view and model still support a "Reverted" badge and `parent/component_original_cost` is saved "so a revert restores exact prices", but no revert route/method exists any more. Is revert deleted or pending? Same for `AssetModification::resultAsset()` docs ("gone once reverted").
11. **Money columns**: `decimal(12,2)` everywhere; depreciation layers are copied with rounding drift fixes in `Depreciation::slice`. `assets.cost` and Σ(layers.cost) are meant to be equal but nothing enforces it (and the trigger can desync them).
12. **`financial_year` is `varchar(9)`** validated against a rolling list (current ± years); the old Oct-13 data contains `2024/2025`, which is inside the window now but will age out of `years_back=10`.
13. **Admin-password authorisation design**: any Staff user can enter any admin's password; there is no record of *who requested* a sale/modification/disposal (only the authorizing admin's name, stored as a string, not an id). Intended?
14. **Engine/FK policy**: v2 — InnoDB with real FKs (and `ON DELETE` rules interacting with soft deletes and `merged_into_asset_id` self-references), or MyISAM as before?
15. **Housekeeping**: `Asset Sale Voucher - SALE-001.pdf` is a tracked file in the repo root; `.env` in the working tree has real local DB credentials (it's gitignored, fine); `storage/app/backups/*.sql` are the only schema evidence and are gitignored — consider committing `schema.sql` so this gap cannot recur.
16. **Dead/duplicate UI**: `layouts/navigation.blade.php`, `layouts/sidebar.blade.php`, `welcome.blade.php` are unused; `register` route is open to any guest (a self-registered user gets no role/permissions and can only see 403s).
