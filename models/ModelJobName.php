<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

class ModelJobName extends A00_MpJobsModelTemplate
{
    public $name;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_job_name',
        'primary' => 'id_job_name',
        'multilang' => true,
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 255,
                'required' => true,
                'lang' => true,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true,
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true,
            ],
        ],
    ];

    public static function getCustomerInfo($id_customer)
    {
        $model = new self($id_customer);
        if (Validate::isLoadedObject($model)) {
            return $model;
        }

        return null;
    }

    public static function getJobNames()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_job_name, name')
            ->from('mp_job_name_lang')
            ->where('id_lang = ' . (int) Context::getContext()->language->id)
            ->orderBy('name ASC');
        $rows = $db->executeS($sql);
        if (!$rows) {
            return [];
        }
        return $rows;
    }

    public static function getJobNamesAsArray()
    {
        $rows = self::getJobNames();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id_job_name']] = $row['name'];
        }

        return $result;
    }

    public static function getJobNameById($id_job_name)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_job_name, name')
            ->from('mp_job_name_lang')
            ->where('id_job_name = ' . (int) $id_job_name)
            ->where('id_lang = ' . (int) Context::getContext()->language->id);
        $row = $db->getRow($sql);
        if ($row) {
            return $row;
        }

        return [];
    }

    public static function getJobNameName($id_job_name)
    {
        $row = self::getJobNameById($id_job_name);
        if ($row) {
            return $row['name'];
        }

        return '';
    }
}