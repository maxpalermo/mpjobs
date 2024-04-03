{*
* 2017 mpSOFT
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    mpSOFT <info@mpsoft.it>
*  @copyright 2017 mpSOFT Massimiliano Palermo
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of mpSOFT
*}
<div id="tab-eurosolution">
    <!-- Tab Eurosolution & subject-->
    <div class="row">
        <div class="col-xs-6">
            <dl class="well list-detail" style="overflow: hidden;">
                <label>{l s="Id EuroSolution"}</label>
                <br>
                <div class="col-md-10">
                    <input type="text" class="form-control text-right" id="id_eur" value="{$customer->id_eur}">
                </div>
                <div class="col-md-2">
                        <a href="#" id="save_id_eur" class="btn btn-default" onclick="javascript:saveIdEur();">
                        <i class="icon icon-save"></i>
                    </a>
                </div>
            </dl>
        </div>
        <div class="col-xs-6">
            <dl class="well list-detail" style="overflow: hidden;">
                <label>{l s="Soggetto"}</label>
                <br>
                <div class="col-md-10">
                    <select id="subject" class="form-control">
                        <option value="0" {if isset($cust_subj) && $cust_subj==0}selected{/if}>{l s='Errore'}</option>
                        <option value="1" {if isset($cust_subj) && $cust_subj==1}selected{/if}>{l s='Ente'}</option>
                        <option value="2" {if isset($cust_subj) && $cust_subj==2}selected{/if}>{l s='Giuridico'}</option>
                        <option value="3" {if isset($cust_subj) && $cust_subj==3}selected{/if}>{l s='Privato'}</option>
                    </select>
                </div>
                <div class="col-md-2">
                        <a href="#" id="save_subj" class="btn btn-default" onclick="javascript:saveSubj();">
                        <i class="icon icon-save"></i>
                    </a>
                </div>
            </dl>
        </div>
    </div>
</div>
<script type="text/javascript">
    function saveIdEur()
    {
        var e = $.Event();
        e.preventDefault();
        $.ajax({
            type: "post",
            dataType: "json",
            data:
            {
                action: "saveIdEur",
                ajax: true,
                id_eur: $("#id_eur").val(),
                id_order: '{$id_order}'
            },
            success: function(response)
            {
                var id_eur = response.id_eur;
                $("#id_eur").val(id_eur);
                $.growl.notice({
                    title: "{l s='Operazione eseguita'}",
                    message: "{l s='Codice EuroSolution aggiornato.'}"
                });
            },
            error: function(response)
            {
                $.growl.error({
                    title: "{l s='Errore'}",
                    message: "{l s='Impossibile aggiornare il codice.'}"
                });
                console.log(response);
            }
        });
    }
    function saveSubj()
    {
        var e = $.Event();
        e.preventDefault();
        $.ajax({
            type: "post",
            dataType: "json",
            data:
            {
                action: "saveSubject",
                ajax: true,
                subject: $("#subject").val(),
                id_order: '{$id_order}'
            },
            success: function(response)
            {
                $.growl.notice({
                    title: "{l s='Operazione eseguita'}",
                    message: "{l s='Soggetto aggiornato.'}"
                });
            },
            error: function(response)
            {
                $.growl.error({
                    title: "{l s='Errore'}",
                    message: "{l s='Impossibile aggiornare il soggetto.'}"
                });
                console.log(response);
            }
        });
    }
    $(document).ready(function(){
        $('#tabAddresses').closest('.panel').find('.panel-heading:first').after($('#tab-eurosolution').detach());
    });
</script>