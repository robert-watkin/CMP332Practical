<?php
require_once('db.php');
require_once('../Model/Movie.php');
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


if (array_key_exists("movieId", $_GET)) {
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
        //  get specific movie
        try{
            $query = $readDB->prepare('select movieId, title, description, runTime, DATE_FORMAT(releaseDate, "%d-%m-%Y") as "releaseDate" from tbl_movies where movieId=:movieId');
            $query->bindParam(":movieId", $movieId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $movieArray = array();
            

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Movie ID Not Found");
                $response->send();
                exit();
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Movie($row['movieId'], $row['title'], $row['description'], $row['runTime'], $row['releaseDate']);
                array_push($movieArray, $task->getMovieAsArray()); 
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['movies'] = $movieArray;

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
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit();
        }
        catch (PDOException $exception){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error: Failed to Get Movies");
            $response->send();
            exit();
        }
    }
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
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
            $descriptionUpdated = false;
            $runTimeUpdated = false;
            $releaseDateUpdated = false;

            $queryFields = "";

            if (isset($jsonData->title)){
                $titleUpdated = true;
                $queryFields .= "title = :title, ";
            }
            if (isset($jsonData->description)){
                $descriptionUpdated = true;
                $queryFields .= "description = :description, ";
            }
            if (isset($jsonData->runTime)){
                $runTimeUpdated = true;
                $queryFields .= "runTime = :runTime, ";
            }
            if (isset($jsonData->releaseDate)){
                $releaseDateUpdated = true;
                $queryFields .= "releaseDate = STR_TO_DATE(:releaseDate, '%d-%m-%Y'), ";
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

            $query = $readDB->prepare('select movieId, title, description, runTime, DATE_FORMAT(releaseDate, "%d-%m-%Y") as "releaseDate" from tbl_movies where movieId = :movieId');
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
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
                $movie = new Movie($row["movieId"], $row["title"], $row["description"], $row["runTime"], $row["releaseDate"]);
            }      
            
            $updateQueryString = "update tbl_movies set ".$queryFields." where movieId = :movieId";
            $updateQuery = $writeDB->prepare($updateQueryString);

            if ($titleUpdated === true){
                $movie->setTitle($jsonData->title);
                $updatedTitle = $movie->getTitle();
                $updateQuery->bindParam(':title', $updatedTitle, PDO::PARAM_STR);
            }
            if ($descriptionUpdated === true){
                $movie->setDescription($jsonData->description);
                $updatedDescription = $movie->getDescription();
                $updateQuery->bindParam(':description', $updatedDescription, PDO::PARAM_STR);
            }
            if ($runTimeUpdated === true){
                $movie->setRunTime($jsonData->runTime);
                $updatedRunTime = $movie->getRunTime();
                $updateQuery->bindParam(':runTime', $updatedRunTime, PDO::PARAM_STR);
            }
            if ($releaseDateUpdated === true){
                $movie->setReleaseDate($jsonData->releaseDate);
                $updatedReleaseDate = $movie->getReleaseDate();
                $updateQuery->bindParam(':releaseDate', $updatedReleaseDate, PDO::PARAM_STR);
            }


            $updateQuery->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $updateQuery->execute();

            $rowCount = $updateQuery->rowCount();
            $movieArray = array();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Movie Not Updated");
                $response->send();
                exit();
            }

            $query = $readDB->prepare('select movieId, title, description, runTime, DATE_FORMAT(releaseDate, "%d-%m-%Y") as "releaseDate" from tbl_movies where movieId = :movieId');
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
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
                $movie = new Movie($row["movieId"], $row["title"], $row["description"], $row["runTime"], $row["releaseDate"]);
                $movieArray[] = $movie->getMovieAsArray();
            }    

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['movies'] = $movieArray;

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
        // delete specific
        try {
            $query = $writeDB->prepare('delete from tbl_movies where movieId=:movieId');
            $query->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Movie not Found");
                $response->send();
                exit();
            }

            // TODO delete all list entries referencing this movie

            

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("Movie Deleted Successfully");
            $response->send();
            exit();
        }
        catch(PDOException $exception){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Delete Movie");
            $response->send();
            exit();
        }
    }
    else {
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
        // get all movies on the DB
        try{
            $query = $readDB->prepare('select movieId, title, description, runTime, DATE_FORMAT(releaseDate, "%d-%m-%Y") as "releaseDate" from tbl_movies');
            $query->execute();

            $rowCount = $query->rowCount();
            $movieArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Movie($row['movieId'], $row['title'], $row['description'], $row['runTime'], $row['releaseDate']);
                array_push($movieArray, $task->getMovieAsArray()); 
            }
            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['movies'] = $movieArray;

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
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit();
        }
        catch (PDOException $exception){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error: Failed to Get Movies");
            $response->send();
            exit();
        }
    }elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
        // add new movie to DB
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


            if (!isset($jsonData->title) || !isset($jsonData->description) || !isset($jsonData->runTime) || !isset($jsonData->releaseDate)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->title) ? $response->addMessage("Error: Title is a Mandatory Field") : false);
                (!isset($jsonData->description) ? $response->addMessage("Error: Description is a Mandatory Field") : false);   
                (!isset($jsonData->runTime) ? $response->addMessage("Error: Run Time is a Mandatory Field") : false);     
                (!isset($jsonData->releaseDate) ? $response->addMessage("Error: Release Date is a Mandatory Field") : false);   
                $response->send();
                exit();
            }

            $newMovie = new Movie(null,
                        (isset($jsonData->title) ? $jsonData->title : null),
                        (isset($jsonData->description) ? $jsonData->description : null),
                        (isset($jsonData->runTime) ? $jsonData->runTime : null),
                        (isset($jsonData->releaseDate) ? $jsonData->releaseDate : null));

                        echo $jsonData->title;

            $title = $newMovie->getTitle();
            $description = $newMovie->getDescription();
            $runTime = $newMovie->getRunTime();
            $releaseDate = $newMovie->getReleaseDate();


            $query = $writeDB->prepare('insert into tbl_movies (title, description, runTime, releaseDate) value (:title, :description, :runTime, STR_TO_DATE(:releaseDate, \'%d-%m-%Y\'))');

            $query->bindParam(':title', $title , PDO::PARAM_STR);
            $query->bindParam(':description', $description , PDO::PARAM_STR);
            $query->bindParam(':runTime', $runTime , PDO::PARAM_INT);
            $query->bindParam(':releaseDate', $releaseDate , PDO::PARAM_STR);

        
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
            
            $lastMovieID = $writeDB->lastInsertId();

            $query = $readDB->prepare('select movieId, title, description, runTime, releaseDate from tbl_movies where movieId=:movieId');
            $query->bindParam(':movieId', $lastMovieID, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $movieArray = array();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Movie ID Not Found");
                $response->send();
                exit();
            }
            

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $movie = new Movie($row['movieId'], $row['title'], $row['description'], $row['runTime'], $row['releaseDate']);
                array_push($movieArray, $movie->getMovieAsArray()); 
            }

            

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['movies'] = $movieArray;


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