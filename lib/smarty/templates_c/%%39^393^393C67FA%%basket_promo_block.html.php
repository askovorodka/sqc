<?php /* Smarty version 2.6.11, created on 2016-02-16 18:58:49
         compiled from /home/a0031953/domains/a0031953.xsph.ru/public_html/conf/../modules/shop/front/templates/basket_promo_block.html */ ?>
<div class="cart__items__after">
    <div class="cart__summ g-pull-right">
        <div class="cart__summ_delivery">��������: <span id="delivery_coast_block">0</span> ���.</div>
        <div class="cart__summ_promo">
            <label for="cart_promo">�������� <input id="cart_promo" class="form_input" name="promo_code" placeholder="HAPPY2016" type="text"/></label>
        </div>
        <div class="cart__summ_summary">����� � <span id="cart_summary"><?php echo $this->_tpl_vars['total_price']; ?>
</span> ���.</div>
    </div>

    <?php if (! empty ( $this->_tpl_vars['user_register_sale'] )): ?>
    <div class="cart__summ_discount g-pull-right">
        ���� ���������� ������ � 5%<br/>
        � ������ <span class="g-text-orange"><span id="register_sale"><?php echo $this->_tpl_vars['user_register_sale']; ?>
</span> ���.</span>
    </div>
    <?php endif; ?>

</div>


<?php echo '
<script type="text/javascript">
    var promoDiff = 0;
    var onReadyState = function(){
        $(document).on(\'keyup\', \'#cart_promo\', function(){
            var
                    self = this,
                    value = $(self).val(),
                    value = $.trim(value),
                    value = escape(value);

            $.ajax({
                type: \'get\',
                url: location.protocol + \'//\' + location.hostname + \'/catalog/check_promo/\',
                data: {value : value},
                beforeSend: function(){
                    $(self).css(\'background-color\',\'white\');
                },
                success: function(response){
                    $(self).css(\'background-color\',\'none\');
                    response = $.parseJSON(response);
                    if (typeof response.status !== \'undefined\')
                    {
                        if (response.status == \'success\')
                        {
                            $(self).css(\'background-color\',\'#45b759\');

                            var percent = new Number(response.data.percent);
                            if (totalPrice > 0 && percent > 0)
                            {
                                promoDiff = (totalPrice * percent / 100);
                                totalPrice = totalPrice - promoDiff;
                                changeDelivery();
                            }

                        }
                        else {
                            if (promoDiff > 0){
                                totalPrice = totalPrice + promoDiff;
                                changeDelivery();
                                promoDiff = 0;
                            }
                        }
                    }
                },
                error:function(error){
                    $(self).css(\'background-color\',\'white\');
                    console.log(error);
                }
            });

        });
    }
    document.addEventListener(\'DOMContentLoaded\', onReadyState, false);
</script>
'; ?>