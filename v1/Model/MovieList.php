<?php
class MovieListException extends Exception{

}

class MovieList{
    private $_listId;
    private $_title;
    private $_lastUpdated;
    private $_userId;

    public function __construct($listId, $title, $lastUpdated, $userId)
    {
        $this->setListId($listId);
        $this->setTitle($title);
        $this->setLastUpdated($lastUpdated);
        $this->setUserId($userId);
    }

    public function isValidDate($date, $format = 'Y-m-d H:i:s'){
        if ($date === null || $date === ""){
            return true;
        }
        
        $dateObj = DateTime::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) == $date;
    }

    // GETTERS

    public function getListId(){
        return $this->_listId;
    }

    public function getTitle(){
        return $this->_title;
    }


    public function getLastUpdated(){
        return $this->_lastUpdated;
    }

    public function getUserId(){
        return $this->_userId;
    }


    // SETTERS
    public function setListId($listId){
        if ($listId !== null && (!is_numeric($listId) || $this->_listId !== null )){
            throw new MovieListException("Error: List ID Issue");
        }
        $this->_listId = $listId;
    }

    public function setTitle($title){
        $this->_title = $title;
    }


    public function setLastUpdated($lastUpdated){
        if (!$this->isValidDate($lastUpdated)){
            throw new MovieListException("Error: Last Updated Issue");
        }
        $this->_lastUpdated = $lastUpdated;
    }

    public function setUserId($userId){
        $this->_userId = $userId;
    }

    public function getListAsArray(){
        $list = array();
        $list['listId'] = $this->getListId();
        $list['title'] = $this->getTitle();
        $list['lastUpdated'] = $this->getLastUpdated();
        $list['userId'] = $this->getUserId();

        return $list;
    }

    public function saveLastUpdated(){
        // TODO update the last updated field in the db to now
    }
}
?>