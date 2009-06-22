<?
$this->template('/common/header.php');
?>
<div id="profileContentWide" style="width: 'auto'; padding-left: 0px;">
<?
$gadget = $vars['application'];
$width = 960;
$view = isset($_GET['view']) ? $_GET['view'] : 'canvas';
$this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 'person' => $vars['person'], 
    'view' => $view));
?>
</div>
<div style="clear: both"></div>
<?
$this->template('/common/footer.php');
?>