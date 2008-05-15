<div id="profileRight">
	<div class="gadgets-gadget-chrome">
		<div class="gadgets-gadget-title-bar">
			<div class="gadgets-gadget-title-button-bar"><a href="/profile/friends/<?=$vars['person']['id']?>">View all</a></div>
			<span class="gadgets-gadget-title"><?=$vars['person']['first_name']?>'s friends (<?=count($vars['friends'])?>)</span>
		</div>
		<? foreach ($vars['friends'] as $friend) {
			echo "<div class=\"friend\">
					<div class=\"thumb\"><center>image</center></div>
					<p class=\"uname\"><a href=\"/profile/{$friend['id']}\">{$friend['first_name']}</a></p>
			</div>";
		}
		?>
	</div>
	<div style="clear:both"></div>
	<br />
	<div class="gadgets-gadget-chrome">
		<div class="gadgets-gadget-title-bar"><span class="gadgets-gadget-title">Information</span></div>
		<div style="margin:6px">
		<div class="form_entry"><div class="info_detail"><?=$vars['person']['first_name']." ".$vars['person']['last_name']?></div>name</div>
		<? if (!empty($vars['person']['gender'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['gender']=='MALE'?'Male':'Female'?></div>gender</div> <? } ?>
		<? if (!empty($vars['person']['date_of_birth'])) { ?><div class="form_entry"><div class="info_detail"><?=strftime('%B %e, %Y', $vars['person']['date_of_birth'])?></div>birthday</div> <? } ?>
		<? if (!empty($vars['person']['relationship_status'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['relationship_status']?></div>relationship</div> <? } ?>
		<? if (!empty($vars['person']['looking_for'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['looking_for']?></div>looking for</div> <? } ?>
		<? if (!empty($vars['person']['political_views'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['political_views']?></div>political views</div> <? } ?>
		<? if (!empty($vars['person']['religion'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['religion']?></div>religion</div> <? } ?>
		<? if (!empty($vars['person']['children'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['children']?></div>children</div> <? } ?>
		<? if (!empty($vars['person']['drinker'])) { ?><div class="form_entry"><div class="info_detail"><?=ucwords(strtolower($vars['person']['drinker']))?></div>drinker</div> <? } ?>
		<? if (!empty($vars['person']['smoker'])) { ?><div class="form_entry"><div class="info_detail"><?=ucwords(strtolower($vars['person']['smoker']))?></div>smoker</div> <? } ?>
		<? if (!empty($vars['person']['ethnicity'])) { ?><div class="form_entry"><div class="info_detail"><?=ucwords($vars['person']['ethnicity'])?></div>ethnicity</div> <? } ?>
		
		<? if (!empty($vars['person']['about_me'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['about_me']?></div>about me</div> <? } ?>
		<? if (!empty($vars['person']['fashion'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['fashion']?></div>fashion</div> <? } ?>
		<? if (!empty($vars['person']['happiest_when'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['happiest_when']?></div>happiest when</div> <? } ?>
		<? if (!empty($vars['person']['humor'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['humor']?></div>humor</div> <? } ?>
		<? if (!empty($vars['person']['job_interests'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['job_interests']?></div>job interests</div> <? } ?>
		<? if (!empty($vars['person']['pets'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['pets']?></div>pets</div> <? } ?>
		<? if (!empty($vars['person']['scared_of'])) { ?><div class="form_entry"><div class="info_detail"><?=$vars['person']['scared_of']?></div>scared of</div> <? } ?>
		</div>
	</div>
</div>