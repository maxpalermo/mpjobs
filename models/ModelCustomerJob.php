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

class ModelCustomerJob extends A00_MpJobsModelTemplate
{
    public $id_job_area;
    public $id_job_name;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'customer_job',
        'primary' => 'id_customer',
        'fields' => [
            'id_job_area' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false,
            ],
            'id_job_name' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false,
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

    public static function getCustomerJobInfo($id_customer)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('ja.id_job_area, ja.name as job_area')
            ->select('jn.id_job_name, jn.name as job_name')
            ->from('customer_job', 'cj')
            ->leftJoin('mp_job_area_lang', 'ja', 'ja.id_job_area=cj.id_job_area')
            ->leftJoin('mp_job_name_lang', 'jn', 'jn.id_job_name=cj.id_job_name')
            ->where('cj.id_customer = ' . (int) $id_customer);
        $row = $db->getRow($sql);
        if ($row) {
            return $row;
        }

        return [];
    }
}