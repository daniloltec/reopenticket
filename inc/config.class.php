<?php

/**
 * Configuração do plugin (chave/valor).
 */
class PluginReopenticketConfig
{
    public static function getTable()
    {
        return 'glpi_plugin_reopenticket_configs';
    }

    public static function get(string $name, $default = null)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['name' => $name],
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            return $default;
        }

        return $iterator->current()['value'];
    }

    public static function set(string $name, $value): void
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['name' => $name],
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            $DB->insert(self::getTable(), [
                'name'  => $name,
                'value' => (string) $value,
            ]);
            return;
        }

        $DB->update(self::getTable(), [
            'value' => (string) $value,
        ], [
            'name' => $name,
        ]);
    }

    public static function enabled(string $name, bool $default = true): bool
    {
        $value = self::get($name, $default ? '1' : '0');
        return (string) $value === '1';
    }

    public static function intValue(string $name, int $default = 0): int
    {
        return (int) self::get($name, (string) $default);
    }

    public static function ensureDefaults(): void
    {
        $defaults = [
            'reopen_solved'  => '1',
            'reopen_closed'  => '1',
            'reopen_days'    => '0',
            'target_status'  => (string) CommonITILObject::ASSIGNED,
            'require_reason' => '1',
        ];

        foreach ($defaults as $name => $value) {
            if (self::get($name, null) === null) {
                self::set($name, $value);
            }
        }
    }
}
