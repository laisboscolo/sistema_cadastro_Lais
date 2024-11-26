<!-- 1ª Digitação (Aqui) -->
<?php
$servername="localhost";
$username="root";
$password= "";
$dbname = "sistema";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die ("Connection failed:" . $conn->connect_error);
}
$sql = "SHOW COLUMNS FROM produtos LIKE 'imagem'";

$result= $conn->query($sql);
if ($result->num_rows == 0){
$sql = "ALTER TABLE produtos ADD COLUMN image VARCHAR (255)";
$conn->query($sql);
}

$sql = "SHOW COLUMNS FROM fornecedores LIKE 'imagem'";
$result= $conn->query($sql);
if ($result->num_rows == 0){
    $sql = "ALTER TABLE fornecedores ADD COLUMN image VARCHAR (255)";
    $conn->query($sql);
}
?>