<?php
// UCID: mp2446
// Date: 07/18/2026
// Project API wrapper for the Open Library Search API.

require_once(__DIR__ . "/api_helper.php");

function get_open_library_books(string $searchTerm, bool $useCached = false): array
{
    $searchTerm = trim($searchTerm);

    if ($searchTerm === "") {
        return [
            "success" => false,
            "message" => "Please enter a book search term.",
            "books" => []
        ];
    }

    $errors = [];

    if ($useCached) {
        $result = api_sample_response("project-api-sample.json");
    } else {
        $result = api_get("https://openlibrary.org/search.json", [
            "q" => $searchTerm,
            "limit" => 10
        ]);
    }

    $data = decode_api_response($result, "docs", $errors);

    if ($data === null) {
        return [
            "success" => false,
            "message" => $errors[0] ?? "The book service returned invalid data.",
            "books" => []
        ];
    }

    $books = [];

    foreach ($data["docs"] as $book) {
        $books[] = [
            "title" => $book["title"] ?? "Unknown Title",
            "author" => isset($book["author_name"])
                ? implode(", ", $book["author_name"])
                : "Unknown Author",
            "publish_year" => $book["first_publish_year"] ?? "Unknown",
            "cover_id" => $book["cover_i"] ?? null
        ];
    }

    return [
        "success" => true,
        "message" => "Books loaded successfully.",
        "books" => $books
    ];
}