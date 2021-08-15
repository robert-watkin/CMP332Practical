<?php

class UserException extends Exception{

}


class User{
    private $_userId;
    private $_APIKey;
    private $_firstName;
    private $_lastName;
    private $_email;
    private $_phoneNumber;
    private $_dateOfBirth;

    public function __construct($userId, $APIKey, $firstName, $lastName, $email, $phoneNumber, $dateOfBirth)
    {
        $this->setUserID($userId);
        $this->setAPIKey($APIKey);
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
        $this->setEmail($email);
        $this->setPhoneNumber($phoneNumber);
        $this->setDateOfBirth($dateOfBirth);
    }

    // GETTERS

    public function getUserID(){
        return $this->_userId;
    }

    public function getAPIKey(){
        return $this->_APIKey;
    }

    public function getFirstName(){
        return $this->_firstName;
    }

    public function getLastName(){
        return $this->_lastName;
    }

    public function getEmail(){
        return $this->_email;
    }

    public function getPhoneNumber(){
        return $this->_phoneNumber;
    }

    public function getDateOfBirth(){
        return $this->_dateOfBirth;
    }


    // SETTERS

    public function setUserID($userId){
        if (($userId !== null) && (!is_numeric($userId) || $this->_userId !== null)){
            throw new UserException("Error: Task Id Issue");
        }
        $this->_userId = $userId;
    }

    public function setAPIKey($APIKey){
        $this->_APIKey = $APIKey;
    }

    public function setFirstName($firstName){

        $this->_firstName = $firstName;
    }

    public function setLastName($lastName){

        $this->_lastName = $lastName;
    }

    public function setEmail($email){
        $this->_email = $email;
    }

    public function setPhoneNumber($phoneNumber){
        $this->_phoneNumber = $phoneNumber;
    }

    public function setDateOfBirth($dateOfBirth){
        $this->_dateOfBirth = $dateOfBirth;
    }

    public function getUserAsArray(){
        $user = array();
        $user['userId'] = $this->getUserID();
        $user['APIKey'] = $this->getAPIKey();
        $user['firstName'] = $this->getFirstName();
        $user['lastName'] = $this->getLastName();
        $user['email'] = $this->getEmail();
        $user['phoneNumber'] = $this->getPhoneNumber();
        $user['dateOfBirth'] = $this->getDateOfBirth();

        return $user;
    }
}





?>