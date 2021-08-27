<?php
require_once('db.php');
require_once('../Model/Movie.php');
require_once('../Model/MovieList.php');
require_once('../Model/User.php');
require_once('../Model/Response.php');

// Handles user authorisation
require_once('auth.php');



// setup DB connection
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
        $response->addMessage("Database Connection Failed".$exception);
        $response->send();
        exit();
    }
}


if (array_key_exists("listId", $_GET) && !array_key_exists("movieId", $_GET)) {
    $listId = $_GET['listId'];

    if (empty($listId) || !is_numeric($listId)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("List ID: Cannot be null and must be numeric");
        $response->send();
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
    

    }elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Request Method");
        $response->send();
        exit();
    }
} elseif (array_key_exists("movieId", $_GET) && !array_key_exists("listId", $_GET)) {
    $movieId = $_GET['movieId'];

    if (empty($movieId) || !is_numeric($movieId)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Movie ID: Cannot be null and must be numeric");
        $response->send();
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
    

    }elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Request Method");
        $response->send();
        exit();
    }    

} elseif (array_key_exists("movieId", $_GET) && array_key_exists("listId", $_GET)) {
    $movieId = $_GET['movieId'];
    $listId = $_GET['listId'];

    if (empty($movieId) || !is_numeric($movieId)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Movie ID: Cannot be null and must be numeric");
        $response->send();
        exit();
    }

    if (empty($listId) || !is_numeric($listId)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("List ID: Cannot be null and must be numeric");
        $response->send();
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
    

    }elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Request Method");
        $response->send();
        exit();
    }    
        
} 
else {
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Error: Invalid Endpoint");
    $response->send();
    exit();
}
?>