<?php
class Controller
{
    public function __construct(protected PDO $db)
    {
        
    }
    //create user
    public function add_user( string $username, string $password, int $role): int|false {

        $username = trim($username);
        $password = trim($password);

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false){
            return false;
        }

        if($hash === null){
            throw new \Exception(" Invalid hashing algorithm");
        }
        try{
            $stmt = $this->db->prepare("INSERT INTO users (username, password, role) VALUES(:username, :password, :role)");

            $stmt -> execute([
                ":username" => $username,
                ":password" => $hash,
                ":role" => $role,
            ]); 
        }catch(\PDOException $e){
            error_log($e->getMessage());
            return false;
        }
        
        $id= $this->db->lastInsertId();
        if($id === false){
            return false;
        }
        return intval($id);
    }
    
    //login check if the account exsit
    public function login_user( string $username, string $password): int|false {

        
        try{
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username)");
            $stmt->bindParam(':username', $username); 
            $stmt->execute();
        }catch(\PDOException $e){
            error_log($e->getMessage());
            return false;
        }
        if($stmt->rowCount() < 1){
            return false;
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user === false){
            return false;
        }
        if(!isset($user["password"])){
            throw new \Exception("password not found in db");
        }
        
        $verify = password_verify($password, $user["password"]);
        if($verify===true){
            return intval($user["id"]);
        }else{
            return false;
        }
    }
}

?>