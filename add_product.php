<?php
// header("Content-Type: application/json");

// include 'db.php';

// $name = isset($_POST['name']) ? $_POST['name'] : '';
// $price = isset($_POST['price']) ? $_POST['price'] : 0;
// $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';

// if (empty($name) || $price <= 0 || empty($category_name)) {
//     echo json_encode(["error" => "Invalid input data. All fields are required."]);
//     exit;
// }

// $category_check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
// $result = mysqli_query($conn, $category_check_sql);

// if (mysqli_num_rows($result) == 0) {
//     echo json_encode(["error" => "Category not found: $category_name"]);
//     exit;
// }

// $row = mysqli_fetch_assoc($result);
// $category_id = $row['category_id'];

// $product_insert_sql = "INSERT INTO products (name, price, category_id) VALUES ('$name', $price, $category_id)";
// if (mysqli_query($conn, $product_insert_sql)) {
//     $product_id = mysqli_insert_id($conn);
//     echo json_encode(["success" => true, "message" => "Product added successfully", "product_id" => $product_id]);
// } else {
//     echo json_encode(["error" => "Failed to add product: " . mysqli_error($conn)]);
// }

// mysqli_close($conn);




header("Content-Type: application/json");

include 'db.php';

// Function to handle API actions
function handleApiAction($action, $conn)
{
    switch ($action) {
        case 'addProduct':
            addProduct($conn);
            break;
        default:
            echo json_encode(["error" => "Invalid action specified"]);
            break;
    }
}

// Function to add a product
function addProduct($conn)
{
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $price = isset($_POST['price']) ? $_POST['price'] : 0;
    $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';

    if (empty($name) || $price <= 0 || empty($category_name)) {
        echo json_encode(["error" => "Invalid input data. All fields are required."]);
        return;
    }

    // Check if category exists
    $category_check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
    $result = mysqli_query($conn, $category_check_sql);

    if (mysqli_num_rows($result) == 0) {
        echo json_encode(["error" => "Category not found: $category_name"]);
        return;
    }

    $row = mysqli_fetch_assoc($result);
    $category_id = $row['category_id'];

    // Insert product into the database
    $product_insert_sql = "INSERT INTO products (name, price, category_id) VALUES ('$name', $price, $category_id)";
    if (mysqli_query($conn, $product_insert_sql)) {
        $product_id = mysqli_insert_id($conn);
        echo json_encode(["success" => true, "message" => "Product added successfully", "product_id" => $product_id]);
    } else {
        echo json_encode(["error" => "Failed to add product: " . mysqli_error($conn)]);
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