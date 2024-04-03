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

class ModelJobArea extends A00_MpJobsModelTemplate
{
    public $name;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_job_area',
        'primary' => 'id_job_area',
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

    public static function getJobAreas()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('ja.id_job_area, ja.name')
            ->from('mp_job_area_lang', 'ja')
            ->where('id_lang = ' . (int) Context::getContext()->language->id)
            ->orderBy('ja.name ASC');
        $rows = $db->executeS($sql);
        if (!$rows) {
            return [];
        }
        return $rows;
    }

    public static function getJobAreaById($id_job_area)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('ja.id_job_area, ja.name')
            ->from('mp_job_area_lang', 'ja')
            ->where('ja.id_job_area = ' . (int) $id_job_area);
        $row = $db->getRow($sql);
        if ($row) {
            return $row;
        }

        return [];
    }

    public static function getJobAreaByName($name)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('ja.id_job_area, ja.name')
            ->from('mp_job_area_lang', 'ja')
            ->where('ja.name = "' . pSQL($name) . '"');
        $row = $db->getRow($sql);
        if ($row) {
            return $row;
        }

        return [];
    }

    public static function getJobAreasAsArray()
    {
        $rows = self::getJobAreas();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['id_job_area']] = $row['name'];
        }

        return $out;
    }

    public static function getJobAreaName($id_job_area)
    {
        $row = self::getJobAreaById($id_job_area);
        if ($row) {
            return $row['name'];
        }

        return '';
    }
}