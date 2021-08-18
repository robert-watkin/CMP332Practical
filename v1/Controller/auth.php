<?php
require $_SERVER['DOCUMENT_ROOT'].'/CMP332 Practical/vendor/autoload.php';
require_once('../Model/Response.php');
require_once('../Model/User.php');
require_once('db.php');

use \Firebase\JWT\JWT;

// setup db connection
if (!isset($writeDB) || !isset($readDB)){
    try {
        $writeDB = DB::connectWriteDB();
        $readDB = DB::connectReadDB();
    }
    catch(PDOException $exception) {
        error_log("Data Connection Error - ", 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Error: Database Connection Failed");
        $response->send();
        exit();
    }
}


// retrieving headers
$headers = apache_request_headers();

// check key is input in authorisation

// check authorization header exists
if (!empty($headers) && isset($headers['Authorization'])) {
    // extract token from header
    $bearer = $headers['Authorization'];
    $split = explode(" ", $bearer);
    $APIKey = $split[1];

    // check for token in the database
    try{
        $query = $readDB->prepare("select * from tbl_api_keys where APIKey=:APIKey");
        $query->bindParam(':APIKey', $APIKey, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("Error: Unauthorised Access - API Key Not Valid or Has Expired");
            $response->send();
            exit();
        }

    } catch (PDOException $exception){
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("PDO Error: API Key Retrieval Failed");
        $response->send();
        exit();
    }

    // decode token
    $secretkey = 'privatekey';
    $values = JWT::decode($APIKey, $secretkey, ['HS512']);
    
    $initialised = $values->iat;
    $expiry = $values->exp;
    $authorisedUserId = $values->userId;

    date_default_timezone_set('Europe/London');
    $now = new DateTime();
    if ($initialised > $now->getTimestamp() || $now->getTimestamp() > $expiry){
        // Token expired
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("Error: Unauthorised Access - API Key Not Valid or Has Expired");
        $response->send();
        exit();
    }
}
else
{
    // TODO error response
    $response = new Response();
    $response->setHttpStatusCode(401);
    $response->setSuccess(false);
    $response->addMessage("Error: Unauthorised Access - API Key Not Supplied");
    $response->send();
    exit();
}
?>