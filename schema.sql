-- =====================================================================
-- Asset Management System — schema v3
--
-- Rebuilt from SYSTEM_INVENTORY.md section 5B (the code -> schema
-- dependency matrix), so every column the application actually reads or
-- writes is present. v2 is superseded and should be discarded: it was
-- written from an incomplete summary and dropped working features.
--
-- ---------------------------------------------------------------------
-- RUN ORDER — this matters
--   1. In phpMyAdmin:   DROP DATABASE IF EXISTS asset_management;
--                       CREATE DATABASE asset_management
--                         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. In the terminal: php artisan migrate
--      (creates users, password_reset_tokens, failed_jobs,
--       personal_access_tokens and the 5 Spatie tables)
--   3. Run THIS FILE.
--   4. php artisan db:seed
--
-- This file deliberately does NOT drop the database or touch Laravel's
-- own tables. It owns the 12 application tables and nothing else, so it
-- can be re-run without destroying your login. activity_logs has a real
-- FK to users, which is why migrate must run first.
--
-- Keep these 5 migration files, delete every other one:
--   2014_10_12_000000_create_users_table.php
--   2014_10_12_100000_create_password_reset_tokens_table.php
--   2019_08_19_000000_create_failed_jobs_table.php
--   2019_12_14_000001_create_personal_access_tokens_table.php
--   2026_10_08_121902_create_permission_tables.php
-- (i.e. rm database/migrations/2026_10_1*.php — that glob spares
--  create_permission_tables, which is dated 10_08.)
--
-- ---------------------------------------------------------------------
-- THE FOUR DECISIONS, AS TAKEN
--   1. Trigger trg_asset_modifications_after_insert: DROPPED. The
--      combine-into-new-asset flow supersedes it, and it corrupted the
--      parent_original_cost the code saves on purpose.
--   2. Engine: InnoDB throughout, with real foreign keys. Everything was
--      MyISAM before, so every FK was silently ignored and
--      DB::transaction() in AssetModificationController was a no-op.
--      ON DELETE RESTRICT on event rows (history must not vanish),
--      SET NULL on nullable/self references. Assets are soft-deleted,
--      not deleted, so RESTRICT should never fire in normal use.
--   3. sales_ID -> sale_id and authorized_BY -> authorized_by. Three
--      code sites change; two model mutators become unnecessary.
--   4. assets.brand_id added (FK to brands), brand_name KEPT as a
--      nullable free-text fallback so existing code and data still work
--      while the Brand master is wired up.
--
-- Also standardised: every PK and FK is BIGINT UNSIGNED. The old mix of
-- int and bigint was harmless under MyISAM but real FKs require the
-- types to match exactly.
--
-- ---------------------------------------------------------------------
-- CODE CHANGES THIS SCHEMA REQUIRES — see the notes at the bottom.
-- =====================================================================

USE asset_management;

SET FOREIGN_KEY_CHECKS = 0;

DROP TRIGGER IF EXISTS trg_asset_modifications_after_insert;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS asset_revaluations;
DROP TABLE IF EXISTS asset_depreciation_layers;
DROP TABLE IF EXISTS asset_allocations;
DROP TABLE IF EXISTS asset_disposals;
DROP TABLE IF EXISTS asset_sales;
DROP TABLE IF EXISTS asset_modifications;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS asset_sub_types;
DROP TABLE IF EXISTS asset_types;
DROP TABLE IF EXISTS brands;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- MASTER FILES  (boss item 1)
-- ---------------------------------------------------------------------

CREATE TABLE asset_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    type_code VARCHAR(10) NOT NULL,              -- the "EL" in EL-LAP-001
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asset_types_name (type_name),
    UNIQUE KEY uq_asset_types_code (type_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE asset_sub_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_type_id BIGINT UNSIGNED NOT NULL,
    sub_type_name VARCHAR(100) NOT NULL,
    sub_type_code VARCHAR(10) NOT NULL,          -- the "LAP" in EL-LAP-001
    -- Default useful life for assets of this sub type. NULL = not
    -- depreciated. Edited on the Depreciation page.
    useful_life_years SMALLINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_types_type FOREIGN KEY (asset_type_id)
        REFERENCES asset_types(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_type_subtype_name (asset_type_id, sub_type_name),
    UNIQUE KEY uq_type_subtype_code (asset_type_id, sub_type_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_brands_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ASSETS
-- ---------------------------------------------------------------------

CREATE TABLE assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- NEW FORMAT (boss item 4): <type_code>-<sub_type_code>-<nnn>,
    -- e.g. EL-LAP-001. Counter runs per (asset_type_id, asset_sub_type_id).
    -- Old format was AST-YYYY-XXXXX. See code note C1.
    asset_code VARCHAR(50) NOT NULL,

    asset_type_id BIGINT UNSIGNED NOT NULL,
    asset_sub_type_id BIGINT UNSIGNED NOT NULL,

    -- Decision 4: brand_id is the master-file link; brand_name stays as a
    -- free-text fallback for assets whose brand isn't in the master yet.
    brand_id BIGINT UNSIGNED NULL,
    brand_name VARCHAR(100) NULL,

    model VARCHAR(100) NULL,

    -- Procurement fields (boss item 2)
    purchase_date DATE NULL,
    supplier VARCHAR(150) NULL,          -- plain text; promote to a
                                         -- suppliers master later if asked
    po_reference VARCHAR(100) NULL,
    invoice_reference VARCHAR(100) NULL,
    financial_year VARCHAR(9) NULL,      -- e.g. 2026/2027

    -- Legacy free-text location. Superseded by asset_allocations but
    -- still read as a fallback by Asset::getLocationLabelAttribute().
    location VARCHAR(150) NULL,

    cost DECIMAL(12,2) NOT NULL,

    -- Depreciation inputs (boss item 2). The real schedule lives in
    -- asset_depreciation_layers; these two are per-asset overrides.
    --   useful_life_months NULL -> fall back to the sub type's
    --     useful_life_years * 12 (current behaviour).
    --   residual_value is the "Depreciation Value" at end of life.
    --     NOTE: App\Support\Depreciation currently assumes zero residual
    --     and ignores this column. See code note C4.
    useful_life_months SMALLINT UNSIGNED NULL,
    residual_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    comments TEXT NULL,

    -- Combination tracking: set on the two originals when they are merged
    -- into a new combined asset. NULL = visible in the registry.
    merged_into_asset_id BIGINT UNSIGNED NULL,
    -- For a component removed during a modification: the asset it came
    -- out of originally.
    origin_asset_id BIGINT UNSIGNED NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,           -- SoftDeletes. Mandatory: the
                                         -- Asset model's global scope adds
                                         -- `deleted_at IS NULL` to EVERY query.

    UNIQUE KEY uq_assets_code (asset_code),
    CONSTRAINT fk_assets_type FOREIGN KEY (asset_type_id)
        REFERENCES asset_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_assets_sub_type FOREIGN KEY (asset_sub_type_id)
        REFERENCES asset_sub_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_assets_brand FOREIGN KEY (brand_id)
        REFERENCES brands(id) ON DELETE SET NULL,
    CONSTRAINT fk_assets_merged_into FOREIGN KEY (merged_into_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,
    CONSTRAINT fk_assets_origin FOREIGN KEY (origin_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,

    -- supports the per type+subtype counter that builds asset_code
    KEY idx_assets_code_seq (asset_type_id, asset_sub_type_id),
    KEY idx_assets_merged_into (merged_into_asset_id),
    KEY idx_assets_origin (origin_asset_id),
    KEY idx_assets_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ALLOCATIONS — asset -> branch/employee. Append-only; newest row wins.
-- ---------------------------------------------------------------------

CREATE TABLE asset_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT UNSIGNED NOT NULL,
    allocation_type VARCHAR(20) NOT NULL,        -- 'branch' | 'employee'
    allocated_to VARCHAR(150) NOT NULL,          -- branch name / employee no.
    allocated_by VARCHAR(150) NULL,              -- acting user's name
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_allocations_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    KEY idx_allocations_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DEPRECIATION LAYERS  (boss item 8)
-- A "layer" is a slice of cost with its own start date and life. A plain
-- asset has one; a combined asset has one per contributing source asset.
-- Straight line, complete months, computed on read.
-- ---------------------------------------------------------------------

CREATE TABLE asset_depreciation_layers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT UNSIGNED NOT NULL,
    source_asset_id BIGINT UNSIGNED NULL,        -- which registered asset
                                                 -- this slice came from
    cost DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    -- The layer stops accruing depreciation on this date. Set when the asset
    -- is revalued, so the superseded layer and the new one do not double
    -- count. NULL = runs for its full useful life.
    end_date DATE NULL,
    useful_life_months INT UNSIGNED NULL,        -- NULL = not depreciated
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_layers_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    CONSTRAINT fk_layers_source FOREIGN KEY (source_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,
    KEY idx_layers_asset (asset_id),
    KEY idx_layers_source (source_asset_id)      -- was missing before
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- MODIFICATIONS
-- id is now the real key (boss item 3); display it as MOD-0001.
-- The old hand-typed modification_id varchar is gone. See code note C2.
-- ---------------------------------------------------------------------

CREATE TABLE asset_modifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    asset_id BIGINT UNSIGNED NOT NULL,           -- the parent asset
    component_asset_id BIGINT UNSIGNED NULL,     -- asset that was added
    result_asset_id BIGINT UNSIGNED NULL,        -- new combined asset
    removed_asset_id BIGINT UNSIGNED NULL,       -- asset created/restored
                                                 -- for the removed part

    modification_date DATE NOT NULL,

    -- boss item 6: two radio buttons. 'replacement' is kept in the column
    -- width but should be dropped from the form's options. See code note C3.
    modification_type VARCHAR(20) NULL,          -- 'upgrade' | 'downgrade'

    removed_asset VARCHAR(150) NULL,             -- display text
    added_asset VARCHAR(150) NULL,               -- display text
    removed_price DECIMAL(12,2) NULL,

    new_value DECIMAL(12,2) NULL,                -- combined cost after
    new_location VARCHAR(150) NULL,

    -- Preserved so a revert can restore exact prices.
    parent_original_cost DECIMAL(12,2) NULL,
    component_original_cost DECIMAL(12,2) NULL,

    authorized_by VARCHAR(150) NULL,
    comments TEXT NULL,
    reverted_at TIMESTAMP NULL,

    -- No created_at/updated_at: AssetModification has $timestamps = false.

    CONSTRAINT fk_mods_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mods_component FOREIGN KEY (component_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,
    CONSTRAINT fk_mods_result FOREIGN KEY (result_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,
    CONSTRAINT fk_mods_removed FOREIGN KEY (removed_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,

    KEY idx_mods_history (asset_id, modification_date),
    KEY idx_mods_result (result_asset_id),
    KEY idx_mods_removed (removed_asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REVALUATIONS  (boss item 5) — NEW TABLE
-- Restates an asset's value on a date. The controller should also write a
-- depreciation layer so the new value depreciates from revaluation_date
-- over the remaining life. See code note C5.
-- ---------------------------------------------------------------------

CREATE TABLE asset_revaluations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT UNSIGNED NOT NULL,
    revaluation_date DATE NOT NULL,
    previous_value DECIMAL(12,2) NULL,           -- book value just before
    revalued_amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) NULL,
    authorized_by VARCHAR(150) NULL,
    comments TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_revals_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    KEY idx_revals_history (asset_id, revaluation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SALES
-- Decision 3: sales_ID -> (dropped, use id); authorized_BY -> authorized_by.
-- ---------------------------------------------------------------------

CREATE TABLE asset_sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   -- display as SAL-0001
    asset_id BIGINT UNSIGNED NOT NULL,
    sale_date DATE NULL,
    buyer_name VARCHAR(150) NULL,
    buyer_contact VARCHAR(100) NULL,
    payment_method VARCHAR(50) NULL,
    price DECIMAL(12,2) NOT NULL,
    authorized_by VARCHAR(150) NULL,
    comments TEXT NULL,

    -- No timestamps: AssetSale has $timestamps = false.

    CONSTRAINT fk_sales_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    KEY idx_sales_asset (asset_id),
    KEY idx_sales_date (sale_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DISPOSALS
-- ---------------------------------------------------------------------

CREATE TABLE asset_disposals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   -- display as DIS-0001
    asset_id BIGINT UNSIGNED NOT NULL,
    method VARCHAR(100) NOT NULL,                -- Donated | Scrapped | Lost
    disposal_date DATE NULL,
    -- 'approved' normally; 'pending' for Lost until an admin approves.
    approval_status VARCHAR(20) NOT NULL DEFAULT 'approved',
    authorized_by VARCHAR(150) NULL,
    comments TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_disposals_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    KEY idx_disposals_asset (asset_id),
    KEY idx_disposals_status (approval_status),
    KEY idx_disposals_date (disposal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ACTIVITY LOG  (boss item 7 — the reporting/audit side)
-- Requires `users` to exist, hence the run order at the top.
-- ---------------------------------------------------------------------

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(191) NOT NULL,
    description TEXT NULL,
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Success/Completed, 0 = Failed/Pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_activity_logs_user (user_id),
    KEY idx_activity_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- STARTER MASTER DATA
-- Clean replacements for the old dirty rows ('Laptop\r\n', type codes
-- '1' and '2'). Edit freely — these only exist so the registry form has
-- something in its dropdowns.
-- ---------------------------------------------------------------------

INSERT INTO asset_types (type_name, type_code) VALUES
    ('Electronics', 'EL'),
    ('Furniture',   'FU'),
    ('Vehicles',    'VE');

INSERT INTO asset_sub_types (asset_type_id, sub_type_name, sub_type_code, useful_life_years) VALUES
    (1, 'Laptop',    'LAP', 5),
    (1, 'Desktop',   'DES', 5),
    (1, 'Printer',   'PRN', 5),
    (1, 'Monitor',   'MON', 5),
    (2, 'Chair',     'CHR', 3),
    (2, 'Desk',      'DSK', 3),
    (2, 'Cabinet',   'CAB', 3),
    (3, 'Car',       'CAR', 8),
    (3, 'Motorbike', 'MOT', 8);

INSERT INTO brands (name) VALUES
    ('Dell'), ('HP'), ('Lenovo'), ('Asus'), ('Canon'), ('Toyota');

-- =====================================================================
-- CODE CHANGES THIS SCHEMA REQUIRES
--
-- BLOCKING — a fresh database is unusable without these two:
--
--   B1. app/Models/User.php
--       $fillable is ['type_code','type_name'] (pasted from AssetType).
--       Change to ['name','email','password'] or registration inserts
--       nothing and fails.
--
--   B2. database/seeders/RoleAndPermissionSeeder.php
--       Creates 10 permissions ('view assets', 'create assets', ...) that
--       no route uses. The routes require exactly these four:
--           register assets, modify assets, sell assets, dispose assets
--       Give Admin all four; Staff 'register assets' and 'modify assets'.
--       Without this every business route 403s, Admin included.
--
-- SCHEMA-DRIVEN — needed because of the four decisions:
--
--   C1. Asset code format -> EL-LAP-001.
--       Three places generate codes today, in two different formats:
--         Asset::generateCode(), Asset::booted() creating hook,
--         and inline in AssetController@store.
--       Collapse to ONE method. It must load the type + sub type codes,
--       count existing assets for that pair (including soft-deleted ones,
--       via withTrashed(), or the counter will reuse a code), add one,
--       pad to 3 digits, and run inside a transaction.
--
--   C2. Hand-typed IDs removed (modification_id, sales_ID, disposal_id).
--       Drop them from $fillable on AssetModification / AssetSale /
--       AssetDisposal, drop the three `unique:` validation rules and the
--       form fields, and format the auto-increment id for display
--       (MOD-0001 / SAL-0001 / DIS-0001) in the index and print views.
--
--   C3. AssetModification::TYPES -> ['upgrade','downgrade'] and the form
--       becomes two radio buttons. Drop 'replacement'.
--
--   C4. residual_value and assets.useful_life_months are new inputs.
--       App\Support\Depreciation assumes zero residual and reads the life
--       from the sub type. Until it is updated, both columns are stored
--       but ignored. Asset::addInitialLayer() should prefer the asset's
--       own useful_life_months when set.
--
--   C5. Revaluations need a controller, route, form and model. On save:
--       record previous_value (the current netBookValue) and write a
--       depreciation layer starting at revaluation_date for the remaining
--       life, so the new value depreciates forward.
--
--   C6. AssetSale model: the setSaleIdAttribute / getSaleIdAttribute /
--       setAuthorizedByAttribute / getAuthorizedByAttribute mutators
--       existed only to paper over sales_ID / authorized_BY. Delete them
--       and use the real column names.
--
--   C7. Asset::brand() now works (brand_id exists). Add brand_id to
--       $fillable and a brand dropdown to the registry form.
--
--   C8. AssetTypeController / AssetSubTypeController / BrandController
--       query a `name` column on asset_types that does not exist (it is
--       type_name), have no routes and no views. They need all three
--       before the master-file screens work. asset_types.type_code and
--       asset_sub_types.sub_type_code are NOT NULL, so their forms must
--       collect them.
--
--   C9. Asset::$fillable lists 'name', which is not a column. Remove.
--
--  C10. The trigger is gone, so nothing updates assets.location from a
--       modification any more. AssetModificationController::modify()
--       should write new_location onto the asset itself, or rely on
--       asset_allocations (which it already carries over).
-- =====================================================================
