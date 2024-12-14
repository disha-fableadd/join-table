<?php
// header("Content-Type: application/json");

// include 'db.php';

// $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';

// if (empty($category_name)) {
//     echo json_encode(["error" => "Category name is required."]);
//     exit;
// }

// $category_check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
// $result = mysqli_query($conn, $category_check_sql);

// if (mysqli_num_rows($result) > 0) {
//     echo json_encode(["error" => "Category already exists."]);
//     exit;
// }

// $insert_sql = "INSERT INTO categories (category_name) VALUES ('$category_name')";
// if (mysqli_query($conn, $insert_sql)) {
//     echo json_encode(["success" => true, "message" => "Category added successfully."]);
// } else {
//     echo json_encode(["error" => "Failed to add category: " . mysqli_error($conn)]);
// }

// mysqli_close($conn);


header("Content-Type: application/json");

include 'db.php';

// Function to handle API actions
function handleApiAction($action, $conn) {
    switch ($action) {
        case 'addCategory':
            addCategory($conn);
            break;
        default:
            echo json_encode(["error" => "Invalid action specified"]);
            break;
    }
}

// Function to add a category
function addCategory($conn) {
    $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';

    if (empty($category_name)) {
        echo json_encode(["error" => "Category name is required."]);
        return;
    }

    // Check if category already exists
    $category_check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
    $result = mysqli_query($conn, $category_check_sql);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(["error" => "Category already exists."]);
        return;
    }

    // Insert new category into the database
    $insert_sql = "INSERT INTO categories (category_name) VALUES ('$category_name')";
    if (mysqli_query($conn, $insert_sql)) {
        echo json_encode(["success" => true, "message" => "Category added successfully."]);
    } else {
        echo json_encode(["error" => "Failed to add category: " . mysqli_error($conn)]);
    }
}

// Main API logic
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action) {
    handleApiAction($action, $conn);
} else {
    echo json_encode(["error" => "No action specified"]);
}

mysqli_close($conn);


?>
