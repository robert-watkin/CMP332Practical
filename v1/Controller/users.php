<?php
require_once('db.php');
require_once('../Model/User.php');
require_once('../Model/Response.php');


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
if (empty($_GET))
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


            if (!isset($jsonData->firstName) || !isset($jsonData->lastName) || !isset($jsonData->email) || !isset($jsonData->phoneNumber) || !isset($jsonData->dateOfBirth) || !isset($jsonData->password)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->firstName) ? $response->addMessage("Error: First Name is a Mandatory Field") : false);
                (!isset($jsonData->lastName) ? $response->addMessage("Error: Last Name is a Mandatory Field") : false);   
                (!isset($jsonData->email) ? $response->addMessage("Error: Email is a Mandatory Field") : false);     
                (!isset($jsonData->phoneNumber) ? $response->addMessage("Error: Phone Number is a Mandatory Field") : false);   
                (!isset($jsonData->dateOfBirth) ? $response->addMessage("Error: Date of Birth is a Mandatory Field") : false);    
                (!isset($jsonData->password) ? $response->addMessage("Error: Password is a Mandatory Field") : false);    
                $response->send();
                exit();
            }

            $newUser = new User(null,
                        (isset($jsonData->firstName) ? $jsonData->firstName : null),
                        (isset($jsonData->lastName) ? $jsonData->lastName : null),
                        (isset($jsonData->email) ? $jsonData->email : null),
                        (isset($jsonData->phoneNumber) ? $jsonData->phoneNumber : null),
                        (isset($jsonData->dateOfBirth) ? $jsonData->dateOfBirth : null),
                        (isset($jsonData->password) ? $jsonData->password : null));

            $firstName = $newUser->getFirstName();
            $lastName = $newUser->getLastName();
            $email = $newUser->getEmail();
            $phoneNumber = $newUser->getPhoneNumber();
            $dateOfBirth = $newUser->getDateOfBirth();
            $password = password_hash($newUser->getPassword(), PASSWORD_BCRYPT);

            if (!$password){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Error: An Issue Occured While Creating the Account");
                $response->send();
                exit();
            }


            $query = $writeDB->prepare('insert into tbl_users (firstName, lastName, email, phoneNumber, dateOfBirth, password) value (:firstName, :lastName, :email, :phoneNumber, STR_TO_DATE(:dateOfBirth, \'%d-%m-%Y\'), :password)');

            $query->bindParam(':firstName', $firstName , PDO::PARAM_STR);
            $query->bindParam(':lastName', $lastName , PDO::PARAM_STR);
            $query->bindParam(':email', $email , PDO::PARAM_STR);
            $query->bindParam(':phoneNumber', $phoneNumber , PDO::PARAM_STR);
            $query->bindParam(':dateOfBirth', $dateOfBirth , PDO::PARAM_STR);
            $query->bindParam(':password', $password , PDO::PARAM_STR);
        
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

            $query = $readDB->prepare('select userId, firstName, lastName, email, phoneNumber, dateOfBirth, password from tbl_users where userId=:userId');
            $query->bindParam(':userId', $lastUserID, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $userArray = array();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Task ID Not Found");
                $response->send();
                exit();
            }
            

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $user = new User($row['userId'], $row['firstName'], $row['lastName'], $row['email'], $row['phoneNumber'], $row['dateOfBirth'], $row['password']);
                array_push($userArray, $user->getUserAsArray()); 
            }

            

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['users'] = $userArray;


            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setCache(true);
            $response->setData($returnData);
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
            $response->addMessage("PDO Error: Failed to Insert User into Database");
            $response->send();
            exit();
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET'){
        require_once('auth.php');


        // GET SPECIFIC USER
        $query = $readDB->prepare('select userId, firstName, lastName, email, phoneNumber, dateOfBirth, password from tbl_users where userId = :userId');
        $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();
        $userArray = array();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage("Error: User ID Not Found");
            $response->send();
            exit();
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $user = new User($row['userId'], $row['firstName'], $row['lastName'], $row['email'], $row['phoneNumber'], $row['dateOfBirth'], $row['password']);
            array_push($userArray, $user->getUserAsArray()); 
        }


        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['users'] = $userArray;


        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->setCache(true);
        $response->setData($returnData);
        $response->send();
        exit();

    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        require_once('auth.php');
        // DELETE SPECIFIC USER

        try {
            $query = $writeDB->prepare('delete from tbl_users where userId=:userId');
            $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: User not Found");
                $response->send();
                exit();
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("User Deleted Successfully");
            $response->send();
            exit();
        }
        catch(PDOException $exception){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Delete Task");
            $response->send();
            exit();
        }


    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH'){
        require_once('auth.php');
        // UPDATE SPECIFIC USER
        
        try{
            if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Error: Invalid Content Type Header");
                $response->send();
                exit();
            }

            $rawPATCHData = file_get_contents('php://input');

            if(!$jsonData = json_decode($rawPATCHData)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Error: Request Body is not Valid JSON");
                $response->send();
                exit();
            }

            $firstNameUpdated = false;
            $lastNameUpdated = false;
            $emailUpdated = false;
            $phoneNumberUpdated = false;
            $dateOfBirthUpdated = false;

            $queryFields = "";

            if (isset($jsonData->firstName)){
                $firstNameUpdated = true;
                $queryFields .= "firstName = :firstName, ";
            }
            if (isset($jsonData->lastName)){
                $lastNameUpdated = true;
                $queryFields .= "lastName = :lastName, ";
            }
            if (isset($jsonData->email)){
                $emailUpdated = true;
                $queryFields .= "email = :email, ";
            }
            if (isset($jsonData->phoneNumber)){
                $phoneNumberUpdated = true;
                $queryFields .= "phoneNumber = :phoneNumber, ";
            }
            if (isset($jsonData->dateOfBirth)){
                $dateOfBirthUpdated = true;
                $queryFields .= "dateOfBirth = :dateOfBirth, ";
            }
            

            $queryFields = rtrim($queryFields, ", ");

            if ($queryFields === ""){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("No Data Provided");
                $response->send();
                exit();
            }

            $counter = 0;

            $query = $readDB->prepare('select userId, firstName, lastName, email, phoneNumber, DATE_FORMAT(dateOfBirth, "%d-%m-%Y %H:%i") as "dateOfBirth", password from tbl_users where userId = :userId');
            $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: User ID Not Found");
                $response->send();
                exit();
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $user = new User($row["userId"], $row["firstName"], $row["lastName"], $row["email"], $row["phoneNumber"], $row["dateOfBirth"], $row["password"]);
            }      
            
            $updateQueryString = "update tbl_users set ".$queryFields." where userId = :userId";
            $updateQuery = $writeDB->prepare($updateQueryString);

            if ($firstNameUpdated === true){
                $user->setFirstName($jsonData->firstName);
                $updatedFirstName = $user->getFirstName();
                $updateQuery->bindParam('firstName', $updatedFirstName, PDO::PARAM_STR);
            }
            if ($lastNameUpdated === true){
                $user->setLastName($jsonData->lastName);
                $updatedLastName = $user->getLastName();
                $updateQuery->bindParam('lastName', $updatedLastName, PDO::PARAM_STR);
            }
            if ($emailUpdated === true){
                $user->setEmail($jsonData->email);
                $updatedEmail = $user->getEmail();
                $updateQuery->bindParam('email', $updatedEmail, PDO::PARAM_STR);
            }
            if ($phoneNumberUpdated === true){
                $user->setPhoneNumber($jsonData->phoneNumber);
                $updatedPhoneNumber = $user->getPhoneNumber();
                $updateQuery->bindParam('phoneNumber', $updatedPhoneNumber, PDO::PARAM_STR);
            }
            if ($dateOfBirthUpdated === true){
                $user->setDateOfBirth($jsonData->dateOfBirth);
                $updatedDateOfBirth = $user->getDateOfBirth();
                $updateQuery->bindParam('dateOfBirth', $updatedDateOfBirth, PDO::PARAM_STR);
            }

            $updateQuery->bindParam('userId', $authorisedUserId, PDO::PARAM_INT);
            $updateQuery->execute();

            $rowCount = $updateQuery->rowCount();
            $taskArray = array();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("User Not Updated");
                $response->send();
                exit();
            }

            $query = $readDB->prepare('select userId, firstName, lastName, email, phoneNumber, DATE_FORMAT(dateOfBirth, "%d-%m-%Y %H:%i") as "dateOfBirth", password from tbl_users where userId = :userId');
            $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: User ID Not Found");
                $response->send();
                exit();
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $user = new User($row["userId"], $row["firstName"], $row["lastName"], $row["email"], $row["phoneNumber"], $row["dateOfBirth"], $row["password"]);
                $userArray[] = $user->getUserAsArray();
            }    

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['users'] = $userArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setCache(true);
            $response->setData($returnData);
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
            $response->addMessage("Failed to Update User");
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