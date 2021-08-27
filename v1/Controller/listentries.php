<?php
require_once('db.php');
require_once('../Model/Movie.php');
require_once('../Model/MovieList.php');
require_once('../Model/ListEntry.php');
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
        // add new entry to DB
        try {
            // check the list exists
            $query = $readDB->prepare('select listId, title, lastUpdated, userId from tbl_movielists where listId=:listId');
            $query->bindParam(':listId', $listId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("ERROR: List Not Found");
                $response->send();
                exit();
            }

            // check the movie exists
            $query = $readDB->prepare('select movieId, title, description, runTime, releaseDate from tbl_movies where movieId=:movieId');
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $query->execute();
            
            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("ERROR: Movie Not Found");
                $response->send();
                exit();
            }

            $query = $readDB->prepare('select listId, movieId from tbl_listentries where listId=:listId and movieid=:movieId');
            $query->bindParam(':listId', $listId, PDO::PARAM_INT);
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount !== 0){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("ERROR: The Entry Already Exists in the Database");
                $response->send();
                exit();
            }


            $newEntry = new ListEntry($listId, $movieId);

            $query = $writeDB->prepare('insert into tbl_listentries (listId, movieId) value (:listId, :movieId)');
            $query->bindParam(':listId', $listId, PDO::PARAM_INT);
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Failed to Insert Movie into Database");
                $response->send();
                exit();
            }
            
            $query = $readDB->prepare('select listId, movieId from tbl_listentries where listId=:listId and movieid=:movieId');
            $query->bindParam(':listId', $listId, PDO::PARAM_INT);
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $entryArray = array();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("ERROR: Entry Not Found");
                $response->send();
                exit();
            }
            

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $entry = new ListEntry($row['listId'], $row['movieId']);
                array_push($entryArray, $entry->getEntryAsArray()); 
            }
            

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['entry'] = $entryArray;


            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setCache(true);
            $response->setData($returnData);
            $response->send();
            exit();
        }
        catch (EntryException $exception){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit();
        } catch (MovieException $exception){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit();
        } catch (MovieListException $exception){
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