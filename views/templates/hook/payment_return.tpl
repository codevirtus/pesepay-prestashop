{if $status}
    <p class="alert alert-success">{l s='Your order on %s is complete.' sprintf=[$shop_name] d='pesepay'}
        <br /><br />
        {l s='Please bear with us as we finish processing your order' mod='pesepay'}
        <br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='pesepay'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='pesepay'}</a>.
    </p>
{else}
    <p class="alert alert-warning">
        {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='pesepay'}
        <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='pesepay'}</a>.
    </p>
{/if}
