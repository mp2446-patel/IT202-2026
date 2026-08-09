<?php
// UCID: mp2446
// Date: 07/30/2026
// User data helpers: shared queries for reading user rows.

function find_user_by_email(string $email): ?array
{
    try {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT
                id,
                username,
                email,
                password_hash,
                role
             FROM Users
             WHERE email = :email
             LIMIT 1"
        );

        $stmt->execute([
            ":email" => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    } catch (PDOException $e) {
        error_log(
            "find_user_by_email() failed: " .
            $e->getMessage()
        );

        return null;
    }
}

function find_user_by_id(int $id): ?array
{
    try {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT
                id,
                username,
                email,
                role,
                created,
                modified
             FROM Users
             WHERE id = :id
             LIMIT 1"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    } catch (PDOException $e) {
        error_log(
            "find_user_by_id() failed: " .
            $e->getMessage()
        );

        return null;
    }
}