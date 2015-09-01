<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a
                href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>

    <div class="box">
        <div class="heading">

            <h1><img src="view/image/payment/w4p.png"
                     style="height:25px; margin-top:-5px;"/> <?php echo $heading_title; ?></h1>

            <div class="buttons">
                <a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
                <a onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a></div>
            </div>

        </div>


        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_merchant; ?></td>
                        <td>
                            <input type="text" name="wayforpay_merchant"
                                   value="<?php echo $wayforpay_merchant; ?>" class="form-control"/>
                            <?php if ($error_merchant) { ?>
                            <div class="text-danger"><?php echo $error_merchant; ?></div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_secretkey; ?></td>
                        <td>
                            <input type="text" name="wayforpay_secretkey"
                                   value="<?php echo $wayforpay_secretkey; ?>" class="form-control"/>
                            <?php if ($error_secretkey) { ?>
                            <div class="text-danger"><?php echo $error_secretkey; ?></div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_returnUrl; ?></td>
                        <td>
                            <input type="text" name="wayforpay_returnUrl"
                                   value="<?php echo $wayforpay_returnUrl; ?>" class="form-control"/>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_serviceUrl; ?></td>
                        <td>
                            <input type="text" name="wayforpay_serviceUrl"
                                   value="<?php echo $wayforpay_serviceUrl; ?>" class="form-control"/>
                        </td>
                    </tr>

                    <tr>
                        <td><?php echo $entry_language; ?></td>
                        <td>
                            <input type="text" name="wayforpay_language"
                                   value="<?php echo ($wayforpay_language == "") ?
                            "RU" : $wayforpay_language; ?>" class="form-control"/>
                        </td>
                    </tr>

                    <tr>
                        <td><?php echo $entry_order_status; ?></td>
                        <td>
                            <select name="wayforpay_order_status_id" class="form-control">
                                <?php
                                foreach ($order_statuses as $order_status) {

                                $st = ($order_status['order_status_id'] == $wayforpay_order_status_id) ? ' selected="selected" ' : "";
                                ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                <?= $st ?> ><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td> <?php echo $entry_status; ?></td>
                        <td>
                            <select name="wayforpay_status" class="form-control">
                                <? $st0 = $st1 = "";
                                 if ( $wayforpay_status == 0 ) $st0 = 'selected="selected"';
                                  else $st1 = 'selected="selected"';
                                ?>
                                <option value="1"
                                <?= $st1 ?> ><?php echo $text_enabled; ?></option>
                                <option value="0"
                                <?= $st0 ?> ><?php echo $text_disabled; ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td><?php echo $entry_sort_order; ?></td>
                        <td>
                            <input type="text" name="wayforpay_sort_order"
                                   value="<?php echo $wayforpay_sort_order; ?>" class="form-control"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

    </div>
    <?php echo $footer; ?>
