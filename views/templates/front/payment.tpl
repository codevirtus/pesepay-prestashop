{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='pesepay'}">{l s='Checkout' mod='pesepay'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Pesepay payment' mod='pesepay'}
    {/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='pesepay'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $error}
    <p class="alert alert-danger">
        {l s='Pesepay Error: ' mod='pesepay'} {$error}
    </p>
{/if}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='pesepay'}
    </p>
{else}
    <form action="{$link->getModuleLink('pesepay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Pesepay payment' mod='pesepay'}
            </h3>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You have chosen to pay via Pesepay.' mod='pesepay'} {l s='Here is a short summary of your order:' mod='pesepay'}
                </strong>
            </p>
            <p>
                - {l s='The total amount of your order is' mod='pesepay'}
                <span id="amount" class="price">{displayPrice price=$total}</span>
                {if $use_taxes == 1}
                    {l s='(tax incl.)' mod='pesepay'}
                {/if}
            </p>
            <p>
                -
                {if $currencies|@count > 1}
                    {l s='We allow several currencies to be paid via Pesepay.' mod='pesepay'}
                <div class="form-group">
                    <label>{l s='Choose one of the following:' mod='pesepay'}</label>
                    <select id="currency_payment" class="form-control" name="currency_payment">
                        {foreach from=$currencies item=currency}
                            <option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>
                                {$currency.name}
                            </option>
                        {/foreach}
                    </select>
                </div>
            {else}
                {l s='We allow the following currency to be paid via Pesepay:' mod='pesepay'}&nbsp;<b>{$currencies.0.name}</b>
                <input type="hidden" name="currency_payment" value="{$currencies.0.id_currency}" />
            {/if}
            </p>
            <p>
                - {l s='After you confirm your order you will be redirected to Pesepay for completion.' mod='pesepay'}
                <br />
                - {l s='Please confirm your order by clicking "I confirm my order".' mod='pesepay'}
            </p>
        </div><!-- .cheque-box -->
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='pesepay'}
            </a>
            <button class="button btn btn-default button-medium" type="submit">
                <span>{l s='I confirm my order' mod='pesepay'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
{/if}