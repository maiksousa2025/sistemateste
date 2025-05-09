<?php
require 'conexao.php';
try {
    $query = $pdo->query("SHOW TABLES");
    echo "<h2>Conexão com o banco de dados estabelecida com sucesso!</h2>";
    echo "<h3>Tabelas no banco de dados avanc958_maik2025:</h3>";
    echo "<ul>";
    while ($table = $query->fetch(PDO::FETCH_COLUMN)) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
?>