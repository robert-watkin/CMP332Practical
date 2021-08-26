<?php

class MovieException extends Exception{

}

class Movie{
    private $_movieId;
    private $_title;
    private $_description;
    private $_runTime;
    private $_releaseDate;

    public function __construct($movieId, $title, $description, $runTime, $releaseDate)
    {
        $this->setMovieId($movieId);
        $this->setTitle($title);
        $this->setDescription($description);
        $this->setRunTime($runTime);
        $this->setReleaseDate($releaseDate);
    }

    public function isValidDate($date, $format = 'Y-m-d'){
        if ($date === null || $date === ""){
            return true;
        }
        
        $dateObj = DateTime::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) == $date;
    }

    // GETTERS

    public function getMovieId(){
        return $this->_movieId;
    }

    public function getTitle(){
        return $this->_title;
    }

    public function getDescription(){
        return $this->_description;
    }

    public function getRunTime(){
        return $this->_runTime;
    }

    public function getReleaseDate(){
        return $this->_releaseDate;
    }

    // SETTERS

    public function setMovieId($movieId){
        if ($movieId !== null && (!is_numeric($movieId) || $this->_movieId !== null)){
            throw new MovieException("Error: Movie ID Issue");
        }

        $this->_movieId = $movieId;
    }

    public function setTitle($title){
        $this->_title = $title;
    }

    public function setDescription($description){
        $this->_description = $description;
    }

    public function setRunTime($runTime){
        if ($runTime !== null && !is_numeric($runTime)){
            throw new MovieException("Error: Run Time Exception");
        }
        $this->_runTime = $runTime;
    }

    public function setReleaseDate($releaseDate){
        if (!$this->isValidDate($releaseDate, 'd-m-Y') && !$this->isValidDate($releaseDate)){
            throw new MovieException("Error: Release Date Issue");
        }
        $this->_releaseDate = $releaseDate;
    }

    public function getMovieAsArray(){
        $movie = array();
        $movie['movieId'] = $this->getMovieId();
        $movie['title'] = $this->getTitle();
        $movie['description'] = $this->getDescription();
        $movie['runTime'] = $this->getRunTime();
        $movie['releaseDate'] = $this->getReleaseDate();


        return $movie;
    }
}
?>