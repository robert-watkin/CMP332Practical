<?php

class UserException extends Exception{

}


class User{
    private $_userId;
    private $_firstName;
    private $_lastName;
    private $_email;
    private $_phoneNumber;
    private $_dateOfBirth;
    private $_password;

    public function __construct($userId, $firstName, $lastName, $email, $phoneNumber, $dateOfBirth, $password)
    {
        $this->setUserID($userId);
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
        $this->setEmail($email);
        $this->setPhoneNumber($phoneNumber);
        $this->setDateOfBirth($dateOfBirth);
        $this->setPassword($password);
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

    public function getPassword(){
        return $this->_password;
    }

    // SETTERS

    public function setUserID($userId){
        if (($userId !== null) && (!is_numeric($userId) || $this->_userId !== null)){
            throw new UserException("Error: Task Id Issue");
        }
        $this->_userId = $userId;
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

    public function setPassword($password){
        if ($password === null || strlen($password) < 6){
            throw new UserException("Error: Password must be at Least 6 characters");
        }

        $this->_password = $password;
    }

    public function getUserAsArray(){
        $user = array();
        $user['userId'] = $this->getUserID();
        $user['firstName'] = $this->getFirstName();
        $user['lastName'] = $this->getLastName();
        $user['email'] = $this->getEmail();
        $user['phoneNumber'] = $this->getPhoneNumber();
        $user['dateOfBirth'] = $this->getDateOfBirth();
        $user['password'] = $this->getPassword();

        return $user;
    }
}





?>