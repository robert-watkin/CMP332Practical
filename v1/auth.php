<?php
require $_SERVER['DOCUMENT_ROOT'].'/CMP332 Practical/vendor/autoload.php';
require_once "Model/Response.php";
require_once "Model/User.php";
require_once('Controller/db.php');

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
            $task = new User($row['userId'], $row['firstName'], $row['lastName'], $row['email'], $row['phoneNumber'], $row['dateOfBirth'], $row['password']);
            if (password_verify($password, $task->getPassword())){
                // Generate Token
                $key = 'privatekey';
                
                $iat = time(); // time the JWT was issued (initated at time)
                $exp = $iat + 60 * 60; // expiration time, 1 hour
                $payload = array(
                    "iss" => 'http://localhost:8080/CMP332 Practical',
                    "aud" => 'http://localhost:8080',
                    "iat" => $iat,
                    "exp" => $exp    
                );

                $jwt = JWT::encode($payload, $key, 'HS512');

                $exp = date("Y-m-d H:i:s", $exp); // convert expiry date to readable format


                // TODO save API key to DB


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