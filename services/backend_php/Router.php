<?php
    declare(strict_types=1);

    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/db_controller.php';

    use Phroute\Phroute\RouteCollector;
    use Phroute\Phroute\Dispatcher;
    use GuzzleHttp\Client;

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    //collect the variable form .env file
    $host = trim($_ENV['DB_host']);
    $db_use = trim($_ENV['DB_database']);
    $db_user = trim($_ENV['DB_username']);
    $db_password = trim($_ENV['DB_password']);
    $db_port = trim($_ENV['DB_port']);
    $js_url = trim($_ENV['js_url']);
    
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);


    if ($path !== '/' && file_exists(__DIR__ . '/../..' . $path)) {
        return false; 
    }

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
    ini_set("display_errors", 0);
    
    //route
    $router = new RouteCollector;

    //collect data or sent data to other backend
    $client = new Client();

    //connect db
    $db = new PDO(
        dsn: "mysql:host=$host;
                    port=$db_port;
                    dbname=$db_use;
                    charset=UTF8",
        username:$db_user,
        password:$db_password,
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $controller = new Controller($db);

    //login page to user
    $router->any("/",function(){

        header("Location: /apps/frontend_web/login.php");
        exit();
    });

    //login
    $router->post("/login-endpoint",function() use($controller){
        if(isset($_POST["username"]) && isset($_POST["password"])){
            if(!empty($_POST["username"])&&!empty($_POST["password"])){

                $username = trim($_POST['username']);
                $password =  trim($_POST['password']);

                $userid = $controller->login_user($username,$password);
                if($userid === false){
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
                    exit();
                }
                $userrole = $controller->get_user_id($userid);
                session_start();
                $_SESSION["logged_in_user"] = $userid;
                $_SESSION["role"] = $userrole;
                header("Location: /apps/frontend_web/mainpage.php");
                exit();
            }else{
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "the username or password is empty."]);
                exit();
            }
        }else{
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Please use your username and password to login."]);
            exit();
        }
    });

    //create account page to user
    $router->get("/creatAcc",function(){
        header("Location: /apps/frontend_web/createAC.php");
        exit();
    });

    //create acc
     $router->post("/creatAcc/create",function()use($controller){
        $username        = trim($_POST['username']);
        $password        = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirmPassword']);
        $email           = trim($_POST['email']);
        $role = "";

        if (empty($username) || empty($password) || empty($confirmPassword) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }

        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username cannot be empty']);
            exit();
        }

        $passwordTest = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*\_]).{8,}$/';
        if (!preg_match($passwordTest, $password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character']);
            exit();
        }

        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit();
        }

        $check = $controller->selectuser($username);
        if($check){
            $addId = $controller->add_user($username, $password, $role,$email);
            session_start();
            $_SESSION["logged_in_user"] = $addId;
            $_SESSION['role']   = 'user';
            header("Location: /apps/frontend_web/mainpage.php");
            exit();

        }




        

        exit();
    });

    
    //forget pass
    $router->get("/forgetPassword", function(){
        header("Location: /apps/frontend_web/fpd.php"); 
        exit();
    });
    $router->post("/forgetPassword", function()use($controller){
        $input = json_decode(file_get_contents('php://input'), true);
        $email = strtolower(trim($input['email'] ?? ''));
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        $userid =  $controller->getUserIdByEmail($email);
        if (!$userid) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No account found with this email']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'message' => 'Password reset request accepted. (Simulated verification link/code sent!)'
        ]);
    });

    //reset pass
    $router->get("/resetPassword/{email}?", function($email = null){
        if (!$email && isset($_GET['email'])) {
            $email = $_GET['email'];
        }
        $redirectUrl = "/apps/frontend_web/rspd.php";
        if (!empty($email)) {
            $redirectUrl .= "?email=" . urlencode((string)$email);
        }

        header("Location: " . $redirectUrl);
        exit();
    });


    $router->post("/resetPassword", function()use($controller){
        $input = json_decode(file_get_contents('php://input'), true);
        $email = strtolower(trim($input['email'] ?? ''));
        $newPassword = trim($input['newPassword']??'');

        if (empty($email) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and new password are required']);
        exit;
        }
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\_])[A-Za-z\d!@#$%^&*\_]{8,}$/';
        if (!preg_match($passwordRegex, $newPassword)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'New password must be at least 8 characters long and contain uppercase, lowercase, numbers, and special characters (!@#$%^&*_)'
            ]);
            exit;
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $controller->updatePasswordByEmail($email, $hashedPassword);
        if (!$result) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
        }
    });


    //???
    $router->get("/usd", function () use ($controller) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentUser = null;
        $userId = $_SESSION["logged_in_user"] ?? null;
        if ($userId !== null) {
            $currentUser = $controller->getUserById((int) $userId) ?: null;
        }
        require __DIR__ . '/../../apps/frontend_web/usd.php';
    });
    
    $router->post("/usd", function()use($controller){
        session_start();
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION["logged_in_user"] ?? null; 
        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'loggedIn' => false, 'message' => 'Not logged in']);
            exit;
        }
        $user = $controller->getUserById($userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'loggedIn' => false, 'message' => 'User not found']);
            exit;
        }
        echo json_encode([
            'success'  => true,
            'loggedIn' => true,
            'user'     => [
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role']
            ]
        ]);
    });
    //???
    /*$router->post("/cusd", function () use ($controller) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['logged_in_user'] ?? null;
        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }
        $input    = json_decode(file_get_contents('php://input'), true) ?: [];
        $newName  = trim($input['username'] ?? '');
        $newEmail = trim($input['email'] ?? '');
        if ($newName === '' || $newEmail === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and email cannot be empty']);
            return;
        }
        $result = $controller->updateUserProfile((int) $userId, $newName, $newEmail);
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        } else {
            echo json_encode(['success' => true, 'message' => 'User information updated successfully']);
        }
    });*/

    //lougout
    $router->get("/logout", function () {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header("Location: /");
        exit();
    });


    //admin 
    $router->get("/addAdmin", function()  {
        require __DIR__ . '/../../apps/frontend_web/addAdmin.php';
    });
    $router->post("/addAdmin", function () use ($controller){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentRole = $_SESSION["role"] ?? null;
        if ($currentRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied. Admin access required.']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $email    = trim($input['email'] ?? '');
        if (empty($username) || empty($password) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username, password, and email are required.']);
            exit();
        }
        $passwordRegex = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*\_]).{8,}$/';
        if (!preg_match($passwordRegex, $password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Password must be at least 8 characters long and contain uppercase, lowercase, numbers, and special characters.'
            ]);
            exit();
        }
        $isAvailable = $controller->selectuser($username);
        if (!$isAvailable) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }
        $newAdminId = $controller->add_admin($username, $password, $email);
        if ($newAdminId === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create admin account due to a server error.']);
            exit();
        }
        echo json_encode([
            'success' => true,
            'message' => 'Admin user created successfully!',
            'adminId' => $newAdminId
        ]);
        exit();
    });


    //uselist
    $router->get("/uselist", function() use ($controller) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (($_SESSION["role"] ?? '') !== 'admin') {
            header("Location: /");
            exit();
        }
        $users = $controller->get_all_users() ?: [];
        require __DIR__ . '/../../apps/frontend_web/uselist.php';
    });


    //delete user
    $router->post("/deleteUser", function() use ($controller) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentRole = $_SESSION["role"] ?? null;
        $currentUserId = $_SESSION["logged_in_user"] ?? null;
        if ($currentRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied. Admin access required.']);
            exit();
        }
        $targetUserId = intval($input['id'] ?? $input['userId'] ?? $_GET['id'] ?? 0);
        if ($targetUserId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing user ID.']);
            exit();
        }
        if ((int) $currentUserId === $targetUserId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own admin account.']);
            exit();
        }
        $deleted = $controller->delete_user($targetUserId);
        if (!$deleted) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found or delete failed.']);
            exit();
        }

        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully!'
        ]);
        exit();
    });


    //serch
    $router->post("/searchUser", function() use ($controller) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

       
        $currentRole = $_SESSION["role"] ?? null;
        if ($currentRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied. Admin access required.']);
            exit();
        }

        
        $input   = json_decode(file_get_contents('php://input'), true);
        $keyword = trim($input['keyword'] ?? '');

        
        $users = $controller->search_users($keyword);

        if ($users === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error occurred during search.']);
            exit();
        }

       
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count'   => count($users),
            'users'   => $users
        ]);
        exit();
    });

    //ai page to user
    $router->get("/ai_function",function(){
        header("Location: /apps/frontend_web/AI_function.php");
        exit();
    });

    $router->post("/upload",function() use ($client,$js_url){
        $url = $js_url;
        try{

        
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "No file uploaded or upload error occurred."]);
                    exit();
                }

                $file = $_FILES['image'];
                $file_tmp_name = $file['tmp_name'];
                $fileName = $file['name'];
                $fileSize = $file['size'];
                $fileType = $file['type'];
                $fileExt = explode(".", $fileName);
                $fileActualExt = strtolower(end($fileExt));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($fileActualExt, $allowed_extensions)) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Invalid file type."]);
                }

                $response = $client->request('POST', $url, [
                    // send form files
                    'multipart' => [
                        [
                            'name'     => 'image', 
                            'contents' => fopen($file_tmp_name, 'r'),
                            'filename' => $fileName
                        ]
                    ],
                    'timeout' => 10 
                ]);
                
                $result_body = $response->getBody()->getContents();
                header('Content-Type: application/json');
                return $result_body;
            }else {
                
                header("Location: AI_function.html");
                exit();
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            http_response_code(502);
            return json_encode([
                'status' => 'error',
                'message' => 'backend connect fail'
            ], JSON_UNESCAPED_UNICODE);
        }
    });



    try {
        $dispatcher = new Dispatcher($router->getData());
        echo $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $path);
    } catch (\Phroute\Phroute\Exception\HttpRouteNotFoundException $e) {
        http_response_code(404);
        echo "404 Page Not Found";
    } catch (\Phroute\Phroute\Exception\HttpMethodNotAllowedException $e) {
        http_response_code(405);
        echo "405 Method Not Allowed";
    }
?>