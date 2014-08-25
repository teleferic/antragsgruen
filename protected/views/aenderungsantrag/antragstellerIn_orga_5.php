<?php

/**
 * @var AenderungsantraegeController $this
 * @var string $mode
 * @var Aenderungsantrag $antrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerIn
 */


if ($mode == "neu") {
	?>
	<div class="policy_antragstellerIn_orga_5">
		<h3><?= $sprache->get("AntragstellerIn") ?></h3>
		<br>
		<?php if ($this->veranstaltung->isAdminCurUser()) { ?>
			<label><input type="checkbox" name="andere_antragstellerIn"> Ich lege diesen Antrag für eine andere AntragstellerIn an
				<small>(Admin-Funktion)</small>
			</label>
		<?php } ?>

		<div class="antragstellerIn_daten">
			<?php
			echo $form->textFieldRow($antragstellerIn, 'name');
			echo $form->textFieldRow($antragstellerIn, 'organisation');
			echo $form->textFieldRow($antragstellerIn, 'email', array("required" => true));
			echo $form->textFieldRow($antragstellerIn, 'telefon', array("required" => true));
			?>
		</div>

		<div class="control-group" id="Person_typ_chooser">
			<label class="control-label">Ich bin...</label>

			<div class="controls">
				<label><input type="radio" name="Person[typ]" value="mitglied" required checked> Parteimitglied</label><br>
				<label><input type="radio" name="Person[typ]" value="organisation" required> ein Gremium, LAK, ...</label><br>
			</div>
		</div>

		<div class="control-group" id="UnterstuetzerInnen">
			<label class="control-label">UnterstützerInnen<br>(min. 4)</label>

			<div class="controls">
				<?php for ($i = 0; $i < 4; $i++) { ?>
					<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">
					<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="KV" title="KV">
					<br>
				<?php } ?>
			</div>
		</div>
	</div>

	<script>
		$(function () {
			var $chooser = $("#Person_typ_chooser"),
				$unter = $("#UnterstuetzerInnen"),
				$andereAntragstellerIn = $("input[name=andere_antragstellerIn]");
			$chooser.find("input").change(function () {
				if ($chooser.find("input:checked").val() == "mitglied") {
					$unter.show();
					$unter.find("input[type=text]").prop("required", true);
				} else {
					$unter.hide();
					$unter.find("input[type=text]").prop("required", false);
				}
			}).change();
			if ($andereAntragstellerIn.length > 0) $andereAntragstellerIn.change(function () {
				if ($(this).prop("checked")) {
					$(".antragstellerIn_daten input").each(function () {
						var $input = $(this);
						$input.data("orig", $input.val());
						$input.val("");
					});
				} else {
					$(".antragstellerIn_daten input").each(function () {
						var $input = $(this);
						$input.val($input.data("orig"));
					});
				}
			});
		})
	</script>


	<div class="ae_select_confirm">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => $sprache->get("Änderungsantrag stellen"))); ?>
	</div>

	<br><br>


<?php
}
