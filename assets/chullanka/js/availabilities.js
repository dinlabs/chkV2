window.dispos = {};
window.displayAvailabilities = function(variantId)
{
    //console.log(dispos);
    var $container = $('#availabilities');
    $container = $('#availabilities').addClass('loading');
    
    if(variantId != undefined)
    {
        $container.find('.availability').addClass('hidden');
        var $good = $container.find('#js_good');
        var $warning = $container.find('#js_warning');
        var $alert = $container.find('#js_alert');
        var $stores = $container.find('#js_stores');

        var $btnAddToCart = $('#addToCart button');
        
        var productPrice = $('#variantMapping [data-id=' + variantId + ']').data('price');//en centimes
        var price4free = 6000;//en centimes
        var defaultPriceTxt = 'à 9,90 €';
        var limitH = 12;
        var dayInSec = 24 * 3600 * 1000;

        if(variantId != '')
        {
            var variant = window.dispos[variantId];
            var canBuy = false;
            var inShip = false;
            var inShop = false;
            var availableStores = [];

            
            //console.log(variant);
            for(var key in variant)
            {
                var qty = variant[key];
                if(key == 'web')
                {
                    if(qty > 0)
                    {
                        inShip = true;
                        canBuy = true;

                        var _shipPrice = (productPrice >= price4free) ? 'GRATUITE' : defaultPriceTxt;
                        $good.find('.price').html(_shipPrice);
                        
                        var _now = new Date();
                        var dayToday = _now.getDay();
                        var hourNow = _now.getHours();
                        
                        var _delivery = new Date();
                        _delivery.setTime(_delivery.getTime() + 2*dayInSec);

                        if(dayToday == 6)
                            _delivery.setTime(_delivery.getTime() + dayInSec);// si samedi
                        
                        if((dayToday == 5) && (hourNow >= 12))
                            _delivery.setTime(_delivery.getTime() + 2*dayInSec);// si vendredi après-midi
                        
                        // si au final on est dimanche, on ajoute un jour
                        if(_delivery.getDay() == 0) _delivery.setTime(_delivery.getTime() + dayInSec);
                        
                        var _deliveryTxt = 'à partir du ';
                        if(_delivery.getDate().toString().length < 2) _deliveryTxt += '0';
                        _deliveryTxt += _delivery.getDate() + '/';
                        if((_delivery.getMonth()+1).toString().length < 2) _deliveryTxt += '0';
                        _deliveryTxt += (_delivery.getMonth()+1) + '/' + _delivery.getFullYear();
                        
                        $good.find('.date').html(_deliveryTxt);
                    }
                }
                else
                {
                    var $storeInPop = $('#dispoMagPop #' + key);
                    $storeInPop.find('.infosup').addClass('hidden');
                    $storeInPop.find('.infos div').addClass('hidden');

                    if(qty > 0)
                    {
                        inShop = true;
                        canBuy = true;
                        availableStores.push(key);
                        $storeInPop.find('.good').removeClass('hidden');

                        if(qty <= 1)
                        {
                            $storeInPop.find('.infosup').removeClass('hidden');
                        }
                    }
                    else
                    {
                        $storeInPop.find('.warning').removeClass('hidden');
                    }
                }
                //console.log(key + ' ==> ' + qty);
            }
        }

        // reaffichage
        if(canBuy) 
        {
            if(inShip)
            {
                $good.removeClass('hidden');
            }
            else
            {
                if(inShop)
                {
                    if(availableStores.length > 1)
                    {
                        $stores.removeClass('hidden');
                    }
                    else
                    {
                        var _name = $('#dispoMagPop #' + availableStores[0] + ' h3').html();
                        $warning.find('.magname').html(_name);
                        $warning.removeClass('hidden');
                    }
                }
            }
            $btnAddToCart.prop('disabled', '');
        }
        else 
        {
            $alert.removeClass('hidden');
            $btnAddToCart.prop('disabled', 'disabled');
        }
    }
    $container.removeClass('loading');
}

window.checkAvailabilities = function()
{
    var selector = '';
    $('#sylius-product-adding-to-cart select[data-option]').each(function (index, element) {
        var select = $(element);
        var option = select.find('option:selected').val();
        selector += '[data-' + select.attr('data-option') + '="' + option + '"]';
    });
    var vid = $('#variantMapping').find(selector).attr('data-id');
    displayAvailabilities(vid);
}