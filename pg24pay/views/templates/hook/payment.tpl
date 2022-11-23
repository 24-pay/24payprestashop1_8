<p class="payment_module">
	<a href="{$link->getModuleLink('pg24pay', 'payment', ['content_only'=>'1'], true)|escape:'html'}" title="{l s='Pay by 24pay gateway.' mod='pg24pay' d='Modules.Pg24pay.Payment'}">
		{l s='24pay gateway' mod='pg24pay' d='Modules.Pg24pay.Payment'} 
            	<span>{l s='(Pay with card or internet banking)' mod='pg24pay' d='Modules.Pg24pay.Payment'}</span>
	</a>
</p>
