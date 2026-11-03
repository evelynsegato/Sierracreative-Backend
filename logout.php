<?php

require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Resposta::erro('Método não permitido. Use POST.', 405);
}

(new AuthController())->logout();