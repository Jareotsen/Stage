<?php
 $config=[
    'db' => [
        'host' => 'localhost',
        'port' => 3308,
        'name' => 'MRRI',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
];
try{
    $db = new PDO(
        "mysql:host=" . $config['db']['host'] . ";port=" . $config['db']['port'] . ";dbname=" . $config['db']['name'] . ";charset=" . $config['db']['charset'],
        $config['db']['user'],
        $config['db']['pass']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, 
                      PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e){
     //message générique d'erreur
    error_log(
        'Erreur PDO :' . $e ->getMessage()
    );

    //message générique poue le user
    http_response_code(500);
    exit('Une erreur interne est survenue. ' .
        'Veuillez réessayer ultérieurement.');
}
