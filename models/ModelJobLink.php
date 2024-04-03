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

class ModelJobLink extends A00_MpJobsModelTemplate
{
    public $id_job_area;
    public $id_job_name;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_job_link',
        'primary' => 'id_job_link',
        'fields' => [
            'id_job_area' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'id_job_name' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
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

    public static function getJobsByIdArea($id_area)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('jn.id_job_name, jn.name')
            ->from('mp_job_name_lang', 'jn')
            ->innerJoin('mp_job_link', 'jl', 'jl.id_job_name=jn.id_job_name')
            ->where('jl.id_job_area = ' . (int) $id_area)
            ->orderBy('jn.name ASC');
        $rows = $db->executeS($sql);
        if ($rows) {
            return $rows;
        }

        return [];
    }
}