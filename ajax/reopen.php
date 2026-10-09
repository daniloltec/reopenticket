<?php

include('../../../inc/includes.php');

header('Content-Type: application/json; charset=UTF-8');

if (!Session::getLoginUserID()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
    return;
}

$tickets_id = (int) ($_POST['tickets_id'] ?? 0);
$result = PluginReopenticketTicketreopen::reopen($tickets_id, (string) ($_POST['reason'] ?? ''));

if (!$result['ok']) {
    http_response_code(400);
}

echo json_encode($result);
