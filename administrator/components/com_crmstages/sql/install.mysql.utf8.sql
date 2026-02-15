CREATE TABLE IF NOT EXISTS `#__crm_companies` (
                                              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                              `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `stage_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `active` TINYINT NOT NULL DEFAULT 1,
    `ordering` INT NOT NULL DEFAULT 0,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    INDEX `idx_stage_id` (`stage_id`),
    INDEX `idx_active` (`active`),
    INDEX `idx_ordering` (`ordering`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__crm_stages` (
                                               `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                               `code` VARCHAR(10) NOT NULL DEFAULT '',
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `ordering` INT NOT NULL DEFAULT 0,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    `active` TINYINT NOT NULL DEFAULT 1,

    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    INDEX `idx_ordering` (`ordering`),
    INDEX `idx_active` (`active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO `#__crm_stages` (
    `code`,
    `name`,
    `description`,
    `ordering`,
    `active`
) VALUES
      ('C0', 'Ice', 'Initial contact not yet made. No interaction history.', 1, 1),
      ('C1', 'Touched', 'There have been no conversations with the company with the LPR', 2, 1),
      ('C2', 'Aware', 'There is a conversation with LPR.', 3, 1),
      ('W1', 'Interested', 'Discovery form completed. Open to scheduling a demo.', 4, 1),
      ('W2', 'Demo_Planned', 'Demo scheduled. Date/time confirmed.', 5, 1),
      ('W3', 'Demo_Done', 'Demo conducted within last 60 days. Next: request/invoice.', 6, 1),
      ('H1', 'Committed', 'Formal agreement or account created. Moving toward payment.', 7, 1),
      ('H2', 'Customer', 'Payment received. Official customer status.', 8, 1),
      ('A1', 'Activated', 'Customer fully onboarded (e.g., ID card issued).', 9, 1),
      ('N0', 'Null', 'Invalid or abandoned lead. No activity.', 10, 1);




CREATE TABLE IF NOT EXISTS `#__crm_actions` (
                                                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                `code` VARCHAR(255) NOT NULL,
                                                `name` VARCHAR(255) NOT NULL DEFAULT '',
    `ordering` INT NOT NULL DEFAULT 0,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    `active` TINYINT NOT NULL DEFAULT 1,

    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    INDEX `idx_ordering` (`ordering`),
    INDEX `idx_name` (`name`),
    INDEX `idx_active` (`active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO `#__crm_actions` (
    `code`,
    `name`,
    `ordering`
) VALUES
      ('attempt_of_contact', 'Attempt of Contact', 1),
      ('conversation_with_lpr_comment','Conversation with LPR + Comment', 2),
      ('filling_out_discovery_form', 'Filling Out Discovery Form', 3),
      ('planning_demo', 'Planning Demo (Date/Time)', 4),
      ('demo_conducted', 'Demo Conducted (Event Link)',5),
      ('invoice_issued', 'Invoice Issued',6),
      ('payment_received', 'Payment Received',7),
      ('first_id_card_issued','First ID Card Issued', 8);


CREATE TABLE IF NOT EXISTS `#__crm_stage_actions` (
                                                      `stage_id` INT UNSIGNED NOT NULL,
                                                      `action_id` INT UNSIGNED NOT NULL,
                                                      `ordering` INT NOT NULL DEFAULT 0,
                                                      `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                      `modified` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,


                                                      PRIMARY KEY (`stage_id`, `action_id`),
    INDEX `idx_stage_id` (`stage_id`),
    INDEX `idx_action_id` (`action_id`),
    INDEX `idx_ordering` (`ordering`),

    CONSTRAINT `fk_stageactions_stage`
    FOREIGN KEY (`stage_id`)
    REFERENCES `#__crm_stages` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT `fk_stageactions_action`
    FOREIGN KEY (`action_id`)
    REFERENCES `#__crm_actions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__crm_stage_actions` (
    `stage_id`,
    `action_id`,
    `ordering`
)
SELECT
    s.id AS stage_id,
    a.id AS action_id,
    CASE
        WHEN s.code = 'C1' THEN
            CASE a.code
                WHEN 'attempt_of_contact' THEN 1
                WHEN 'conversation_with_lpr_comment' THEN 2
                ELSE 99
                END
        WHEN s.code = 'C2' THEN
            CASE a.code
                WHEN 'filling_out_discovery_form' THEN 1
                ELSE 99
                END
        WHEN s.code = 'W1' THEN
            CASE a.code
                WHEN 'planning_demo' THEN 1
                ELSE 99
                END
        WHEN s.code = 'W2' THEN
            CASE a.code
                WHEN 'demo_conducted' THEN 1
                ELSE 99
                END
        WHEN s.code = 'W3' THEN
            CASE a.code
                WHEN 'invoice_issued' THEN 1
                ELSE 99
                END
        WHEN s.code = 'H1' THEN
            CASE a.code
                WHEN 'payment_received' THEN 1
                ELSE 99
                END
        WHEN s.code = 'H2' THEN
            CASE a.code
                WHEN 'first_id_card_issued' THEN 1
                ELSE 99
                END
        ELSE 99  -- For stages like C0, N0: no valid actions
        END AS ordering
FROM
    `#__crm_stages` s
        INNER JOIN `#__crm_actions` a ON (
        -- Define allowed combinations
        (s.code = 'C1' AND a.code IN ('attempt_of_contact', 'conversation_with_lpr_comment')) OR
        (s.code = 'C2' AND a.code = 'filling_out_discovery_form') OR
        (s.code = 'W1' AND a.code = 'planning_demo') OR
        (s.code = 'W2' AND a.code = 'demo_conducted') OR
        (s.code = 'W3' AND a.code = 'invoice_issued') OR
        (s.code = 'H1' AND a.code = 'payment_received') OR
        (s.code = 'H2' AND a.code = 'first_id_card_issued')
        )
WHERE
    s.active = 1  -- Only link to active stages
        AND a.active IS NULL OR a.active = 1;  -- If you add 'active' to actions later



CREATE TABLE IF NOT EXISTS `#__crm_action_log` (
                                                   `company_id` INT UNSIGNED NOT NULL,
                                                   `stage_id` INT UNSIGNED NOT NULL,
                                                   `action_id` INT UNSIGNED NOT NULL,
                                                   `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`company_id`, `stage_id`, `action_id`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_stage_id` (`stage_id`),
    INDEX `idx_action_id` (`action_id`),
    INDEX `idx_created` (`created`),

    CONSTRAINT `fk_actionlog_company`
    FOREIGN KEY (`company_id`)
    REFERENCES `#__crm_companies` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT `fk_actionlog_stage`
    FOREIGN KEY (`stage_id`)
    REFERENCES `#__crm_stages` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT `fk_actionlog_action`
    FOREIGN KEY (`action_id`)
    REFERENCES `#__crm_actions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


INSERT INTO `#__crm_companies` (
    `name`,
    `description`,
    `stage_id`,
    `active`,
    `ordering`,
    `created`,
    `modified`
) VALUES (
             'Acme Corporation',
             'Leading provider of innovative tech solutions.',
             1,          -- stage_id: corresponds to a valid stage (e.g., 'C0 - Ice')
             1,          -- active: true
             1,          -- ordering: sort order
             NOW(),      -- created: current timestamp
             NULL        -- modified: null (will be set on update)
         );
