<?php
include 'db.php';

$categoria = $_GET['categoria'] ?? '';
$term_inicio = $_GET['term_inicio'] ?? ''; // Cambiamos 'term' a 'term_inicio'

if (!empty($categoria) && !empty($term_inicio)) {
    $term_inicio = $conn->real_escape_string($term_inicio);
    $sql = "SELECT DISTINCT titulo FROM publicacions WHERE categoria = '$categoria' AND titulo LIKE '$term_inicio%' LIMIT 5"; // Buscamos coincidencias al inicio (%)
    $result = $conn->query($sql);
    $suggestions = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row['titulo'];
        }
    }
    echo json_encode($suggestions);
} else {
    echo json_encode([]);
}

$conn->close();
?>