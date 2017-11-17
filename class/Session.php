<?php

class Session
{
    /**
     * Stock les variables de configurations présentes dans config.php
     * @var array
     */
    private $config = [];

    /**
     * Instance de Session
     * @var Session
     */
    private static $_instance;

    /**
     * Constructeur de Session
     */
    public function __construct()
    {
        session_start();

        $this->config = require dirname(__DIR__) . '/config/config.php';

        if ($this->config["security"]) {
            if (!isset($_SESSION["logged_in"])) {
                $_SESSION["logged_in"] = false;
            }
        } else {
            $_SESSION["logged_in"] = true;
        }
    }

    /**
     * Permet de retourner une instance de Session
     * @return POO Instance de Session
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Session();
        }

        return self::$_instance;
    }

    /**
     * Connexion
     * @param  string $password Password
     * @return boolean          Connecté ou pas
     */
    public function login(string $password)
    {
        if ($this->config["password"] === hash("sha256",$password)) {
            $_SESSION["logged_in"] = true;
        } else {
            $_SESSION["logged_in"] = false;
        }
        return (bool)$_SESSION["logged_in"];
    }

    /**
     * Savoir si on est déjà connectés
     * @return boolean Connecté ou déconnecté
     */
    public function is_logged_in()
    {
        return (bool)$_SESSION["logged_in"];
    }

    /**
     * Permet de se déconnecter
     * @return void 
     */
    public function logout()
    {
        unset($_SESSION);
        session_destroy();
    }
}
