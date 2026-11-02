<?php
namespace Custom\Uslugi;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Application;

class ServiceTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'custom_uslugi_service';
    }

    public static function getMap()
    {
        return [
            (new Entity\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new Entity\StringField('NAME'))->configureRequired()->configureTitle('Название услуги'),
            (new Entity\FloatField('PRICE'))->configureTitle('Цена, руб.'),
            (new Entity\FloatField('NORMO_HOURS'))->configureTitle('Нормо-часы'),
            (new Entity\TextField('PARTS'))->configureTitle('Запчасти (ID)'),
            (new Entity\DatetimeField('DATE_CREATED'))->configureDefaultValue(new Type\DateTime()),
        ];
    }

    public static function ensureTableExists()
    {
        $connection = Application::getConnection();
        $tableName = self::getTableName();

        // Если таблицы нет — создаем
        if (!$connection->isTableExists($tableName)) {
            self::getEntity()->createDbTable();
            return;
        }

        // Если таблица есть, проверяем наличие колонки PARTS
        $sqlHelper = $connection->getSqlHelper();
        $columns = $connection->getTableFields($tableName);

        if (!isset($columns['PARTS'])) {
            $connection->queryExecute(
                "ALTER TABLE " . $sqlHelper->quote($tableName) . " ADD COLUMN " . $sqlHelper->quote('PARTS') . " TEXT NULL"
            );
        }
    }
}