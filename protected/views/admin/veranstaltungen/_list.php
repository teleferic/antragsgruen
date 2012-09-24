<?php
/* @var $this VeranstaltungenController */
/* @var $data Veranstaltung */
?>

<table class="view">

	<tr>
		<th colspan="2"><?php echo GxHtml::link(GxHtml::encode("ID " . $data->id . ": " . $data->name), array('update', 'id' => $data->id)); ?></th>
	</tr>
	<tr>
		<th><?php echo GxHtml::encode($data->getAttributeLabel('datum_von')); ?>:</th>
		<td><?php echo GxHtml::encode($data->datum_von); ?></td>
	</tr>
	<tr>
		<th><?php echo GxHtml::encode($data->getAttributeLabel('datum_bis')); ?>:</th>
		<td><?php echo GxHtml::encode($data->datum_bis); ?></td>
	</tr>

</table>