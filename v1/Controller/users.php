<?php
require_once('db.php');
require_once('../Model/User.php');
require_once('../Model/Response.php');

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

if (array_key_exists("APIKey", $_GET)){
    $APIKey = $_GET['APIKey'];

    // Check API Key is Supplied
    if ($APIKey === '' || $APIKey === null){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("API Key: Cannot be null");
        $response->send();
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET'){
        // GET SPECIFIC USER
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        // DELETE SPECIFIC USER
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH'){
        // UPDATE SPECIFIC USER
    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Invalid Request Method");
        $response->send();
        exit();
    }

}
elseif (empty($_GET))
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST'){
        // CREATES A NEW USER
        try {
            if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Error: Invalid Content Type Header");
                $response->send();
                exit();
            }

            $rawPOSTData = file_get_contents('php://input');

            if(!$jsonData = json_decode($rawPOSTData)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Error: Request Body is not Valid JSON");
                $response->send();
                exit();
            }

            // Generate API Key
            $APIKey = md5(rand(1, 99999));

            if (!isset($jsonData->firstName) || !isset($jsonData->lastName) || !isset($jsonData->email) || !isset($jsonData->phoneNumber) || !isset($jsonData->dateOfBirth)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->firstName) ? $response->addMessage("Error: First Name is a Mandatory Field") : false);
                (!isset($jsonData->lastName) ? $response->addMessage("Error: Last Name is a Mandatory Field") : false);   
                (!isset($jsonData->email) ? $response->addMessage("Error: Email is a Mandatory Field") : false);     
                (!isset($jsonData->phoneNumber) ? $response->addMessage("Error: Phone Number is a Mandatory Field") : false);   
                (!isset($jsonData->dateOfBirth) ? $response->addMessage("Error: Date of Birth is a Mandatory Field") : false);    
                $response->send();
                exit();
            }

            $newUser = new User(null,
                        $APIKey,
                        (isset($jsonData->firstName) ? $jsonData->firstName : null),
                        (isset($jsonData->lastName) ? $jsonData->lastName : null),
                        (isset($jsonData->email) ? $jsonData->email : null),
                        (isset($jsonData->phoneNumber) ? $jsonData->phoneNumber : null),
                        (isset($jsonData->dateOfBirth) ? $jsonData->dateOfBirth : null));

            $firstName = $newUser->getFirstName();
            $lastName = $newUser->getLastName();
            $email = $newUser->getEmail();
            $phoneNumber = $newUser->getPhoneNumber();
            $dateOfBirth = $newUser->getDateOfBirth();


            $query = $writeDB->prepare('insert into tbl_users (APIKey, firstName, lastName, email, phoneNumber, dateOfBirth) value (:APIKey, :firstName, :lastName, :email, :phoneNumber, STR_TO_DATE(:dateOfBirth, \'%d-%m-%Y\'))');

            $query->bindParam(':APIKey', $APIKey , PDO::PARAM_STR);
            $query->bindParam(':firstName', $firstName , PDO::PARAM_STR);
            $query->bindParam(':lastName', $lastName , PDO::PARAM_STR);
            $query->bindParam(':email', $email , PDO::PARAM_STR);
            $query->bindParam(':phoneNumber', $phoneNumber , PDO::PARAM_STR);
            $query->bindParam(':dateOfBirth', $dateOfBirth , PDO::PARAM_STR);
        
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Failed to Insert User into Database");
                $response->send();
                exit();
            }
            
            $lastUserID = $writeDB->lastInsertId();

            $query = $readDB->prepare('select userId, APIKey, firstName, lastName, email, phoneNumber, dateOfBirth from tbl_users where userId = :userId');
            $query->bindParam(':userId', $lastUserID, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $taskArray = array();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Task ID Not Found");
                $response->send();
                exit();
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new User($row['userId'], $row['APIKey'], $row['firstName'], $row['lastName'], $row['email'], $row['phoneNumber'], $row['dateOfBirth']);
                array_push($taskArray, $task->getUserAsArray()); 
            }


            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;


            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setCache(true);
            $response->setData($returnData);
            $response->addMessage("Take Note of Your Unique API Key");
            $response->send();
            exit();
        }
        catch (UserException $exception){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit();
        }
        catch (PDOException $exception){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("PDO Error: Failed to Insert User into Database".$exception);
            $response->send();
            exit();
        }
    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Invalid Request Method");
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