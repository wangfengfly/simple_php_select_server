<?php
error_reporting(~E_NOTICE);
set_time_limit(0);

/**
 * Created by PhpStorm.
 * User: wangfeng211731
 * Date: 2016/9/21
 * Time: 17:13
 */
class Master{
    private $max_clients;
    private $ip;
    private $port;

    public function  __construct(){
        $this->ip = "0.0.0.0";
        $this->port = 5000;
        $this->max_clients = 2;
    }

    public function run(){
        if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))){
            $errcode = socket_last_error();
            $errmsg = socket_strerror($errcode);
            die("Could not create socket: [$errcode] $errmsg");
        }
        echo "Socket created.\n";
        //绑定地址
        if(!socket_bind($sock, $this->ip, $this->port)){
            $errcode = socket_last_error();
            $errmsg = socket_strerror($errcode);
            die("Could not bind socket: [$errcode] $errmsg \n");
        }
        echo "Socket bind OK \n";

        if(!socket_listen($sock, $this->max_clients)){
            $errcode = socket_last_error();
            $errmsg = socket_strerror($errcode);
            die("Could not listen on socket: [$errcode] $errmsg \n");
        }
        echo "Socket listen OK \n";
        echo "Waiting for incoming connections ...\n";
        //array of client sockets
        $client_socks = array();
        //start loop to listen for incoming connections and process existing connection
        while(true) {
            //prepare array of readable client sockets
            $read = array();
            //first socket is the master socket
            $read[0] = $sock;
            //now add the existing client sockets
            for($i = 0; $i < $this->max_clients; $i++){
                if($client_socks[$i] != null){
                    $read[$i + 1] = $client_socks[$i];
                }
            }
            //blocking call
            if(socket_select($read, $write, $except, null) === false){
                $errcode = socket_last_error();
                $errmsg = socket_strerror($errcode);
                die("Could not listen on socket: [$errcode] $errmsg \n");
            }
            //if ready contains the master socket, then a new connection has come in
            if(in_array($sock, $read)){
                for($i = 0; $i < $this->max_clients; $i++){
                    if($client_socks[$i] == null){
                        $client_socks[$i] = socket_accept($sock);
                        //display info about client who is connected
                        if(socket_getpeername($client_socks[$i], $address, $port)){
                            echo "Client $address : $port is now connected to us. \n";
                        }
                        //send welcome message to client
                        $message = "Welcom to php socket server version 1.0 \n";
                        $message .= "Enter a message and press enter, and i shall reply back\n";
                        socket_write($client_socks[$i], $message);
                        break;
                    }
                }
            }
            for($i = 0; $i < $this->max_clients; $i++){
                if(in_array($client_socks[$i], $read)){
                    $input = socket_read($client_socks[$i], 1024);
                    if($input == ''){
                        //zero length string meaning disconnected, remove and close client sockets
                        unset($client_socks[$i]);
                        socket_close($client_socks[$i]);
                    }
                    $n = trim($input);
                    $output = "OK ... $n \n";
                    echo "sending output to client \n";
                    socket_write($client_socks[$i], $output);
                }
            }
        }

    }

}

$m = new Master();
$m->run();