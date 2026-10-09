<?php

/**
 * Reopen Ticket - Plugin GLPI 11
 *
 * Permite reabrir um chamado solucionado ou fechado por um botão na tela do ticket.
 */

define('PLUGIN_REOPENTICKET_VERSION', '1.0.0');
define('PLUGIN_REOPENTICKET_MIN_GLPI', '11.0');
define('PLUGIN_REOPENTICKET_MAX_GLPI', '11.99');

function plugin_version_reopenticket()
{
    return [
        'name'         => 'Reopen Ticket',
        'version'      => PLUGIN_REOPENTICKET_VERSION,
        'author'       => 'Reopen Ticket',
        'license'      => 'GPLv2+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_REOPENTICKET_MIN_GLPI,
                'max' => PLUGIN_REOPENTICKET_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_init_reopenticket()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['reopenticket'] = true;
    $PLUGIN_HOOKS['config_page']['reopenticket'] = 'front/config.form.php';
    $PLUGIN_HOOKS['pre_show_item']['reopenticket'] = 'plugin_reopenticket_pre_show_item';

    Plugin::registerClass(PluginReopenticketProfile::class, ['addtabon' => [Profile::class]]);
}

function plugin_reopenticket_install()
{
    global $DB;

    if (!isset($DB) || !($DB instanceof DBmysql)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `glpi_plugin_reopenticket_configs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `value` TEXT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $DB->doQuery($sql);

    PluginReopenticketConfig::ensureDefaults();
    PluginReopenticketProfile::installRights();

    return true;
}

function plugin_reopenticket_uninstall()
{
    global $DB;

    $DB->doQuery("DROP TABLE IF EXISTS `glpi_plugin_reopenticket_configs`");
    PluginReopenticketProfile::uninstallRights();

    return true;
}

function plugin_reopenticket_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, PLUGIN_REOPENTICKET_MIN_GLPI, '<')) {
        echo 'Reopen Ticket requer GLPI >= ' . PLUGIN_REOPENTICKET_MIN_GLPI;
        return false;
    }
    return true;
}

function plugin_reopenticket_check_config()
{
    return true;
}
