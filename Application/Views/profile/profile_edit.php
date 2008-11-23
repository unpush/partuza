<?
$this->template('/common/header.php');
?>
<div id="profileInfo" class="blue">
	<?
$this->template('profile/profile_info.php', $vars);
?>
	<?
$date_of_birth_month = date('n', $vars['person']['date_of_birth']);
$date_of_birth_day = date('j', $vars['person']['date_of_birth']);
$date_of_birth_year = date('Y', $vars['person']['date_of_birth']);
?>
</div>
<div id="profileContentWide">
<div style="padding: 12px">
<form method="post" enctype="multipart/form-data">
<ul id="tabs" class="subsection_tabs">
	<li><a href="#basic">Basic</a></li>
	<li><a href="#contact">Contact</a></li>
	<li><a href="#relationship">Relationship</a></li>
	<li><a href="#personal">Personal</a></li>
	<li><a href="#education">Education</a></li>
	<li><a href="#work">Work</a></li>
	<li><a href="#oauth">OAuth</a></li>
	<li><a href="#picture">Picture</a></li>
</ul>

<div id="basic">
<div class="form_entry">
<div class="form_label"><label for="first_name">first name</label></div>
<input type="text" name="first_name" id="first_name"
	value="<?=isset($vars['person']['first_name']) ? $vars['person']['first_name'] : ''?>" />
</div>

<div class="form_entry">
<div class="form_label"><label for="last_name">last name</label></div>
<input type="text" name="last_name" id="last_name"
	value="<?=isset($vars['person']['last_name']) ? $vars['person']['last_name'] : ''?>" />
</div>

<div class="form_entry">
<div class="form_label"><label for="nickname">nickname</label></div>
<input type="text" name="nickname" id="nickname"
	value="<?=isset($vars['person']['nickname']) ? $vars['person']['nickname'] : ''?>" />
</div>

<div class="form_entry">
<div class="form_label"><label for="gender">gender</label></div>
<select name="gender" id="gender">
	<option value="-">-</option>
	<option value='FEMALE'
		<?=$vars['person']['gender'] == 'FEMALE' ? ' SELECTED' : ''?>>Female</option>
	<option value='MALE'
		<?=$vars['person']['gender'] == 'MALE' ? ' SELECTED' : ''?>>Male</option>
</select></div>

<div class="form_entry">
<div class="form_label"><label for="date_of_birth_month">date of birth</label></div>
<select name="date_of_birth_month" id="date_of_birth_month"
	style="width: auto">
	<option value="-">-</option>
					<?
    for ($month = 1; $month <= 12; $month ++) {
      $sel = $month == $date_of_birth_month && $vars['person']['date_of_birth'] != 0 ? ' SELECTED' : '';
      echo "<option value=\"$month\"$sel>$month</option>\n";
    }
    ?>
					</select> <select name="date_of_birth_day" id="date_of_birth_day"
	style="width: auto">
	<option value="-">-</option>
					<?
    for ($day = 1; $day <= 31; $day ++) {
      $sel = $day == $date_of_birth_day && $vars['person']['date_of_birth'] != 0 ? ' SELECTED' : '';
      echo "<option value=\"$day\"$sel>$day</option>\n";
    }
    ?>
					</select> <select name="date_of_birth_year" id="date_of_birth_year"
	style="width: auto">
	<option value="-">-</option>
					<?
    for ($year = 1940; $year <= 2008; $year ++) {
      $sel = $year == $date_of_birth_year && $vars['person']['date_of_birth'] != 0 ? ' SELECTED' : '';
      echo "<option value=\"$year\"$sel>$year</option>\n";
    }
    ?>
					</select></div>

<div class="form_entry">
<div class="form_label"><label for="political_views">political views</label></div>
<input type="text" name="political_views" id="political_views"
	value="<?=isset($vars['person']['political_views']) ? $vars['person']['political_views'] : ''?>" />
</div>

<div class="form_entry">
<div class="form_label"><label for="religion">religion</label></div>
<input type="text" name="religion" id="religion"
	value="<?=isset($vars['person']['religion']) ? $vars['person']['religion'] : ''?>" />
</div>

<div class="form_entry">
<div class="form_label"><label for="children">children</label></div>
<select name="children" id="children">
	<option value="-">-</option>
	<option value="none"
		<?=$vars['person']['children'] == 'none' ? ' SELECTED' : ''?>>none</option>
					<?
    for ($children = 1; $children <= 4; $children ++) {
      $sel = $vars['person']['children'] == $children ? ' SELECTED' : '';
      echo "<option value=\"$children\"$sel>$children</option>\n";
    }
    ?>				
					<option value="more then 4">more then 4</option>
</select></div>

<div class="form_entry">
<div class="form_label"><label for="drinker">drinker</label></div>
<select name="drinker" id="drinker">
	<option value="-">-</option>
	<option value='HEAVILY'
		<?=$vars['person']['drinker'] == 'HEAVILY' ? ' SELECTED' : ''?>>Heavily</option>
	<option value='NO'
		<?=$vars['person']['drinker'] == 'NO' ? ' SELECTED' : ''?>>No</option>
	<option value='OCCASIONALLY'
		<?=$vars['person']['drinker'] == 'OCCASIONALLY' ? ' SELECTED' : ''?>>Occasionally</option>
	<option value='QUIT'
		<?=$vars['person']['drinker'] == 'QUIT' ? ' SELECTED' : ''?>>Quit</option>
	<option value='QUITTING'
		<?=$vars['person']['drinker'] == 'QUITTING' ? ' SELECTED' : ''?>>Quitting</option>
	<option value='REGULARLY'
		<?=$vars['person']['drinker'] == 'REGULARLY' ? ' SELECTED' : ''?>>Regularly</option>
	<option value='SOCIALLY'
		<?=$vars['person']['drinker'] == 'SOCIALLY' ? ' SELECTED' : ''?>>Socially</option>
	<option value='YES'
		<?=$vars['person']['drinker'] == 'YES' ? ' SELECTED' : ''?>>Yes</option>
</select></div>

<div class="form_entry">
<div class="form_label"><label for="smoker">smoker</label></div>
<select name="smoker" id="smoker">
	<option value="-">-</option>
	<option value='HEAVILY'
		<?=$vars['person']['smoker'] == 'HEAVILY' ? ' SELECTED' : ''?>>Heavily</option>
	<option value='NO'
		<?=$vars['person']['smoker'] == 'NO' ? ' SELECTED' : ''?>>No</option>
	<option value='OCCASIONALLY'
		<?=$vars['person']['smoker'] == 'OCCASIONALLY' ? ' SELECTED' : ''?>>Ocasionally</option>
	<option value='QUIT'
		<?=$vars['person']['smoker'] == 'QUIT' ? ' SELECTED' : ''?>>Quit</option>
	<option value='QUITTING'
		<?=$vars['person']['smoker'] == 'QUITTING' ? ' SELECTED' : ''?>>Quitting</option>
	<option value='REGULARLY'
		<?=$vars['person']['smoker'] == 'REGULARLY' ? ' SELECTED' : ''?>>Regularly</option>
	<option value='SOCIALLY'
		<?=$vars['person']['smoker'] == 'SOCIALLY' ? ' SELECTED' : ''?>>Socially</option>
	<option value='YES'
		<?=$vars['person']['smoker'] == 'YES' ? ' SELECTED' : ''?>>Yes</option>
</select></div>

<div class="form_entry">
<div class="form_label"><label for="ethnicity">ethnicity</label></div>
<select id="ethnicity" name="ethnicity">
	<option value="-">-</option>
	<option value="african american (black)"
		<?=$vars['person']['ethnicity'] == 'african american (black)' ? ' SELECTED' : ''?>>african
	american (black)</option>
	<option value="asian"
		<?=$vars['person']['ethnicity'] == 'asian' ? ' SELECTED' : ''?>>asian</option>
	<option value="caucasian (white)"
		<?=$vars['person']['ethnicity'] == 'caucasian (white)' ? ' SELECTED' : ''?>>caucasian
	(white)</option>
	<option value="east indian"
		<?=$vars['person']['ethnicity'] == 'east indian' ? ' SELECTED' : ''?>>east
	indian</option>
	<option value="hispanic/latino"
		<?=$vars['person']['ethnicity'] == 'hispanic/latino' ? ' SELECTED' : ''?>>hispanic/latino</option>
	<option value="middle eastern"
		<?=$vars['person']['ethnicity'] == 'middle eastern' ? ' SELECTED' : ''?>>middle
	eastern</option>
	<option value="native american"
		<?=$vars['person']['ethnicity'] == 'native american' ? ' SELECTED' : ''?>>native
	american</option>
	<option value="pacific islander"
		<?=$vars['person']['ethnicity'] == 'pacific islander' ? ' SELECTED' : ''?>>pacific
	islander</option>
	<option value="multi-ethnic"
		<?=$vars['person']['ethnicity'] == 'multi-ethnic' ? ' SELECTED' : ''?>>multi-ethnic</option>
	<option value="other"
		<?=$vars['person']['ethnicity'] == 'other' ? ' SELECTED' : ''?>>other</option>
</select></div>
</div>

<div id="contact" style="display: none">emails<br />
addresses<br />
<br />
</div>

<div id="relationship" style="display: none">
<div class="form_entry">
<div class="form_label"><label for="relationship_status">relationship
status</label></div>
<select name="relationship_status" id="relationship_status">
	<option value="-">-</option>
	<option value="Single"
		<?=$vars['person']['relationship_status'] == 'Single' ? ' SELECTED' : ''?>>Single</option>
	<option value="In a relationship"
		<?=$vars['person']['relationship_status'] == 'In a relationship' ? ' SELECTED' : ''?>>In
	a relationship</option>
	<option value="Engaged"
		<?=$vars['person']['relationship_status'] == 'Engaged' ? ' SELECTED' : ''?>>Engaged</option>
	<option value="Married"
		<?=$vars['person']['relationship_status'] == 'Married' ? ' SELECTED' : ''?>>Married</option>
	<option value="It's complicated"
		<?=$vars['person']['relationship_status'] == 'It\'s complicated' ? ' SELECTED' : ''?>>It's
	complicated</option>
	<option value="In an open relationship"
		<?=$vars['person']['relationship_status'] == 'In an open relationship' ? ' SELECTED' : ''?>>In
	an open relationship</option>
</select></div>
<div class="form_entry">
<div class="form_label"><label for="looking_for">looking for</label></div>
<select name="looking_for" id="looking_for">
	<option value="-">-</option>
	<option value="Dating"
		<?=$vars['person']['looking_for'] == 'Dating' ? ' SELECTED' : ''?>>Dating</option>
	<option value="Friends"
		<?=$vars['person']['looking_for'] == 'Friends' ? ' SELECTED' : ''?>>Friends</option>
	<option value="Relationship"
		<?=$vars['person']['looking_for'] == 'Relationship' ? ' SELECTED' : ''?>>Relationship</option>
	<option value="Networking"
		<?=$vars['person']['looking_for'] == 'Networking' ? ' SELECTED' : ''?>>Networking</option>
	<option value="Activity partners"
		<?=$vars['person']['looking_for'] == 'Activity partners' ? ' SELECTED' : ''?>>Activity
	partners</option>
</select></div>
<div class="form_entry">
<div class="form_label"><label for="living_arrangement">living
arrangement</label></div>
<select name="living_arrangement" id="living_arrangement">
	<option value="-">-</option>
	<option value="Alone"
		<?=$vars['person']['living_arrangement'] == 'Alone' ? ' SELECTED' : ''?>>Alone</option>
	<option value="With roommate(s)"
		<?=$vars['person']['living_arrangement'] == 'With roommate(s)' ? ' SELECTED' : ''?>>With
	roommate(s)</option>
	<option value="With partner"
		<?=$vars['person']['living_arrangement'] == 'With partner' ? ' SELECTED' : ''?>>With
	partner</option>
	<option value="With kid(s)"
		<?=$vars['person']['living_arrangement'] == 'With kid(s)' ? ' SELECTED' : ''?>>With
	kid(s)</option>
	<option value="With pet(s)"
		<?=$vars['person']['living_arrangement'] == 'With pet(s)' ? ' SELECTED' : ''?>>With
	pet(s)</option>
	<option value="With parent(s)"
		<?=$vars['person']['living_arrangement'] == 'With parent(s)' ? ' SELECTED' : ''?>>With
	parent(s)</option>
</select></div>
</div>

<div id="personal" style="display: none">
<div class="form_entry">
<div class="form_label"><label for="about_me">about me</label></div>
<textarea name="about_me" id="about_me"><?=isset($vars['person']['about_me']) ? $vars['person']['about_me'] : ''?></textarea>
</div>
<div class="form_entry">
<div class="form_label"><label for="fashion">fashion</label></div>
<textarea name="fashion" id="fashion"><?=isset($vars['person']['fashion']) ? $vars['person']['fashion'] : ''?></textarea>
</div>
<div class="form_entry">
<div class="form_label"><label for="happiest_when">happiest when</label></div>
<textarea name="happiest_when" id="happiest_when"><?=isset($vars['person']['happiest_when']) ? $vars['person']['happiest_when'] : ''?></textarea>
</div>
<div class="form_entry">
<div class="form_label"><label for="humor">humor</label></div>
<textarea name="humor" id="humor"><?=isset($vars['person']['humor']) ? $vars['person']['humor'] : ''?></textarea>
</div>
<div class="form_entry">
<div class="form_label"><label for="job_interests">job interests</label></div>
<textarea name="job_interests" id="job_interests"><?=isset($vars['person']['job_interests']) ? $vars['person']['job_interests'] : ''?></textarea>
</div>
<div class="form_entry">
<div class="form_label"><label for="pets">pets</label></div>
<textarea name="pets" id="pets"><?=isset($vars['person']['pets']) ? $vars['person']['pets'] : ''?></textarea>
</div>
<div class="form_entry">
<div class="form_label"><label for="scared_of">scared of</label></div>
<textarea name="scared_of" id="scared_of"><?=isset($vars['person']['scared_of']) ? $vars['person']['scared_of'] : ''?></textarea>
</div>
</div>

<div id="education" style="display: none">Schools here<br />
</div>

<div id="work" style="display: none">Jobs here<br />
</div>

<div id="picture" style="display: none">
<div>
<div class="friend" style="margin-right: 12px">
<div class="thumb">
<center><img
	src="<?=Image::by_size(PartuzaConfig::get('site_root') . (! empty($vars['person']['thumbnail_url']) ? $vars['person']['thumbnail_url'] : '/images/people/nophoto.gif'), 64, 64)?>" /></center>
</div>
<p class="uname">Current profile photo</p>
</div>
Select a new photo to upload<br />
<input type="hidden" name="MAX_FILE_SIZE" value="6000000" /> <input
	type="file" name="profile_photo" />
<div style="clear: both"></div>
</div>
</div>
<div id="oauth" style="display: none">
<div class="form_entry"><br />
<i>The OAuth consumer key and secret are automatically generated and
unique for your profile.<br />
They can be used to develop an REST+OAuth client, if your not developing
an OAuth client,<br />
feel free to ignore these values :-)</i> <br />
<br />
</div>
<div class="form_entry">
<div class="form_label"><label for="oauth_consumer_key">oauth consumer
key</label></div>
					<?=$vars['oauth']['consumer_key']?>
				</div>
<div class="form_entry">
<div class="form_label"><label for="oauth_consumer_secret">oauth
consumer secret</label></div>
					<?=$vars['oauth']['consumer_secret']?>
				</div>
</div>
<br />
<input type="submit" class="submit" value="save" /></form>
</div>
</div>
<script>
	//Event.observe(window, 'load', function() {
 	   new Control.Tabs('tabs');
 	// });
</script>

<div style="clear: both"></div>
<?
$this->template('/common/footer.php');
?>