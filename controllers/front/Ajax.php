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
if (!defined('_PS_VERSION_')) {
    exit;
}

class MpOEurosolutionAjaxModuleFrontController extends ModuleFrontController
{
    protected $controller_name;
    protected $dbHelper;
    protected $id_lang;

    public function __construct()
    {
        parent::__construct();
        $this->controller_name = 'Ajax';
        $this->id_lang = (int) Context::getContext()->language->id;
    }

    public function initContent()
    {
        $this->ajax = true;
        // your code here
        parent::initContent();
    }

    public function displayAjax()
    {
        $action = Tools::getValue('action');
        $this->response("METHOD $action NOT FOUND");
    }

    protected function response($params)
    {
        header('Content-Type: application/json; charset=utf-8');
        exit (json_encode($params));
    }

    public function displayAjaxSaveIdEur()
    {
        $id_order = (int) Tools::getValue('id_order', 0);
        $id_eur = (int) Tools::getValue('id_eur', 0);
        $order = new Order($id_order);
        $id_customer = (int) $order->id_customer;
        Db::getInstance()->update(
            'customer',
            array(
                'id_eur' => $id_eur,
            ),
            'id_customer=' . (int) $id_customer
        );
        $id_eur = (int) Db::getInstance()->getValue(
            "select id_eur from " . _DB_PREFIX_ . "customer where id_customer=" . (int) $id_customer
        );
        print Tools::jsonEncode(
            array(
                'id_eur' => $id_eur,
            )
        );
        exit();
    }

    public function displayAjaxSaveSubject()
    {
        $id_order = (int) Tools::getValue('id_order', 0);
        $subject = (int) Tools::getValue('subject', 0);
        $order = new Order($id_order);
        Db::getInstance()->update(
            'address',
            array(
                'subject' => (int) $subject,
            ),
            'id_customer=' . (int) $order->id_customer
        );
        $subject = (int) Db::getInstance()->getValue(
            "select subject from " . _DB_PREFIX_ . "address where id_address=" . (int) $order->id_address_invoice
        );
        print Tools::jsonEncode(
            array(
                'subject' => $subject,
            )
        );
        exit();
    }
}