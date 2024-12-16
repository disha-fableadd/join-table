<?php

$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

include 'db.php';
header("Content-Type: application/json");

$validApiKey = "12gwdjhgduyf4543fcdctc765";

function validateAuthorization($authHeader, $validApiKey)
{
    if (empty($authHeader)) {
        echo json_encode(["error" => "Authorization header is missing."]);
        http_response_code(401);
        exit;
    }

    if ($authHeader !== $validApiKey) {
        echo json_encode(["error" => "Invalid API key."]);
        http_response_code(401);
        exit;
    }
}

validateAuthorization($authHeader, $validApiKey);

header("Content-Type: application/json");


function handleApiAction($action, $conn)
{
    switch ($action) {
        case 'createOrder':
            createOrder($conn);
            break;
        case 'addCategory':
            addCategory($conn);
            break;
        case 'addProduct':
            addProduct($conn);
            break;
        case 'showCategory':
            showCategory($conn);
            break;
        case 'displayOrder':
            displayOrder($conn);
            break;
        case 'showProduct':
            showProduct($conn);
            break;
       
        default:
            echo json_encode(["error" => "Invalid action specified"]);
            break;
    }
}
function handleDeleteApiAction($action, $conn)
{
    switch ($action) {
        case 'deleteCategory':
            deleteCategory($conn);
            break;
        case 'deleteProduct':
            deleteProduct($conn);
            break;
        case 'deleteOrder':
            deleteOrder($conn);
            break;
        default:
            echo json_encode(["error" => "Invalid action specified"]);
            break;
    }
}


// Function to create an order
function createOrder($conn)
{
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0; // For updating orders
    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';
    $product_names = isset($_POST['product_names']) ? explode(',', $_POST['product_names']) : [];
    $quantities = isset($_POST['quantities']) ? explode(',', $_POST['quantities']) : [];

    if (empty($customer_name) || empty($product_names) || empty($quantities) || count($product_names) !== count($quantities)) {
        echo json_encode(["status" => "fail", "message" => "Invalid input: Customer name, product names, and quantities must be provided and match."]);
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        if ($order_id > 0) {
            // Update existing order
            $sql_update_order = "UPDATE orders SET customer_name = '$customer_name' WHERE order_id = $order_id";
            if (!mysqli_query($conn, $sql_update_order)) {
                throw new Exception("Order update failed: " . mysqli_error($conn));
            }

            // Remove existing order items for this order
            $sql_delete_items = "DELETE FROM order_items WHERE order_id = $order_id";
            if (!mysqli_query($conn, $sql_delete_items)) {
                throw new Exception("Failed to remove existing order items: " . mysqli_error($conn));
            }
        } else {
            // Insert new order
            $sql_order = "INSERT INTO orders (customer_name) VALUES ('$customer_name')";
            if (!mysqli_query($conn, $sql_order)) {
                throw new Exception("Order insertion failed: " . mysqli_error($conn));
            }
            $order_id = mysqli_insert_id($conn);
        }

        // Insert or update order items
        for ($i = 0; $i < count($product_names); $i++) {
            $product_name = $product_names[$i];
            $quantity = $quantities[$i];

            // Fetch the product ID
            $sql_product = "SELECT product_id FROM products WHERE name = '$product_name'";
            $result = mysqli_query($conn, $sql_product);

            if (mysqli_num_rows($result) == 0) {
                throw new Exception("Product not found: $product_name");
            }

            $product = mysqli_fetch_assoc($result);
            $product_id = $product['product_id'];

            // Insert the order item
            $sql_item = "INSERT INTO order_items (order_id, product_id, quantity) VALUES ('$order_id', '$product_id', '$quantity')";
            if (!mysqli_query($conn, $sql_item)) {
                throw new Exception("Order item insertion failed for product: $product_name - " . mysqli_error($conn));
            }
        }

        mysqli_commit($conn);
        echo json_encode(["status" => "success", "message" => "Order created successfully", "order_id" => $order_id]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "fail", "message" => $e->getMessage()]);
    }

    mysqli_close($conn);
}

// Function to add a category
function addCategory($conn)
{
    $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    if (empty($category_name)) {
        echo json_encode(["status" => "fail", "message" => "Category name is required."]);
        return;
    }

    if ($category_id > 0) {
        // Update the category name
        $update_sql = "UPDATE categories SET category_name = '$category_name' WHERE category_id = $category_id";
        if (mysqli_query($conn, $update_sql)) {
            echo json_encode(["status" => "success", "message" => "Category updated successfully."]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to update category: " . mysqli_error($conn)]);
        }
    } else {
        // Check if category already exists
        $category_check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
        $result = mysqli_query($conn, $category_check_sql);

        if (mysqli_num_rows($result) > 0) {
            echo json_encode(["status" => "fail", "message" => "Category already exists."]);
            return;
        }

        // Insert new category
        $insert_sql = "INSERT INTO categories (category_name) VALUES ('$category_name')";
        if (mysqli_query($conn, $insert_sql)) {
            echo json_encode(["status" => "success", "message" => "Category added successfully."]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to add category: " . mysqli_error($conn)]);
        }
    }
}

// Function to add a product
function addProduct($conn)
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $price = isset($_POST['price']) ? $_POST['price'] : 0;
    $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';
    $colors = isset($_POST['colors']) ? $_POST['colors'] : []; 
    $image = isset($_FILES['image']) ? $_FILES['image'] : null;

    if (empty($name) || $price <= 0 || empty($category_name)) {
        echo json_encode(["status" => "fail", "message" => "Invalid input data. All fields are required."]);
        return;
    }

    if (!is_array($colors) || empty($colors)) {
        echo json_encode(["status" => "fail", "message" => "At least one color must be selected."]);
        return;
    }

    $category_check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
    $result = mysqli_query($conn, $category_check_sql);

    if (mysqli_num_rows($result) == 0) {
        echo json_encode(["status" => "fail", "message" => "Category not found: $category_name"]);
        return;
    }

    $row = mysqli_fetch_assoc($result);
    $category_id = $row['category_id'];

    $colors_str = implode(',', $colors);

    $image_path = null;
    if ($image && $image['error'] === UPLOAD_ERR_OK) {
        $image_name = pathinfo($image['name'], PATHINFO_FILENAME);
        $image_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $unique_suffix = time() . '_' . rand(1000, 9999);
        $new_image_name = $image_name . '_' . $unique_suffix . '.' . $image_extension;

        $target_dir = 'upload/';
        $target_file = $target_dir . $new_image_name;

        if (!is_writable($target_dir)) {
            echo json_encode(["status" => "fail", "message" => "Upload folder is not writable."]);
            return;
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($image['type'], $allowed_types)) {
            echo json_encode(["status" => "fail", "message" => "Invalid image format. Allowed types: jpg, png, gif."]);
            return;
        }

        if (move_uploaded_file($image['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to upload image."]);
            return;
        }
    }

    if ($product_id > 0) {
        $update_sql = "UPDATE products SET name = '$name', price = $price, category_id = $category_id, colors = '$colors_str'";
        if ($image_path) {
            $update_sql .= ", image = '$image_path'";
        }
        $update_sql .= " WHERE product_id = $product_id";

        if (mysqli_query($conn, $update_sql)) {
            echo json_encode(["status" => "success", "message" => "Product updated successfully."]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to update product: " . mysqli_error($conn)]);
        }
    } else {
        $product_insert_sql = "INSERT INTO products (name, price, category_id, colors, image) VALUES ('$name', $price, $category_id, '$colors_str', '$image_path')";
        if (mysqli_query($conn, $product_insert_sql)) {
            $product_id = mysqli_insert_id($conn);
            echo json_encode(["status" => "success", "message" => "Product added successfully.", "product_id" => $product_id]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to add product: " . mysqli_error($conn)]);
        }
    }
}




// Function to show categories
function showCategory($conn)
{
    $sql = "SELECT * FROM categories";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        echo json_encode(["status" => "fail", "message" => "Query failed: " . mysqli_error($conn)]);
    }
}
// Function to display order details by `order_item_id`
function displayOrder($conn)
{
    $order_item_id = isset($_GET['order_item_id']) ? intval($_GET['order_item_id']) : 0;

    if ($order_item_id <= 0) {
        echo json_encode([
            "status" => "fail",
            "message" => "Invalid order item ID."
        ]);
        return;
    }

    $sql = "SELECT o.order_id, o.customer_name, o.order_date, 
                   p.name AS product_name, oi.quantity, p.price, 
                   (oi.quantity * p.price) AS total_price 
            FROM orders o
            INNER JOIN order_items oi ON o.order_id = oi.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_item_id = $order_item_id";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "message" => "Order details fetched successfully.",
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "Query failed: " . mysqli_error($conn)
        ]);
    }
}

// Function to display products and their categories
function showProduct($conn)
{
    $sql = "SELECT p.product_id, p.name AS product_name, p.price,p.image,p.color,c.category_name 
            FROM products p 
            INNER JOIN categories c ON p.category_id = c.category_id";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode([
            "status" => "success",
            "message" => "Product details fetched successfully.",
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "Query failed: " . mysqli_error($conn)
        ]);
    }
}

function deleteCategory($conn)
{
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    if ($category_id <= 0) {
        echo json_encode(["status" => "fail", "message" => "Invalid category ID."]);
        return;
    }

    $sql = "DELETE FROM categories WHERE category_id = $category_id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Category deleted successfully."]);
    } else {
        echo json_encode(["status" => "fail", "message" => "Failed to delete category: " . mysqli_error($conn)]);
    }
}


// DELETE: Function to delete a product
function deleteProduct($conn)
{
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

    if ($product_id <= 0) {
        echo json_encode(["status" => "fail", "message" => "Invalid product ID."]);
        return;
    }

    $sql = "DELETE FROM products WHERE product_id = $product_id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Product deleted successfully."]);
    } else {
        echo json_encode(["status" => "fail", "message" => "Failed to delete product: " . mysqli_error($conn)]);
    }
}


// DELETE: Function to delete an order
function deleteOrder($conn)
{
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if ($order_id <= 0) {
        echo json_encode(["status" => "fail", "message" => "Invalid order ID."]);
        return;
    }

    mysqli_begin_transaction($conn);

    try {
        // Delete order items
        $sql_items = "DELETE FROM order_items WHERE order_id = $order_id";
        if (!mysqli_query($conn, $sql_items)) {
            throw new Exception("Failed to delete order items: " . mysqli_error($conn));
        }

        // Delete the order
        $sql_order = "DELETE FROM orders WHERE order_id = $order_id";
        if (!mysqli_query($conn, $sql_order)) {
            throw new Exception("Failed to delete order: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        echo json_encode(["status" => "success", "message" => "Order deleted successfully."]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "fail", "message" => $e->getMessage()]);
    }
}




$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action) {
    if ($method === 'DELETE') {
        handleDeleteApiAction($action, $conn);
    } else {
        handleApiAction($action, $conn);
    }
} else {
    echo json_encode(["error" => "No action specified"]);
}
?>