CREATE TABLE IF NOT EXISTS ew_technical_checks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    manuscript_id INT UNSIGNED NOT NULL,
    checked_by INT UNSIGNED NOT NULL,
    result ENUM('pending','passed','minor_corrections','failed') NOT NULL DEFAULT 'pending',
    comments TEXT NULL,
    checked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ew_tc_manuscript (manuscript_id),
    KEY idx_ew_tc_user (checked_by),
    CONSTRAINT fk_ew_tc_ms FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ew_tc_user FOREIGN KEY (checked_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_editor_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    manuscript_id INT UNSIGNED NOT NULL,
    editor_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NOT NULL,
    status ENUM('active','ended') NOT NULL DEFAULT 'active',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_ew_ea_ms (manuscript_id),
    KEY idx_ew_ea_editor (editor_id),
    CONSTRAINT fk_ew_ea_ms FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ew_ea_editor FOREIGN KEY (editor_id) REFERENCES users(id),
    CONSTRAINT fk_ew_ea_by FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_reviewer_pool (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    full_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    affiliation VARCHAR(255) NULL,
    expertise TEXT NULL,
    orcid VARCHAR(100) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ew_reviewer_email (email),
    KEY idx_ew_reviewer_user (user_id),
    CONSTRAINT fk_ew_reviewer_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_reviewer_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    manuscript_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NOT NULL,
    status ENUM('invited','accepted','declined','in_review','completed','cancelled') NOT NULL DEFAULT 'invited',
    invited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    due_at DATETIME NULL,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ew_ra_pair (manuscript_id, reviewer_id),
    KEY idx_ew_ra_reviewer (reviewer_id),
    CONSTRAINT fk_ew_ra_ms FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ew_ra_reviewer FOREIGN KEY (reviewer_id) REFERENCES ew_reviewer_pool(id),
    CONSTRAINT fk_ew_ra_by FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_peer_reviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    recommendation ENUM('accept','minor_revision','major_revision','reject') NULL,
    comments_to_editor TEXT NULL,
    comments_to_author TEXT NULL,
    confidential_comments TEXT NULL,
    submitted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ew_review_assignment (assignment_id),
    CONSTRAINT fk_ew_review_assignment FOREIGN KEY (assignment_id) REFERENCES ew_reviewer_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_editorial_decisions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    manuscript_id INT UNSIGNED NOT NULL,
    decided_by INT UNSIGNED NOT NULL,
    decision ENUM('minor_revision','major_revision','accept','reject') NOT NULL,
    letter TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ew_decision_ms (manuscript_id),
    CONSTRAINT fk_ew_decision_ms FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ew_decision_user FOREIGN KEY (decided_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_revisions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    manuscript_id INT UNSIGNED NOT NULL,
    version_no INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    response_text LONGTEXT NULL,
    status ENUM('requested','received','under_review','accepted','rejected') NOT NULL DEFAULT 'requested',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ew_revision_version (manuscript_id, version_no),
    CONSTRAINT fk_ew_revision_ms FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ew_revision_user FOREIGN KEY (requested_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ew_audit_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    manuscript_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ew_audit_ms (manuscript_id),
    CONSTRAINT fk_ew_audit_ms FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE SET NULL,
    CONSTRAINT fk_ew_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

