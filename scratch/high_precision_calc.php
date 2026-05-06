<?php
// Utilisation de la haute précision pour le calcul
$p1 = 150000000 / 10000.00;
$p2 = -10000000 / 10926.92;
$p3 = 49500000 / 10987.61;

$totalParts = $p1 + $p2 + $p3;

$vlFinal = 11357.49;
$valorization = $totalParts * $vlFinal;

echo "Calcul Haute Précision (PHP Double)\n";
echo str_repeat("-", 50) . "\n";
echo "Parts 1: " . number_format($p1, 14, '.', '') . "\n";
echo "Parts 2: " . number_format($p2, 14, '.', '') . "\n";
echo "Parts 3: " . number_format($p3, 14, '.', '') . "\n";
echo str_repeat("-", 50) . "\n";
echo "TOTAL PARTS: " . number_format($totalParts, 14, '.', '') . "\n";
echo "VL FINALE  : " . number_format($vlFinal, 2, '.', '') . "\n";
echo str_repeat("-", 50) . "\n";
echo "VALORISATION: " . number_format($valorization, 10, '.', '') . "\n";
echo "VALORISATION ARRONDIE: " . number_format($valorization, 0, '.', ' ') . " XAF\n";
