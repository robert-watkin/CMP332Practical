<?php
class MovieListException extends Exception{

}

class MovieList{
    private $_listId;
    private $_title;
    private $_movieIds;
    private $_lastUpdated;
    private $_userId;

    public function __construct($listId, $title, $movieIds, $lastUpdated, $userId)
    {
        $this->setListId($listId);
        $this->setTitle($title);
        $this->setMovieIds($movieIds);
        $this->setLastUpdated($lastUpdated);
        $this->setUserId($userId);
    }

    // GETTERS

    public function getListId(){
        $this->_listId;
    }

    public function getTitle(){
        return $this->_title;
    }

    public function getMovieIds(){
        return $this->_movieIds;
    }

    public function getLastUpdated(){
        return $this->_lastUpdated;
    }

    public function getUserId(){
        return $this->_userId;
    }


    // SETTERS
    public function setListId($listId){
        $this->_listId = $listId;
    }

    public function setTitle($title){
        $this->_title = $title;
    }

    public function setMovieIds($movieIds){
        $this->_movieIds = $movieIds;
    }

    public function setLastUpdated($lastUpdated){
        $this->_lastUpdated = $lastUpdated;
    }

    public function setUserId($userId){
        $this->_userId = $userId;
    }
}
?>