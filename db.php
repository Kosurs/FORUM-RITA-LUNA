<?php
// db.php: Conexão com MySQL e o codigo para fazer contato com o banco de dados
$host = "127.0.0.1";
$porta = "3306";
$banco = "forum1";
$usuario = "root";
$senha = "";

try {
    $conexao = new PDO("mysql:host=" .$host.";port=".$porta.";dbname=".$banco,$usuario,$senha);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $erro) {
    die('Erro na conexão: ' . $erro->getMessage());
}
