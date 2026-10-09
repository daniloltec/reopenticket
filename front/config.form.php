<?php

include('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

$targetOptions = [
    CommonITILObject::INCOMING => 'Novo',
    CommonITILObject::ASSIGNED => 'Em atendimento',
];

if (isset($_POST['save'])) {
    $days = max(0, (int) ($_POST['reopen_days'] ?? 0));
    $target = (int) ($_POST['target_status'] ?? CommonITILObject::ASSIGNED);
    if (!isset($targetOptions[$target])) {
        $target = CommonITILObject::ASSIGNED;
    }

    PluginReopenticketConfig::set('reopen_solved', isset($_POST['reopen_solved']) ? '1' : '0');
    PluginReopenticketConfig::set('reopen_closed', isset($_POST['reopen_closed']) ? '1' : '0');
    PluginReopenticketConfig::set('require_reason', isset($_POST['require_reason']) ? '1' : '0');
    PluginReopenticketConfig::set('reopen_days', (string) $days);
    PluginReopenticketConfig::set('target_status', (string) $target);

    Session::addMessageAfterRedirect('Configuração salva.', true, INFO);
    Html::redirect($_SERVER['REQUEST_URI']);
}

Html::header('Reabrir chamado - Configuração', $_SERVER['PHP_SELF'], 'config', 'plugins');

$reopenSolved = PluginReopenticketConfig::enabled('reopen_solved', true);
$reopenClosed = PluginReopenticketConfig::enabled('reopen_closed', true);
$requireReason = PluginReopenticketConfig::enabled('require_reason', true);
$days = PluginReopenticketConfig::intValue('reopen_days', 0);
$target = PluginReopenticketConfig::intValue('target_status', CommonITILObject::ASSIGNED);
if (!isset($targetOptions[$target])) {
    $target = CommonITILObject::ASSIGNED;
}
?>

<div class="center">
<h2>Reabrir chamado</h2>
<form method="post" action="">
<table class="tab_cadre_fixe">
    <tr><th colspan="2">Quando o botão aparece</th></tr>
    <tr>
        <td>Reabrir chamado solucionado</td>
        <td><input type="checkbox" name="reopen_solved" value="1" <?= $reopenSolved ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td>Reabrir chamado fechado</td>
        <td><input type="checkbox" name="reopen_closed" value="1" <?= $reopenClosed ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td>Prazo em dias depois da solução ou do fechamento</td>
        <td>
            <input type="number" name="reopen_days" min="0" value="<?= (int) $days ?>">
            <span class="text-muted">0 = sem limite</span>
        </td>
    </tr>
    <tr><th colspan="2">O que acontece ao reabrir</th></tr>
    <tr>
        <td>Status de destino</td>
        <td>
            <select name="target_status">
                <?php foreach ($targetOptions as $value => $label) { ?>
                    <option value="<?= (int) $value ?>" <?= $target === (int) $value ? 'selected' : '' ?>>
                        <?= htmlescape($label) ?>
                    </option>
                <?php } ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Motivo obrigatório</td>
        <td><input type="checkbox" name="require_reason" value="1" <?= $requireReason ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td colspan="2" class="center">
            <input type="submit" name="save" value="Salvar" class="submit">
        </td>
    </tr>
</table>
<?php Html::closeForm(); ?>
</div>

<?php
Html::footer();
