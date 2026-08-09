-- UCID: mp2446
-- Date: 08/04/2026
-- Creates the many-to-many relationship between users and books.
-- Repeated user/book pairs are not valid, so a unique constraint prevents them.

CREATE TABLE IF NOT EXISTS UserBooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_userbooks_user
        FOREIGN KEY (user_id)
        REFERENCES Users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_userbooks_book
        FOREIGN KEY (book_id)
        REFERENCES Books(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_userbooks_user_book
        UNIQUE (user_id, book_id)
);