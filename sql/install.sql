-- =============================================================================
-- ksf_FA_RBAC installation SQL
-- =============================================================================
--
-- Creates the 4 RBAC tables and seeds the 'user' crm_categories type for
-- FA system user → crm_persons identity resolution.
--
-- The 'user' category is INSERT IGNORE so subsequent upgrade re-runs are safe.
-- =============================================================================

-- -----------------------------------------------------------
-- 1. RBAC Teams
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `0_rbac_teams` (
    `id`                VARCHAR(64)     NOT NULL,
    `display_name`      VARCHAR(255)    NOT NULL DEFAULT '',
    `team_type`         VARCHAR(32)     NOT NULL DEFAULT 'explicit'
                        COMMENT 'individual | explicit | org_direct | org_indirect | service_account',
    `owner_id`          VARCHAR(64)     NULL DEFAULT NULL
                        COMMENT 'FK to 0_users.id or synthetic ID for service accounts',
    `auto_managed`      TINYINT(1)      NOT NULL DEFAULT '0'
                        COMMENT '1 = managed by system (individual teams, org chart auto-teams)',
    `requires_approval` TINYINT(1)      NOT NULL DEFAULT '0',
    `inactive`          TINYINT(1)      NOT NULL DEFAULT '0',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `team_type` (`team_type`, `inactive`),
    KEY `owner_id` (`owner_id`, `inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 2. RBAC Team Memberships
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `0_rbac_team_members` (
    `id`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `team_id`      VARCHAR(64)         NOT NULL
                   COMMENT 'FK to 0_rbac_teams.id',
    `user_id`      VARCHAR(64)         NOT NULL
                   COMMENT 'FK to 0_users.id',
    `role`         VARCHAR(32)         NOT NULL DEFAULT 'member'
                   COMMENT 'member | owner',
    `approved`     TINYINT(1)          NOT NULL DEFAULT '1',
    `approved_by`  VARCHAR(64)         NULL DEFAULT NULL,
    `approved_at`  DATETIME            NULL DEFAULT NULL,
    `added_by`     VARCHAR(64)         NULL DEFAULT NULL,
    `added_at`     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `removed_by`   VARCHAR(64)         NULL DEFAULT NULL,
    `removed_at`   DATETIME            NULL DEFAULT NULL,
    `inactive`     TINYINT(1)          NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `team_user_inactive` (`team_id`, `user_id`, `inactive`),
    KEY `user_id` (`user_id`, `inactive`),
    KEY `approved` (`team_id`, `approved`, `inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 3. RBAC Record Access xref
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `0_rbac_record_access` (
    `id`          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `module`      VARCHAR(64)         NOT NULL,
    `record_type` VARCHAR(64)         NOT NULL,
    `record_id`   INT(11) UNSIGNED    NOT NULL,
    `team_id`     VARCHAR(64)         NOT NULL
                  COMMENT 'FK to 0_rbac_teams.id',
    `projection`  VARCHAR(32)         NOT NULL DEFAULT 'public',
    `can_view`    TINYINT(1)          NOT NULL DEFAULT '0',
    `can_edit`    TINYINT(1)          NOT NULL DEFAULT '0',
    `can_delete`  TINYINT(1)          NOT NULL DEFAULT '0',
    `can_export`  TINYINT(1)          NOT NULL DEFAULT '0',
    `can_print`   TINYINT(1)          NOT NULL DEFAULT '0',
    `can_invite`  TINYINT(1)          NOT NULL DEFAULT '0',
    `can_restore` TINYINT(1)          NOT NULL DEFAULT '0',
    `granted_by`  VARCHAR(64)         NULL DEFAULT NULL,
    `granted_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`  DATETIME            NULL DEFAULT NULL,
    `inactive`    TINYINT(1)          NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `record_lookup` (`module`, `record_type`, `record_id`, `inactive`),
    KEY `team_lookup` (`team_id`, `inactive`),
    KEY `expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 4. RBAC Audit Log (append-only)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `0_rbac_audit_log` (
    `id`          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `action`      VARCHAR(64)         NOT NULL
                  COMMENT 'grant | revoke | elevate | role_assign | role_revoke | provision',
    `actor_id`    VARCHAR(64)         NOT NULL,
    `target_id`   VARCHAR(64)         NULL DEFAULT NULL,
    `module`      VARCHAR(64)         NULL DEFAULT NULL,
    `record_type` VARCHAR(64)         NULL DEFAULT NULL,
    `record_id`   INT(11) UNSIGNED    NULL DEFAULT NULL,
    `details`     TEXT                NULL DEFAULT NULL
                  COMMENT 'JSON payload with before/after or context',
    `ip_address`  VARCHAR(45)         NULL DEFAULT NULL,
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `actor` (`actor_id`, `created_at`),
    KEY `target` (`target_id`, `created_at`),
    KEY `record` (`module`, `record_type`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 5. Seed 'user' crm_category for FA person registry
-- -----------------------------------------------------------
INSERT IGNORE INTO `0_crm_categories`
    (`type`, `action`, `name`, `description`, `system`, `inactive`)
VALUES
    ('user', 'general', 'System User',
     'FA system user account linked via 0_crm_contacts.entity_id = 0_users.id',
     1, 0);
