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
    $py_url = trim($_ENV['py_url']);
    
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);


    if ($path !== '/' && file_exists(__DIR__ . '/../..' . $path)) {
        return false; 
    }
    
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
        session_start();

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
                if($userid){


                }else{

                }
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

    //ai page to user
    $router->get("/ai_function",function(){
        header("Location: /apps/frontend_web/AI_function.php");
        exit();
    });

    $router->post("/upload",function() use ($client,$py_url){
        $url = $py_url;

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