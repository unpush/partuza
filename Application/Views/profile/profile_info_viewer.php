<?
if (! isset($vars['is_friend']) || ! $vars['is_friend']) {
  ?>
<a
	href="<?=PartuzaConfig::get('web_prefix');?>/home/addfriend/<?=$vars['person']['id']?>">Add <?=$vars['person']['first_name']?> as friend</a>
<?
} elseif (isset($vars['is_friend']) && $vars['is_friend']) {
  ?>
<a
	href="<?=PartuzaConfig::get('web_prefix');?>/home/removefriend/<?=$vars['person']['id']?>">Remove <?=$vars['person']['first_name']?> as friends</a>
<?
}
?>
