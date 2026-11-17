<?php
class Controller
{
    public function __construct(protected PDO $db)
    {
        
    }
    //select user
    public function selectuser( string $username): bool {
        try{
            $stmt = $this->db->prepare("SELECT * FROM user WHERE username = :username");
            $stmt->bindParam(':username', $username); 
            $stmt->execute();
        }catch(\PDOException $e){
            error_log($e->getMessage());
            return false;
        }
        if($stmt->rowCount() > 0){
            return false;
        }else{
            return true;
        }
    }

    //create user
    public function add_user( string $username, string $password, string $role, string $email): int|false {

        $username = trim($username);
        $password = trim($password);
        $role = "user";
        $email = trim($email);

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false){
            return false;
        }

        if($hash === null){
            throw new \Exception(" Invalid hashing algorithm");
        }
        try{
            $stmt = $this->db->prepare("INSERT INTO user (username, password, role, email) VALUES(:username, :password, :role, :email)");

            $stmt -> execute([
                ":username" => $username,
                ":password" => $hash,
                ":role" => $role,
                ":email" => $email,
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
            $stmt = $this->db->prepare("SELECT * FROM user WHERE username = :username");
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
    //
    public function get_user_id(int $userid): int|false{
        try{
            $stmt = $this->db->prepare("SELECT * FROM user WHERE id = :id");
            $stmt->bindParam(':id', $userid); 
            $stmt->execute();
        }catch(\PDOException $e){
            error_log($e->getMessage());
            return false;
        }
        $role = $stmt->fetchColumn();
        if($role === false){
            return false;
        }
        return $role;
    }
    //get userid by email
    public function getUserIdByEmail( string $email): int|false{
        try{
            $stmt = $this->db->prepare("SELECT id FROM user WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $id = $stmt->fetchColumn();
            return $id !== false ? (int) $id : false;
        }catch (\PDOException $e) { 
            error_log($e->getMessage());
            return false;
        }
    }
    //updata pass by email
    public function updatePasswordByEmail(string $email, string $hashedPassword): int|false{
        try{
            $stmt = $this->db->prepare("UPDATE user SET password = :password WHERE email = :email");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':email'    => $email
            ]);
            return true;
        } catch (\PDOException  $e) { 
            error_log($e->getMessage());
            return false;
        }
    }
    //???
    public function getUserById(int $userId): array|false{
        try{
            $stmt = $this->db->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($user) ? $user : false;
        } catch (\PDOException  $e) { 
            error_log($e->getMessage());
            return false;
        }
    }
    //???
    /*
    public function updateUserProfile(string $newName, string $newEmail, int $userId): int|false{
        try{
            $stmt = $this->db->prepare("UPDATE users SET username = :name, email = :email WHERE id = :id");
            $stmt->execute([
                ':name'  => $newName,
                ':email' => $newEmail,
                ':id'    => $userId,
            ]);
            $stmt->fetch(PDO::FETCH_ASSOC);
            return true;
        }catch (\PDOException  $e) { 
            error_log($e->getMessage());
            return false;
        }
    }
    */
    
    //???
    public function add_admin(string $username, string $password, string $email): int|false
    {
        $username = trim($username);
        $password = trim($password);
        $role     = "admin"; 
        $email    = trim($email);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false || $hash === null) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("INSERT INTO user (username, password, role, email) VALUES (:username, :password, :role, :email)");
            $stmt->execute([
                ":username" => $username,
                ":password" => $hash,
                ":role"     => $role,
                ":email"    => $email,
            ]); 

            $id = $this->db->lastInsertId();
            return $id !== false ? intval($id) : false;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    //???
    public function get_all_users(): array|false 
    {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, role FROM user ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function delete_user(int $userId): bool 
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM user WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function search_users(string $keyword): array|false 
    {
        try {
            if (empty($keyword)) {
                $stmt = $this->db->prepare("SELECT id AS _id, username, email, role FROM user");
                $stmt->execute();
            } else if (is_numeric($keyword)) {
                $stmt = $this->db->prepare("SELECT id AS _id, username, email, role FROM user WHERE id = :id");
                $stmt->execute([':id' => (int) $keyword]);
            } else {
                $stmt = $this->db->prepare("SELECT id AS _id, username, email, role FROM user WHERE username LIKE :kw OR email LIKE :kw");
                $search = "%{$keyword}%";
                $stmt->execute([':kw' => $search]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
?>