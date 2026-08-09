-- UCID: mp2446
-- Date: 07/26/2026
-- Books table for API-imported and manually created records.

CREATE TABLE IF NOT EXISTS Books (
    id INT UNSIGNED AUTO_INCREMENT,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL DEFAULT 'Unknown Author',
    publish_year SMALLINT UNSIGNED DEFAULT NULL,
    cover_id INT UNSIGNED DEFAULT NULL,

    source ENUM('api', 'manual') NOT NULL DEFAULT 'manual',
    external_key VARCHAR(300) DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_external_key (external_key),
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_publish_year (publish_year),
    INDEX idx_source (source)
);