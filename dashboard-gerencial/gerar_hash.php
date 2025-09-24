<?php
// Gera um hash de senha para uso no banco de dados
$senha = 'treinamento';
$hash = password_hash($senha, PASSWORD_DEFAULT);
echo $hash;
