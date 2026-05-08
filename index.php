<?php
session_start();

// reset data if needed
if (isset($_GET["reset"])) {
    unset($_SESSION["books"]);
    header("Location: index.php");
    exit;
}

// allowed genres
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// sample books
if (!isset($_SESSION["books"])) {
    $_SESSION["books"] = [
        [
            "id" => 1,
            "title" => "Java Programming",
            "author" => "Herbert Schildt",
            "genre" => "Technology",
            "year" => 2018,
            "pages" => 720
        ],
        [
            "id" => 2,
            "title" => "Atomic Habits",
            "author" => "James Clear",
            "genre" => "Non-Fiction",
            "year" => 2018,
            "pages" => 320
        ],
        [
            "id" => 3,
            "title" => "Steve Jobs",
            "author" => "Walter Isaacson",
            "genre" => "Biography",
            "year" => 2011,
            "pages" => 656
        ]
    ];
}

$books = &$_SESSION["books"];

$errors = [];

$submittedData = [
    "title" => "",
    "author" => "",
    "genre" => "",
    "year" => "",
    "pages" => ""
];

$editMode = false;
$editId = "";

// function to protect output
function clean($value) {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

// edit mode
if (isset($_GET["edit_id"])) {
    $editId = (int) $_GET["edit_id"];

    foreach ($books as $book) {
        if ($book["id"] == $editId) {
            $editMode = true;

            $submittedData["title"] = $book["title"];
            $submittedData["author"] = $book["author"];
            $submittedData["genre"] = $book["genre"];
            $submittedData["year"] = $book["year"];
            $submittedData["pages"] = $book["pages"];

            break;
        }
    }
}

// delete book
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"])) {
    $deleteId = (int) $_POST["delete_id"];

    $books = array_filter($books, function ($book) use ($deleteId) {
        return $book["id"] != $deleteId;
    });

    $books = array_values($books);
    $_SESSION["books"] = $books;

    $_SESSION["success"] = "Book deleted successfully.";
    header("Location: index.php");
    exit;
}

// add or update book
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["delete_id"])) {

    $submittedData["title"] = htmlspecialchars(trim($_POST["title"] ?? ""));
    $submittedData["author"] = htmlspecialchars(trim($_POST["author"] ?? ""));
    $submittedData["genre"] = htmlspecialchars(trim($_POST["genre"] ?? ""));
    $submittedData["year"] = htmlspecialchars(trim($_POST["year"] ?? ""));
    $submittedData["pages"] = htmlspecialchars(trim($_POST["pages"] ?? ""));

    if ($submittedData["title"] == "") {
        $errors["title"] = "Title is required.";
    } elseif (strlen($submittedData["title"]) < 3 || strlen($submittedData["title"]) > 120) {
        $errors["title"] = "Title must be between 3 and 120 characters.";
    }

    if ($submittedData["author"] == "") {
        $errors["author"] = "Author is required.";
    } elseif (str_word_count($submittedData["author"]) < 2) {
        $errors["author"] = "Author must contain at least two words.";
    }

    if ($submittedData["genre"] == "") {
        $errors["genre"] = "Genre is required.";
    } elseif (!in_array($submittedData["genre"], $genres)) {
        $errors["genre"] = "Invalid genre selected.";
    }

    $currentYear = (int) date("Y");

    if ($submittedData["year"] == "") {
        $errors["year"] = "Year is required.";
    } elseif (!preg_match("/^[0-9]{4}$/", $submittedData["year"])) {
        $errors["year"] = "Year must be 4 digits.";
    } elseif ((int) $submittedData["year"] < 1000 || (int) $submittedData["year"] > $currentYear) {
        $errors["year"] = "Year must be between 1000 and " . $currentYear . ".";
    }

    if ($submittedData["pages"] == "") {
        $errors["pages"] = "Pages is required.";
    } elseif (!filter_var($submittedData["pages"], FILTER_VALIDATE_INT) || (int) $submittedData["pages"] <= 0) {
        $errors["pages"] = "Pages must be a positive number.";
    }

    if (empty($errors)) {

        if (isset($_POST["edit_id"]) && $_POST["edit_id"] != "") {
            $updateId = (int) $_POST["edit_id"];

            foreach ($books as $index => $book) {
                if ($book["id"] == $updateId) {
                    $books[$index]["title"] = $submittedData["title"];
                    $books[$index]["author"] = $submittedData["author"];
                    $books[$index]["genre"] = $submittedData["genre"];
                    $books[$index]["year"] = (int) $submittedData["year"];
                    $books[$index]["pages"] = (int) $submittedData["pages"];
                    break;
                }
            }

            $_SESSION["success"] = "Book updated successfully.";
            header("Location: index.php");
            exit;
        }

        $maxId = 0;

        foreach ($books as $book) {
            if ($book["id"] > $maxId) {
                $maxId = $book["id"];
            }
        }

        $newBook = [
            "id" => $maxId + 1,
            "title" => $submittedData["title"],
            "author" => $submittedData["author"],
            "genre" => $submittedData["genre"],
            "year" => (int) $submittedData["year"],
            "pages" => (int) $submittedData["pages"]
        ];

        $books[] = $newBook;

        $_SESSION["success"] = "Book added successfully.";
        header("Location: index.php");
        exit;
    }
}

$successMessage = "";

if (isset($_SESSION["success"])) {
    $successMessage = $_SESSION["success"];
    unset($_SESSION["success"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Book Library</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container mt-5">

    <h1 class="text-center mb-4">My Book Library</h1>

    <?php if ($successMessage != ""): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo clean($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">

        <div class="col-md-4">
            <h2 class="mb-3">
                <?php echo $editMode ? "Edit Book" : "Add Book"; ?>
            </h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    Please check the form errors.
                </div>
            <?php endif; ?>

            <form method="POST">

                <?php if ($editMode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo clean($editId); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title"
                           class="form-control <?php echo isset($errors["title"]) ? "is-invalid" : ""; ?>"
                           value="<?php echo clean($submittedData["title"]); ?>">

                    <?php if (isset($errors["title"])): ?>
                        <div class="invalid-feedback">
                            <?php echo clean($errors["title"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Author</label>
                    <input type="text" name="author"
                           class="form-control <?php echo isset($errors["author"]) ? "is-invalid" : ""; ?>"
                           value="<?php echo clean($submittedData["author"]); ?>">

                    <?php if (isset($errors["author"])): ?>
                        <div class="invalid-feedback">
                            <?php echo clean($errors["author"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Genre</label>

                    <select name="genre" class="form-control <?php echo isset($errors["genre"]) ? "is-invalid" : ""; ?>">
                        <option value="">Select Genre</option>

                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo clean($genre); ?>"
                                <?php echo ($submittedData["genre"] == $genre) ? "selected" : ""; ?>>
                                <?php echo clean($genre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (isset($errors["genre"])): ?>
                        <div class="invalid-feedback">
                            <?php echo clean($errors["genre"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year"
                           class="form-control <?php echo isset($errors["year"]) ? "is-invalid" : ""; ?>"
                           value="<?php echo clean($submittedData["year"]); ?>">

                    <?php if (isset($errors["year"])): ?>
                        <div class="invalid-feedback">
                            <?php echo clean($errors["year"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pages</label>
                    <input type="number" name="pages"
                           class="form-control <?php echo isset($errors["pages"]) ? "is-invalid" : ""; ?>"
                           value="<?php echo clean($submittedData["pages"]); ?>">

                    <?php if (isset($errors["pages"])): ?>
                        <div class="invalid-feedback">
                            <?php echo clean($errors["pages"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <?php echo $editMode ? "Update Book" : "Add Book"; ?>
                </button>

                <?php if ($editMode): ?>
                    <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                <?php endif; ?>

            </form>
        </div>

        <div class="col-md-8">
            <h2 class="mb-3">Books List</h2>

            <table class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Year</th>
                        <th>Pages</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo clean($book["id"]); ?></td>
                            <td><?php echo clean($book["title"]); ?></td>
                            <td><?php echo clean($book["author"]); ?></td>
                            <td><?php echo clean($book["genre"]); ?></td>
                            <td><?php echo clean($book["year"]); ?></td>
                            <td><?php echo clean($book["pages"]); ?></td>

                            <td>
                                <a href="index.php?edit_id=<?php echo clean($book["id"]); ?>" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal<?php echo clean($book["id"]); ?>">
                                    Delete
                                </button>

                                <div class="modal fade" id="deleteModal<?php echo clean($book["id"]); ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Book</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                Are you sure?
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancel
                                                </button>

                                                <form method="POST">
                                                    <input type="hidden" name="delete_id" value="<?php echo clean($book["id"]); ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        Yes, Delete
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>