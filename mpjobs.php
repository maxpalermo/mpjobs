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

require_once dirname(__FILE__) . '/src/helpers/HtmlHelper.php';
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/models/autoload.php';

class MpJobs extends MpSoft\MpJobs\Module\ModuleTemplate
{
    protected $adminClassName = 'AdminMpCustomersJob';

    public function __construct()
    {
        $this->name = 'mpjobs';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '';

        parent::__construct();

        $this->displayName = $this->l('Customer jobs');
        $this->description = $this->l('Store jobs informations for customers');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $hooks = [
            'actionAdminControllerSetMedia',
            'actionAdminOrdersListingResultsModifier',
            'actionAdminOrdersListingFieldsModifier',
            'actionAdminCustomersListingResultsModifier',
            'actionAdminCustomersListingFieldsModifier',
            'actionAdminCustomersControllerRenderForm',
            'actionObjectCustomerAddAfter',
            'actionObjectCustomerUpdateAfter',
            'actionObjectCustomerDeleteAfter',
            'displayAdminCustomersPersonalInfo',
            'displayAdminOrderBeforeContent',
        ];

        $install = parent::install()
            && $this->registerHooks($this, $hooks)
            && ModelCustomerJob::createTable()
            && ModelJobArea::createTable()
            && ModelJobLink::createTable()
            && ModelJobName::createTable()
            && $this->installMenu(
                $this->l('Mp Customer Jobs'),
                $this->name,
                'AdminParentCustomer',
                $this->adminClassName
            );

        if ($install) {
            ModelJobArea::alterPrimaryKey('mp_job_area_lang', ['id_job_area', 'id_lang']);
            ModelJobName::alterPrimaryKey('mp_job_area_lang', ['id_job_name', 'id_lang']);
            ModelJobLink::createIndex('mp_job_link', ['id_job_area', 'id_job_name'], 'KEY_UNIQUE_JOB_LINK', 'UNIQUE');
        }

        return $install;
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallMenu($this->adminClassName);
    }

    public function hookDisplayAdminOrderBeforeContent($params)
    {
        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);
        $customer = new Customer($order->id_customer);
        $eurosolution = new ModelEurosolution($order->id_customer);
        $template = $this->local_path . 'views/templates/admin/eurosolution.tpl';
        $tpl_vars = array(
            'id_order' => (int) $id_order,
            'customer' => $customer,
            'eurosolution' => $eurosolution,
        );
        $this->context->smarty->assign($tpl_vars);
        return $this->context->smarty->fetch($template);
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $this->context->controller->addCSS($this->getLocalPath() . 'views/css/icons.css');
    }
    public function hookActionAdminCustomersControllerRenderForm(&$params)
    {
        $form = $params['form'];
        $values = $params['values'];
        $id_customer = $params['id_customer'];
        $job = new ModelCustomerJob($id_customer);

        $inputs = [
            [
                'label' => $this->l('Settore'),
                'type' => 'select',
                'name' => 'id_job_area',
                'options' => [
                    'query' => ModelJobArea::getJobAreas(),
                    'id' => 'id_job_area',
                    'name' => 'name',
                ],
                'class' => 'chosen',
                'col' => 4,
            ],
            [
                'label' => $this->l('Professione'),
                'type' => 'select',
                'name' => 'id_job_name',
                'options' => [
                    'query' => ModelJobName::getJobNames(),
                    'id' => 'id_job_name',
                    'name' => 'name',
                ],
                'class' => 'chosen',
                'col' => 4,
            ],
        ];

        if (Validate::isLoadedObject($job)) {
            $values['id_job_area'] = $job->id_job_area;
            $values['id_job_name'] = $job->id_job_name;
        }

        $inputs = array_merge($form['input'], $inputs);
        $form['input'] = $inputs;

        $params['form'] = $form;
        $params['values'] = $values;
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        $id_customer = (int) $params['object']->id;
        $job = new ModelCustomerJob($id_customer);
        $job->id_job_area = (int) Tools::getValue('id_job_area');
        $job->id_job_name = (int) Tools::getValue('id_job_name');
        if (Validate::isLoadedObject($job)) {
            return $job->save();
        }
        $job->id = $id_customer;
        $job->force_id = true;
        return $job->add();
    }

    public function hookDisplayAdminCustomersPersonalInfo($params)
    {
        if ($params['type'] == 'customer') {
            $id_lang = (int) Context::getContext()->language->id;
            $id_customer = (int) $params['id_customer'];
            $customer = new Customer($id_customer);
            $job = new ModelCustomerJob($customer->id);
            try {
                $jobArea = (new ModelJobArea($job->id_job_area, $id_lang))->name;
            } catch (\Throwable $th) {
                $jobArea = '--';
            }
            try {
                $jobName = (new ModelJobName($job->id_job_name, $id_lang))->name;
            } catch (\Throwable $th) {
                $jobName = '--';
            }
            $template = $this->local_path . 'views/templates/admin/adminJobsPersonalInfo.tpl';
            $tpl_vars = [
                'id_customer' => (int) $id_customer,
                'customer' => $customer,
                'job' => $job,
                'elements' => [
                    [
                        'title' => $this->l('Settore'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $jobArea,
                    ],
                    [
                        'title' => $this->l('Professione'),
                        'style' => 'primary',
                        'icon' => '',
                        'label' => $jobName,
                    ],
                ],
            ];
            $this->context->smarty->assign($tpl_vars);
            return $this->context->smarty->fetch($template);
        }

        if ($params['type'] == 'address') {

        }
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        //nothing
    }

    public function hookActionAdminCustomersListingFieldsModifier($params)
    {
        if (isset($params['select'])) {
            //Table alias `a` is `orders` table
            $params['select'] = rtrim($params['select'], ',')
                . ",jobs.`id_job_area` as `id_job_area`\n"
                . ",jobs.`id_job_name` as `id_job_name`\n";
            $params['join'] .=
                " LEFT JOIN `" . _DB_PREFIX_ . 'customer_job` jobs ON '
                . "(a.id_customer=jobs.id_customer)";
        }

        if (isset($params['fields'])) {
            //ID EUROSOLUTION
            $idx = $this->getIndexOfField($params['fields'], 'newsletter');

            $field = [];
            $field['id_job_area'] = [
                'title' => $this->l('Settore'),
                'type' => 'select',
                'list' => ModelJobArea::getJobAreasAsArray(),
                'float' => true,
                'search' => true,
                'filter_key' => 'jobs!id_job_area',
                'orderby' => false,
                'callback' => 'displayJobArea',
                'callback_object' => $this,
            ];
            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx
            );

            $field = [];
            $field['id_job_name'] = [
                'title' => $this->l('Professione'),
                'type' => 'select',
                'list' => ModelJobName::getJobNamesAsArray(),
                'float' => true,
                'search' => true,
                'filter_key' => 'jobs!id_job_name',
                'orderby' => false,
                'callback' => 'displayJobName',
                'callback_object' => $this,
            ];
            //Insert field after customer column
            $params['fields'] = $this->insertValueAtPosition(
                $params['fields'],
                $field,
                $idx + 1
            );
        }
    }

    public function displayIdEur($value)
    {
        if (!$value) {
            return '--';
        }

        return $value;
    }

    public function displayJobArea($value)
    {
        return ModelJobArea::getJobAreaName($value);
    }

    public function displayJobName($value)
    {
        return ModelJobName::getJobNameName($value);
    }
}