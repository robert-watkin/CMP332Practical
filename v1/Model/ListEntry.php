<?php

class EntryException extends Exception{

}

class Movie{
    private $_listId;
    private $_movieId;

    public function __construct($listId, $movieId)
    {
        $this->setListId($listId);
        $this->setMovieId($movieId);
    }

    // GETTERS

    public function getListId(){
        return $this->_listId;
    }

    public function getMovieId(){
        return $this->_movieId;
    }


    // SETTERS

    public function setListId($listId){
        if ($listId !== null && (!is_numeric($listId) || $this->listId !== null)){
            throw new EntryException("Error: List ID Issue");
        }

        $this->_listId = $listId;
    }

    public function setMovieId($movieId){
        if ($movieId !== null && (!is_numeric($movieId) || $this->_movieId !== null)){
            throw new MovieException("Error: Movie ID Issue");
        }

        $this->_movieId = $movieId;
    }
}
?>