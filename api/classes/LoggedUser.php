<?php
class LoggedUser
{
    public $user;
    public $token;

    public function __construct($user, $token) {
        $this->user = $user;
        $this->token = $token;
    }    
}
