<option value=''>{Lang::T('Select Routers')}</option>
{if $show_radius|default:false}
    <option value="radius">Radius</option>
{/if}
{foreach $d as $ds}
	<option value="{$ds['name']}">{$ds['name']}</option>
{/foreach}