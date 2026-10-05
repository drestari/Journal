-- AJSMR Dynamic Article Publication Module V1
-- Run this SQL against the existing `ajsmr_editorial` database.
-- This does NOT modify the existing manuscript workflow tables.

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_id VARCHAR(80) NOT NULL,
    article_type VARCHAR(80) NOT NULL DEFAULT 'Research Article',
    title TEXT NOT NULL,
    running_title VARCHAR(500) NULL,
    abstract MEDIUMTEXT NULL,
    keywords TEXT NULL,
    volume VARCHAR(30) NULL,
    issue VARCHAR(30) NULL,
    year SMALLINT UNSIGNED NULL,
    received_date DATE NULL,
    revised_date DATE NULL,
    accepted_date DATE NULL,
    published_date DATE NULL,
    page_start VARCHAR(30) NULL,
    page_end VARCHAR(30) NULL,
    doi VARCHAR(255) NULL,
    section VARCHAR(150) NULL,
    license VARCHAR(255) NULL DEFAULT 'CC BY 4.0',
    rights_statement TEXT NULL,
    pdf_file VARCHAR(500) NULL,
    supplementary_file VARCHAR(500) NULL,
    graphical_abstract VARCHAR(500) NULL,
    cover_image VARCHAR(500) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'DRAFT',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_articles_article_id (article_id),
    UNIQUE KEY uq_articles_doi (doi),
    KEY idx_articles_status (status),
    KEY idx_articles_year_issue (year, volume, issue),
    KEY idx_articles_published_date (published_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_authors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_id INT UNSIGNED NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    affiliation TEXT NULL,
    email VARCHAR(255) NULL,
    orcid VARCHAR(100) NULL,
    author_order INT UNSIGNED NOT NULL DEFAULT 1,
    is_corresponding TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_article_authors_article (article_id),
    CONSTRAINT fk_article_authors_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_references (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_id INT UNSIGNED NOT NULL,
    reference_text TEXT NOT NULL,
    reference_order INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_article_refs_article (article_id),
    CONSTRAINT fk_article_refs_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_files (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_id INT UNSIGNED NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) NULL,
    file_size BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_article_files_article (article_id),
    CONSTRAINT fk_article_files_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
