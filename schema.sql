-- =====================================================================
-- Asset Management System — schema v3
--
-- Rebuilt from SYSTEM_INVENTORY.md section 5B (the code -> schema
-- dependency matrix), so every column the application actually reads or
-- writes is present. v2 is superseded and should be discarded: it was
-- written from an incomplete summary and dropped working features.


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

SET FOREIGN_KEY_CHECKS=1;

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
    asset_code VARCHAR(50) NOT NULL,

    asset_type_id BIGINT UNSIGNED NOT NULL,
    asset_sub_type_id BIGINT UNSIGNED NOT NULL,

    brand_id BIGINT UNSIGNED NULL,
    brand_name VARCHAR(100) NULL,

    model VARCHAR(100) NULL,

    -- Procurement fields (boss item 2)
    purchase_date DATE NULL,
    supplier VARCHAR(150) NULL,         
    po_reference VARCHAR(100) NULL,
    invoice_reference VARCHAR(100) NULL,
    financial_year VARCHAR(9) NULL,      -- e.g. 2026/2027
    location VARCHAR(150) NULL,
    cost DECIMAL(12,2) NOT NULL,
    useful_life_months SMALLINT UNSIGNED NULL,
    monthly_depreciation DECIMAL(12,2) NULL,
    residual_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    comments TEXT NULL,
    merged_into_asset_id BIGINT UNSIGNED NULL,
    supereseded_by_asset_id BIGINT UNSIGNED NULL,
    origin_asset_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,           
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
    CONSTRAINT fk_assets_supereseded_by FOREIGN KEY (supereseded_by_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,

    KEY idx_assets_superseded_by (superseded_by_asset_id),

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
    source_asset_id BIGINT UNSIGNED NULL,        
    cost DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    useful_life_months INT UNSIGNED NULL,    
    monthly_depreciation DECIMAL(12,2) NULL,
        -- NULL = not depreciated
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
    component_asset_id BIGINT UNSIGNED NULL,     --  that was added
    result_asset_id BIGINT UNSIGNED NULL,        -- new combined asset
    removed_asset_id BIGINT UNSIGNED NULL,       -- asset created/restored
                                                 -- for the removed part

    modification_date DATE NOT NULL,
    modification_type VARCHAR(20) NULL,          

    removed_asset VARCHAR(150) NULL,             
    added_asset VARCHAR(150) NULL,               
    removed_price DECIMAL(12,2) NULL,
    new_value DECIMAL(12,2) NULL,                -- combined cost after
    new_location VARCHAR(150) NULL,
    parent_original_cost DECIMAL(12,2) NULL,
    component_original_cost DECIMAL(12,2) NULL,
    authorized_by VARCHAR(150) NULL,
    comments TEXT NULL,
    reverted_at TIMESTAMP NULL,


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
    result_asset_id BIGINT UNSIGNED NULL,
    new_useful_life_months SMALLINT UNSIGNED NULL,               -- if a new asset was
                                                 -- created for the revalued
                                                 -- item, link it here
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_revals_asset FOREIGN KEY (asset_id)
        REFERENCES assets(id) ON DELETE RESTRICT,
    KEY idx_revals_history (asset_id, revaluation_date),

    CONSTRAINT fk_revals_result FOREIGN KEY (result_asset_id)
        REFERENCES assets(id) ON DELETE SET NULL,
    KEY idx_revals_result (result_asset_id)
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
--Data Insertions
-- ---------------------------------------------------------------------

INSERT INTO asset_types (type_name, type_code) VALUES
    ('Electronics', 'EL'),
    ('Furniture',   'FU'),
    ('Vehicles',    'VE');

INSERT INTO asset_sub_types (asset_type_id, sub_type_name, sub_type_code, useful_life_years) VALUES
    (1, 'Laptop',    'LAP', 3),
    (1, 'Desktop',   'DES', 3),
    (1, 'Printer',   'PRN', 3),
    (1, 'Monitor',   'MON', 3),
    (1, 'SSD',       'SSD', 3),
    (2, 'Chair',     'CHR', 3),
    (2, 'Desk',      'DSK', 3),
    (2, 'Cabinet',   'CAB', 3),
    (3, 'Car',       'CAR', 5),
    (3, 'Motorbike', 'MOT', 5);

INSERT INTO brands (name) VALUES
    ('Dell'), ('HP'), ('Lenovo'), ('Asus'), ('Canon'), ('Toyota'), ('Damro');


