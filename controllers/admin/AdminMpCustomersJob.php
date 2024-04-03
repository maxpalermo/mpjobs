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

class AdminMpCustomersJobController extends ModuleAdminController
{
    public const ISACCO_USER = 'MPIMPORTISACCO_USER';
    public const ISACCO_PASSWORD = 'MPIMPORTISACCO_PASSWORD';
    public const ISACCO_URL = 'MPIMPORTISACCO_URL';
    public const ISACCO_TOKEN_ENDPOINT = 'MPIMPORTISACCO_TOKEN_ENDPOINT';
    public const ISACCO_TOKEN_USER = 'MPIMPORTISACCO_TOKEN_USER';
    public const ISACCO_TOKEN_PASSWORD = 'MPIMPORTISACCO_TOKEN_PWD';

    public $adminClassName;
    private $items;
    private $isacco_token = "";
    private $isacco_records = 0;
    private $total_rows = 0;
    private $processed_rows = 0;
    private $cookie_where;

    public function __construct()
    {
        $this->id_lang = (int) ContextCore::getContext()->language->id;
        $this->id_shop = (int) ContextCore::getContext()->shop->id;

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'ModelImportIsacco';
        $this->adminClassName = 'AdminMpImportIsacco';
        $this->token = Tools::getAdminTokenLite($this->adminClassName);
        $this->table = 'mp_customer_job';
        $this->identifier = 'id_customer';
        $this->force_show_bulk_actions = true;

        $this->bulk_actions = array(
            'export' => array(
                'text' => $this->l('Export selected'),
                'confirm' => $this->l('Export selected products?'),
                'icon' => 'icon-upload'
            ),
        );

        parent::__construct();
    }

    private function setConfig($key, $value)
    {
        $continue = false;
        switch ($key) {
            case self::ISACCO_USER:
            case self::ISACCO_PASSWORD:
            case self::ISACCO_URL:
            case self::ISACCO_TOKEN_ENDPOINT:
            case self::ISACCO_TOKEN_USER:
            case self::ISACCO_TOKEN_PASSWORD:
                $continue = true;
                break;
            default:
                $continue = false;
        }

        if ($continue) {
            return Configuration::updateValue($key, $value);
        }
        return false;
    }

    private function getConfig($key)
    {
        $continue = false;
        switch ($key) {
            case self::ISACCO_USER:
            case self::ISACCO_PASSWORD:
            case self::ISACCO_URL:
            case self::ISACCO_TOKEN_ENDPOINT:
            case self::ISACCO_TOKEN_USER:
            case self::ISACCO_TOKEN_PASSWORD:
                $continue = true;
                break;
            default:
                $continue = false;
        }

        if ($continue) {
            return Configuration::get($key);
        }
        return false;
    }

    public function init()
    {
        parent::init();
        $this->fields_list = array(
            'image' => array(
                'title' => $this->l('Image'),
                'float' => true,
                'align' => 'center',
                'width' => '96px',
                'search' => false,
                'callback' => 'getImage',
            ),
            'isa_id' => array(
                'title' => $this->l('id'),
                'align' => 'left',
                'width' => 'auto',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!isa_id',
            ),
            'sku' => array(
                'title' => $this->l('sku'),
                'align' => 'left',
                'width' => 'auto',
                'filter_key' => 'a!sku',
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
                'align' => 'left',
                'width' => 'auto',
                'class' => 'fixed-width-md',
                'filter_key' => 'a!reference'
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'left',
                'width' => 'auto',
                'filter_key' => 'a!name',
            ),
            'color' => array(
                'title' => $this->l('Color'),
                'align' => 'left',
                'width' => 'auto',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!color',
            ),
            'size' => array(
                'title' => $this->l('Size'),
                'align' => 'left',
                'width' => 'auto',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!size',
                'callback' => 'getSize',
            ),
            'date_add' => array(
                'title' => $this->l('Date order'),
                'type' => 'date',
                'align' => 'center',
                'width' => 'auto',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!date_add',
            ),
            'date_upd' => array(
                'title' => $this->l('Date order'),
                'type' => 'date',
                'align' => 'center',
                'width' => 'auto',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!date_upd',
            ),
        );

        $parser = new ParseDataMpImportIsacco($this->module);
        $where = base64_decode($parser->getQuery());
        if ($where) {
            $this->_where = " AND reference in (" . $where . ") ";
            $this->cookie_where = str_replace("'", "", $where);
        }
    }

    private function diff($array1, $array2)
    {
        $diff = array_diff($array1, $array2);
        if ($diff) {
            $this->warnings[] = "Prodotti non trovati: " . implode("<br>", $diff);
        }
    }

    public function initToolbar()
    {
        parent::initToolbar();
        $this->toolbar_btn = array(
            'back' => array(
                'href' => '#',
                'desc' => $this->l('')
            ),
            'delete' => array(
                'href' => $this->context->link->getAdminLink($this->adminClassName) . '&clearQuery',
                'desc' => $this->module->l('Clear Import Filter', $this->adminClassName)
            ),
            'upload' => array(
                'href' => $this->context->link->getAdminLink($this->adminClassName) . '&exportAll',
                'desc' => $this->module->l('Export All Products Filtered', $this->adminClassName)
            )
        );
    }

    public function initContent()
    {
        $template = $this->module->getLocalPath() . 'views/templates/admin/adminForm.tpl';
        $smarty = $this->context->smarty;
        $form = $smarty->fetch($template);
        $this->content = $form;

        parent::initContent();
        if ($this->cookie_where) {
            $db = Db::getInstance();
            $sql = "select distinct reference from " . _DB_PREFIX_ . "mp_import_isacco";
            $rows = $db->executeS($sql);
            $refs = [];
            $where = explode(",", $this->cookie_where);
            if ($rows) {
                foreach ($rows as $row) {
                    $refs[] = $row['reference'];
                }
            }
            $this->diff($where, $refs);
        }

        if (Tools::getIsset('exportAll')) {
            $query = $this->_listsql;
            $pos = strpos($query, "LIMIT");
            if ($pos !== false) {
                $query = Tools::substr($query, 0, $pos);
            }
            $db = Db::getInstance();
            $rows = $db->executeS($query);
            $this->boxes = [];
            foreach ($rows as $row) {
                $this->boxes[] = $row['id_mp_import_isacco'];
            }
            $this->processBulkExport();
        }
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::getIsset('clearQuery')) {
            $parser = new ParseDataMpImportIsacco($this->module);
            $parser->clearQuery();
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink($this->adminClassName));
        }
        if (Tools::isSubmit('submitImport')) {
            $this->setConfig(self::ISACCO_PASSWORD, Tools::getValue('isacco_password'));
            $this->setConfig(self::ISACCO_USER, Tools::getValue('isacco_user'));
            $this->getCatalog();
        }
        if (Tools::isSubmit('submitImportList')) {
            $file = Tools::fileAttachment('import_file');
            $parser = new ParseDataMpImportIsacco($this->module);
            $parsed = $parser->parseData($file);
            if ($parsed) {
                $this->confirmations[] = sprintf(
                    $this->module->l('File %s imported.', $this->adminClassName),
                    $file['name']
                );
                $this->_where = " AND reference in (" . base64_decode($parser->getQuery()) . ")";
            }
        }
    }

    public function processBulkExport()
    {
        $rows = [];
        $reference = "";
        $current_reference = "";
        if (!$this->boxes) {
            $this->warnings[] = "Nessun dato da processare.";
            return false;
        }

        $this->boxes[] = -1;
        $first = (int) array_shift($this->boxes);
        $id_product = (int) $first;
        $row = $this->addToExcel($id_product);
        $reference = $row['reference'];
        $current_reference = $reference;
        $row = $this->addToExcel($id_product);
        $rows[$current_reference] = $row;
        $rows[$current_reference]['size'] = [];
        $rows[$current_reference]['size'][] = $this->getSize("", $row);

        foreach ($this->boxes as $box) {
            if ($box == -1) {
                break;
            }
            $id_product = (int) $box;
            $row = $this->addToExcel($id_product);
            $reference = $row['reference'];
            if ($current_reference == $reference) {
                $rows[$current_reference]['size'][] = $this->getSize("", $row);
            } else {
                $rows[$current_reference]['size'] = implode(";", $rows[$current_reference]['size']);
                $current_reference = $reference;
                $row = $this->addToExcel($id_product);
                $rows[$current_reference] = $row;
                $rows[$current_reference]['size'] = [];
                $rows[$current_reference]['size'][] = $this->getSize("", $row);
            }

        }

        $first = reset($rows);
        $headers = [];
        foreach ($first as $key => $col) {
            $headers[] = $key;
        }

        $rows = array_values($rows);
        array_unshift($rows, $headers);
        //Tools::dieObject($rows);

        $file = rand(11111111, 99999999) . ".xlsx";
        $size = strlen($file);

        $writer = new XLSXWriter();
        $writer->writeSheet($rows);
        $output = $writer->writeToString();
        $size = strlen($output);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $file);
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $size);

        die($output);
    }

    private function addToExcel($id_product)
    {
        $row = [];
        $product = new ModelImportIsacco($id_product);
        //Tools::dieObject($product->json);
        $row = [
            'id_product' => '',
            'sku' => $product->sku,
            'reference' => 'ISA' . $product->reference,
            'ean13' => "",
            'supplier_reference' => $product->reference,
            'product_name' => $product->name,
            'id_supplier' => $this->getSupplier('ISACCO'),
            'condition' => 'new',
            'wholesale_price' => 0,
            'price' => $product->json->price,
            'is_virtual' => 0,
            'description_short' => html_entity_decode($this->getAttribute('description', $product)),
            'id_manufacturer' => "",
            'manufacturer' => 'ISACCO',
            'id_tax_rules_group' => "",
            'tax' => 'IT Standard Rate (22%)',
            'id_category_default' => '',
            'categories' => '',
            'img_root' => 'https://www.isacco.it',
            'img_folder' => '/pub/media/catalog/product',
            'images' => $product->image,
            'suppliers' => 'ISACCO',
            'weight' => html_entity_decode($this->getAttribute('fabric_weight', $product)),
        ];
        return $row;
    }

    private function getAttribute($attribute_key, $product)
    {
        $attributes = $product->json->custom_attributes;
        foreach ($attributes as $attribute) {
            if ($attribute->attribute_code == $attribute_key) {
                return $attribute->value;
            }
        }
        return "";
    }

    private function getSupplier($name)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_supplier')
            ->from('supplier')
            ->where('name = \'' . pSQL($name) . '\'');
        return (int) $db->getValue($sql);
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'form' => array(
                //'tinymce' => true,
                'legend' => array(
                    'title' => $this->module->l('Import Tracking'),
                    'icon' => 'icon-truck',
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l('Tracking CSV'),
                        'name' => 'file_tracking',
                        'class' => 'fixed-width-xl',
                        'lang' => false,
                        'hint' => $this->l('File CSV with tracking'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Change order state'),
                        'name' => 'id_order_state',
                        'class' => 'fixed-width-xl chosen',
                        'lang' => false,
                        'hint' => $this->l('Change order state after tracking'),
                        'options' => array(
                            'query' => [],
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Last file updated'),
                        'name' => 'last_file',
                        'class' => 'fixed-width-xl',
                        'lang' => false,
                        'hint' => $this->l('This is the last filename uploaded'),
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Last file date'),
                        'name' => 'last_file_date',
                        'class' => 'fixed-width-lg text-center',
                        'lang' => false,
                        'hint' => $this->l('This is the last filename date uploaded'),
                        'readonly' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Upload'),
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-upload',
                ),
            )
        );

        $this->helper = new HelperForm();
        $this->helper->show_toolbar = true;
        $this->helper->table = "orders";
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $this->helper->default_form_language = $lang->id;
        $this->helper->allow_employee_form_lang =
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->helper->identifier = "id_order";
        $this->helper->submit_action = 'submitImport';
        $this->helper->currentIndex = $this->context->link->getAdminLink($this->adminClassName, false);
        $this->helper->token = Tools::getAdminTokenLite($this->adminClassName);
        $this->helper->tpl_vars = array(
            'fields_value' => array(
                'type_tracking' => Tools::getValue('type_tracking', 0),
                'id_order_state' => (int) Tools::getValue('id_order_state', (int) Configuration::get("MP_CARRIER_IMPORT_ID_ORDER_STATE")),
                'last_file' => Configuration::get("MP_TRACKING_IMPORT_LAST_FILE"),
                'last_file_date' => Tools::displayDate(Configuration::get("MP_TRACKING_IMPORT_LAST_FILE_DATE"), $lang->id, true),
            ),
            'languages' => $this->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $this->helper->generateForm(array($this->fields_form));
    }

    private function getCatalog($page = 1)
    {
        if (!$this->getConfig(self::ISACCO_TOKEN_PASSWORD)) {
            $this->setConfig(self::ISACCO_URL, 'https://www.isacco.it/rest/V1/products?');
            $this->setConfig(self::ISACCO_USER, '1IMSRL');
            $this->setConfig(self::ISACCO_PASSWORD, '1111');
            $this->setConfig(self::ISACCO_TOKEN_USER, 'catalogo_api');
            $this->setConfig(self::ISACCO_TOKEN_PASSWORD, 'ctg12#25p');
            $this->setConfig(self::ISACCO_TOKEN_ENDPOINT, 'https://www.isacco.it/index.php/rest/V1/integration/admin/token');

        }

        $url = $this->getConfig(self::ISACCO_URL);
        $res = $this->getCurlCatalog($url, $this->token, 1, $page);

        if ($page == 1) {
            ModelImportIsacco::truncate();
        }

        if ($res) {
            foreach ($this->items as $item) {
                $id = (int) $item['id'];
                $sku = pSQL($item['sku']);
                $name = pSQL($item['name']);
                $is_new = 0;
                $reference = "";
                $size = "";
                $color = "";
                $image = "";
                foreach ($item['custom_attributes'] as $attribute) {
                    $code = $attribute['attribute_code'];
                    $value = $attribute['value'];
                    switch ($code) {
                        case "is_new":
                            $is_new = (int) $value;
                            break;
                        case "isacco_catalog_code":
                            $reference = pSQL($value);
                            break;
                        case "color":
                            $color = (int) $value;
                            break;
                        case "size":
                            $size = (int) $value;
                            break;
                        case "image":
                            $image = pSQL($value);
                            break;
                    }
                }

                $record = new ModelImportIsacco();
                $record->image = $image;
                $record->isa_id = $id;
                $record->sku = $sku;
                $record->name = $name;
                $record->is_new = $is_new;
                $record->reference = $reference;
                $record->size = $size;
                $record->color = $color;
                $record->json = json_encode($item);
                //Tools::dieObject($record);
                try {
                    $res = $record->add();
                } catch (\Throwable $th) {
                    $this->errors[] = $th->getMessage();
                    $res = false;
                }
            }
        }

        return $res;
    }

    private function getAuthToken($endpoint, $username, $password)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(['username' => $username, 'password' => $password]),
            CURLOPT_HTTPHEADER => [
                "cache-control: no-cache",
                "content-type: application/json",
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $this->errors[] = $err;
            return false;
        }
        return json_decode($response, true);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getCurlCatalog($url, $token = "", $pageSize = 500, $page = 1, $isNew = false)
    {
        if ($token == '') {
            if ($this->token == '') {
                $token_user = $this->getConfig(self::ISACCO_TOKEN_USER);
                $token_pwd = $this->getConfig(self::ISACCO_TOKEN_PASSWORD);
                $token_endpoint = $this->getConfig(self::ISACCO_TOKEN_ENDPOINT);
                $this->token = $this->getAuthToken($token_endpoint, $token_user, $token_pwd);
            }
            $token = $this->token;
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url . "searchCriteria[pageSize]=$pageSize&searchCriteria[currentPage]=$page",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "cache-control: no-cache",
                "authorization: Bearer " . $token,
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $this->errors[] = $err;
            return false;
        }
        $rows = json_decode($response, true);
        if (is_array($rows)) {
            $total_count = (int) $rows['total_count'];
            $current_items = count($rows['items']);
            $total_rows = ($pageSize * ($page - 1)) + $current_items;

            $this->total_rows = $total_count;
            $this->processed_rows = $total_rows;

            $this->items = $rows['items'];
            if ($total_rows >= $total_count) {
                return false;
            } else {
                return true;
            }
        } else {
            $this->items = $rows;
            return true;
        }
    }

    private function search($items, $attribute_name, $attribute_value)
    {
        $output = [];
        foreach ($items as $item) {
            foreach ($item['custom_attributes'] as $attribute) {
                if (is_array($attribute_value)) {
                    if ($attribute['attribute_code'] == $attribute_name && in_array($attribute['value'], $attribute_value)) {
                        $output[] = $item;
                    }
                } else {
                    if ($attribute['attribute_code'] == $attribute_name && $attribute['value'] == $attribute_value) {
                        $output[] = $item;
                    }
                }
            }
        }
        return $output;
    }

    private function die($value)
    {
        die(json_encode($value));
    }

    public function ajaxProcessGetCatalog()
    {
        $page = (int) Tools::getValue('page');
        $res = $this->getCatalog($page);
        if (!$res) {
            $stop = 1;
        } else {
            $stop = 0;
        }
        $this->die([
            'items' => $this->items,
            'pages' => $page,
            'stop' => $stop,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'errors' => $this->errors,
        ]);
    }

    public function getImage($value)
    {
        return '<img src="' . ModelImportIsacco::IMAGE_PATH . $value . '" style="width: 96px; height: auto;">';
    }

    public function getSize($value, $row)
    {
        $size = "";
        $values = explode("_", $row['sku']);
        if (count($values) == 3) {
            $size = trim($values[2]);
        }

        $value = $size;
        return $value;
    }
}