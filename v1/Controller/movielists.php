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


if (array_key_exists("listId", $_GET)) {
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
        // get specific list
        $query = $readDB->prepare('select listId, title, DATE_FORMAT(lastUpdated, "%d-%m-%Y %H-%i-%s") as "lastUpdated", userId from tbl_movielists where userId = :userId and listId = :listId');
        $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
        $query->bindParam(':listId', $listId, PDO::PARAM_INT);

        $query->execute();

        $rowCount = $query->rowCount();
        $listArray = array();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage("Error: List ID Not Found or List Belongs to a Different User");
            $response->send();
            exit();
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $list = new MovieList($row['listId'], $row['title'], $row['lastUpdated'], $row['userId']);
            array_push($listArray, $list->getListAsArray()); 
        }

        // TODO get all movies


        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['lists'] = $listArray;


        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->setCache(true);
        $response->setData($returnData);
        $response->send();
        exit();

    }elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
        // patch a specific movie
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

            $titleUpdated = false;

            $queryFields = "";

            if (isset($jsonData->title)){
                $titleUpdated = true;
                $queryFields .= "title = :title, ";
            }

            $lastUpdatedUpdated = true;
            $queryFields .= "lastUpdated = :lastUpdated, ";

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

            $query = $readDB->prepare('select listId, title, lastUpdated, userId from tbl_movielists where userId = :userId and listId = :listId');
            $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
            $query->bindParam(':listId', $listId, PDO::PARAM_INT);

            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: List ID Not Found or List Belongs to a Different User");
                $response->send();
                exit();
            }

            
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $list = new MovieList($row["listId"], $row["title"], $row["lastUpdated"], $row["userId"]);
            }      
            
            $updateQueryString = "update tbl_movielists set ".$queryFields." where listId = :listId";
            $updateQuery = $writeDB->prepare($updateQueryString);
            

            if ($titleUpdated === true){
                $list->setTitle($jsonData->title);
                $updatedTitle = $list->getTitle();
                $updateQuery->bindParam(':title', $updatedTitle, PDO::PARAM_STR);
            }
            
            $current = new DateTime();
            $lastUpdated = $current->format('Y-m-d H:i:s');

            $list->setLastUpdated($lastUpdated);
            $updatedLastUpdated = $list->getLastUpdated();
            $updateQuery->bindParam(':lastUpdated', $updatedLastUpdated, PDO::PARAM_STR);


            $updateQuery->bindParam(':listId', $listId, PDO::PARAM_INT);
            $updateQuery->execute();

            $rowCount = $updateQuery->rowCount();
            $listArray = array();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Movie List Not Updated");
                $response->send();
                exit();
            }

            
            $query = $readDB->prepare('select listId, title, lastUpdated, userId from tbl_movielists where userId = :userId and listId = :listId');
            $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
            $query->bindParam(':listId', $listId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Movie ID Not Found");
                $response->send();
                exit();
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $list = new MovieList($row["listId"], $row["title"], $row["lastUpdated"], $row["userId"]);
                $listArray[] = $list->getListAsArray();
            }    

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['lists'] = $listArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setCache(true);
            $response->setData($returnData);
            $response->send();
            exit();
        }
        catch (MovieException $exception){
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
            $response->addMessage("Failed to Update Movie".$exception);
            $response->send();
            exit();
        }
    }elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        try {
            $query = $writeDB->prepare('delete from tbl_movielists where listId=:listId and userId=:userId');
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: List not Found");
                $response->send();
                exit();
            }

            // TODO delete all list entries referencing this list

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("List Deleted Successfully");
            $response->send();
            exit();
        }
        catch(PDOException $exception){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Delete List");
            $response->send();
            exit();
        }
    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Request Method");
        $response->send();
        exit();
    }
}
elseif(empty($_GET)) {
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        // get all for that specific user
        $query = $readDB->prepare('select listId, title, lastUpdated, userId from tbl_movielists where userId = :userId');
        $query->bindParam(':userId', $authorisedUserId, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();
        $listArray = array();


        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $list = new MovieList($row['listId'], $row['title'], $row['lastUpdated'], $row['userId']);
            array_push($listArray, $list->getListAsArray()); 
        }


        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['lists'] = $listArray;


        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->setCache(true);
        if ($rowCount === 0){
            $response->addMessage("You Have No Lists");
        }
        $response->setData($returnData);
        $response->send();
        exit();


        
    }elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
        // add new movie list to DB
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


            if (!isset($jsonData->title)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->title) ? $response->addMessage("Error: Title is a Mandatory Field") : false);
                $response->send();
                exit();
            }

            // TODO check if list name is already taken

            
            $current = new DateTime();
            $lastUpdated = $current->format('Y-m-d H:i:s');

            $newList = new MovieList(null,
                        (isset($jsonData->title) ? $jsonData->title : null),
                        $lastUpdated,
                        $authorisedUserId
                    );


            $title = $newList->getTitle();
            $lastUpdated = $newList->getLastUpdated();

            $query = $writeDB->prepare('insert into tbl_movielist (title, lastUpdated, userId) value (:title, STR_TO_DATE(:lastUpdated, \'%Y-%m-%d %H:%i:%s\'), :userId)');
            

            $query->bindParam(':title', $title , PDO::PARAM_STR);
            $query->bindParam(':lastUpdated', $lastUpdated , PDO::PARAM_STR);
            $query->bindParam(':userId', $authorisedUserId , PDO::PARAM_STR);

        
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Failed to Insert Movie List into Database");
                $response->send();
                exit();
            }
            
            $lastListID = $writeDB->lastInsertId();

            $query = $readDB->prepare('select listId, title, lastUpdated, userId from tbl_movielist where listId=:listId');
            $query->bindParam(':listId', $lastListID, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $listArray = array();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: List ID Not Found");
                $response->send();
                exit();
            }

            

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                echo $row['lastUpdated'];

                $list = new MovieList($row['listId'], $row['title'], $row['lastUpdated'], $row['userId']);
                array_push($listArray, $list->getListAsArray()); 
            }

            

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['list'] = $listArray;


            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setCache(true);
            $response->setData($returnData);
            $response->send();
            exit();
        }
        catch (MovieException $exception){
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
            $response->addMessage("PDO Error: Failed to Insert Movie into Database".$exception);
            $response->send();
            exit();
        }

    }
    else{
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