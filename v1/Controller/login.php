<?php
require $_SERVER['DOCUMENT_ROOT'].'/CMP332 Practical/vendor/autoload.php';
require_once ('../Model/Response.php');
require_once('../Model/User.php');
require_once('db.php');

use \Firebase\JWT\JWT;

try {
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
}
catch(PDOException $exception) {
    error_log("Data Connection Error - ", 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database Connection Failed".$exception);
    $response->send();
    exit();
}

if (array_key_exists("email", $_GET) && array_key_exists("password", $_GET)){
    if ($_SERVER['REQUEST_METHOD'] === 'GET'){
    
        if ($_GET['email'] === '' || $_GET['password'] === ''){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Error: Email and Password Must have a Value Supplied");
            $response->send();
            exit();
        }

        $email = $_GET['email'];
        $password = $_GET['password'];

        // verify user
        $query = $readDB->prepare('select userId, firstName, lastName, email, phoneNumber, dateOfBirth, password from tbl_users where email = :email');
        $query->bindParam(':email', $email, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();
        $taskArray = array();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(403);
            $response->setSuccess(false);
            $response->addMessage("Error: Incorrect Email or Password");
            $response->send();
            exit();
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            try {
                $user = new User($row['userId'], $row['firstName'], $row['lastName'], $row['email'], $row['phoneNumber'], $row['dateOfBirth'], $row['password']);
                $userId = $user->getUserID();
                if (password_verify($password, $user->getPassword())){
                    date_default_timezone_set('Europe/London');

                    // Generate Token
                    $key = 'privatekey';
                    
                    $iat = time(); // time the JWT was issued (initated at time)
                    echo date("d-m-Y H:i:s", $iat);
                    $exp = $iat + 60 * 60; // expiration time, 1 hour
                    $payload = array(
                        "iss" => 'http://localhost:8080/CMP332 Practical',
                        "aud" => 'http://localhost:8080',
                        "iat" => $iat,
                        "exp" => $exp,
                        "userId" => $userId  
                    );

                    $jwt = JWT::encode($payload, $key, 'HS512');

                    
                    // save API key to DB
                    $query = $writeDB->prepare('insert into tbl_api_keys (APIKey) value (:APIKey)');

                    $query->bindParam(':APIKey', $jwt , PDO::PARAM_STR);
                
                    $query->execute();

                    $rowCount = $query->rowCount();

                    if ($rowCount === 0){
                        $response = new Response();
                        $response->setHttpStatusCode(500);
                        $response->setSuccess(false);
                        $response->addMessage("Error: Failed to Insert API Key into Database");
                        $response->send();
                        exit();
                    }
                

                    $exp = date("d-m-Y H:i:s", $exp); // convert expiry date to readable format
                    // setup data & response to return the key
                    $data = array(
                        "JWT"=>$jwt,
                        "Expiry Date/Time"=>$exp
                    );

                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->setCache(true);
                    $response->addMessage("Account Authorised");
                    $response->setData($data);
                    $response->send();
                    exit();
                
                } else {
                    $response = new Response();
                    $response->setHttpStatusCode(403);
                    $response->setSuccess(false);
                    $response->addMessage("Error: Incorrect Email or Password");
                    $response->send();
                    exit();
                }
            }catch (PDOException $exception){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("PDO Error: Failed to Insert User into Database".$exception);
                $response->send();
                exit();
            }
        }
    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Endpoint (hint: use GET to generate token)");
        $response->send();
        exit();
    }
}
else
{
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("Error: Please Provide Email and Password to Generate Token");
    $response->send();
    exit();
}
?>