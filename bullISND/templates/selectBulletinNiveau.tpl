<div id="selecteur" class="noprint" style="clear:both">
	<form name="selecteur" id="formSelecteur" method="POST" action="index.php">
		Bulletin n° <select name="bulletin" id="bulletin">
		{section name=boucleBulletin start=1 loop=$nbBulletins+1}
			<option value="{$smarty.section.boucleBulletin.index}" {if $smarty.section.boucleBulletin.index == $bulletin}selected{/if}>{$smarty.section.boucleBulletin.index}</option>
		{/section}
	</select>
	Année:
	<select name="niveau" id="niveau">
		<option value="">Niveau</option>
		{foreach from=$listeNiveaux item=unNiveau}
			<option value="{$unNiveau}"{if $unNiveau eq $niveau} selected{/if}>{$unNiveau}</option>
		{/foreach}
	</select>
	
	<input type="submit" value="OK" name="OK" id="envoi">
	<input type="hidden" name="action" value="{$action}">
	<input type="hidden" name="mode" value="{$mode}">
	<input type="hidden" name="etape" value="showNiveau">
	</form>
</div>

<script type="text/javascript">
{literal}
$(document).ready (function() {
	$("#formSelecteur").submit(function(){
		$("#wait").show();
		$.blockUI();
		})
	
	$("#bulletin, #niveau").change(function(){
		$("#formSelecteur").submit();
		})
	})
{/literal}
</script>